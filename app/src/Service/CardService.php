<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\CardAssignment;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\PlanType;
use App\Exception\QuotaExceededException;
use App\Repository\CardAssignmentRepository;
use App\Repository\CardRepository;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

class CardService
{
    public function __construct(
        private CardRepository $cardRepository,
        private QuotaService $quotaService,
        private EntityManagerInterface $entityManager,
        private TeamMemberRepository $teamMemberRepository,
        private CardAssignmentRepository $cardAssignmentRepository,
        private SecureKeyGenerator $secureKeyGenerator
    ) {
    }

    public function createCard(Card $card, User $user): Card
    {
        // Validate quota
        $this->quotaService->validateQuota($user);

        // Generate slug
        $name = $card->getContent()['name'] ?? 'card';
        $slug = $this->generateUniqueSlug($name);
        $card->setSlug($slug);
        $card->setUser($user);
        $card->setStatus('active');

        // Generate public access key if not already set
        if (!$card->getPublicAccessKey()) {
            $card->setPublicAccessKey($this->secureKeyGenerator->generateRandomKey());
        }

        $this->entityManager->persist($card);
        $this->entityManager->flush();

        return $card;
    }

    public function updateCard(Card $card): void
    {
        $this->entityManager->flush();
    }

    public function deleteCard(Card $card): void
    {
        $card->delete();
        $this->entityManager->flush();
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = $this->slugify($name);

        if ($this->cardRepository->slugExists($slug)) {
            $slug = $slug . '-' . bin2hex(random_bytes(4));
        }

        return $slug;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 100);

        // If slug is empty or too short, generate random one
        if (empty($slug) || strlen($slug) < 3) {
            $slug = 'card-' . bin2hex(random_bytes(4));
        }

        return $slug;
    }

    /**
     * @return Card[]
     */
    public function getUserCards(User $user): array
    {
        return $this->cardRepository->findByUser($user);
    }

    /**
     * Check if user can access a card (ownership, team membership, or assignment)
     */
    public function canAccessCard(Card $card, User $user): bool
    {
        // Card owner always has access
        if ($card->getUser() === $user) {
            return true;
        }

        $account = $user->getAccount();
        if (!$account) {
            return false;
        }

        // Check if Enterprise plan
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            return false;
        }

        // Check if user is team member
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return false;
        }

        // ADMINs can view all cards in the account
        if ($teamMember->getRole()->canViewAllCards()) {
            // Verify card belongs to account owner
            return $card->getUser() === $account->getUser();
        }

        // MEMBERs can only access assigned cards
        return $this->cardAssignmentRepository->isAssignedTo($card, $teamMember);
    }

    /**
     * Get cards assigned to a team member
     *
     * @return Card[]
     */
    public function getAssignedCardsForUser(User $user): array
    {
        $account = $user->getAccount();
        if (!$account || $account->getPlanType() !== PlanType::ENTERPRISE) {
            return [];
        }

        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return [];
        }

        $assignments = $this->cardAssignmentRepository->findByTeamMember($teamMember);
        return array_map(fn(CardAssignment $assignment) => $assignment->getCard(), $assignments);
    }

    /**
     * Get all cards accessible to user (owned + assigned if MEMBER, or all account cards if ADMIN)
     *
     * @return Card[]
     */
    public function getAccessibleCardsForUser(User $user): array
    {
        $account = $user->getAccount();
        if (!$account) {
            return $this->getUserCards($user);
        }

        // Non-Enterprise: return owned cards only
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            return $this->getUserCards($user);
        }

        // Check if team member
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return $this->getUserCards($user);
        }

        // ADMINs can view all cards in the account
        if ($teamMember->getRole()->canViewAllCards()) {
            return $this->cardRepository->findByUser($account->getUser());
        }

        // MEMBERs: return assigned cards only
        return $this->getAssignedCardsForUser($user);
    }

    /**
     * Search accessible cards for user with pagination
     *
     * @param User $user
     * @param string|null $query Search query
     * @param int $limit Number of results to return
     * @param int $offset Offset for pagination
     * @return Card[]
     */
    public function searchAccessibleCardsForUser(User $user, ?string $query, int $limit = 10, int $offset = 0): array
    {
        $account = $user->getAccount();
        
        // For non-Enterprise or users without account, search only owned cards
        if (!$account || $account->getPlanType() !== PlanType::ENTERPRISE) {
            return $this->cardRepository->searchByUser($user, $query, $limit, $offset);
        }

        // Check if team member
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return $this->cardRepository->searchByUser($user, $query, $limit, $offset);
        }

        // ADMINs can view all cards in the account
        if ($teamMember->getRole()->canViewAllCards()) {
            return $this->cardRepository->searchByUser($account->getUser(), $query, $limit, $offset);
        }

        // MEMBERs: return assigned cards only
        // We fetch more cards than needed because we need to filter by assignment afterward
        // Multiplier of 3 provides a buffer to account for cards that will be filtered out
        $preFilterMultiplier = 3;
        $allCards = $this->cardRepository->searchByUser($account->getUser(), $query, $limit * $preFilterMultiplier, 0);
        $assignedCards = $this->getAssignedCardsForUser($user);
        $assignedCardIds = array_map(fn($c) => $c->getId(), $assignedCards);
        
        $filteredCards = array_filter($allCards, fn($c) => in_array($c->getId(), $assignedCardIds));
        
        return array_slice($filteredCards, $offset, $limit);
    }

    /**
     * Count accessible cards for user with optional search query
     *
     * @param User $user
     * @param string|null $query Search query
     * @return int
     */
    public function countAccessibleCardsForUser(User $user, ?string $query = null): int
    {
        $account = $user->getAccount();
        
        // For non-Enterprise or users without account, count only owned cards
        if (!$account || $account->getPlanType() !== PlanType::ENTERPRISE) {
            return $this->cardRepository->countByUser($user, $query);
        }

        // Check if team member
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return $this->cardRepository->countByUser($user, $query);
        }

        // ADMINs can view all cards in the account
        if ($teamMember->getRole()->canViewAllCards()) {
            return $this->cardRepository->countByUser($account->getUser(), $query);
        }

        // MEMBERs: return count of assigned cards
        // For simplicity, we'll filter assigned cards from all cards
        $assignedCards = $this->getAssignedCardsForUser($user);
        
        if (!$query || trim($query) === '') {
            return count($assignedCards);
        }
        
        // Filter assigned cards by query
        $query = strtolower($query);
        $filteredCards = array_filter($assignedCards, function($card) use ($query) {
            $content = $card->getContent();
            $name = strtolower($content['name'] ?? '');
            $email = strtolower($content['email'] ?? '');
            $company = strtolower($content['company'] ?? '');
            $slug = strtolower($card->getSlug());
            
            return str_contains($name, $query) || 
                   str_contains($email, $query) || 
                   str_contains($company, $query) ||
                   str_contains($slug, $query);
        });
        
        return count($filteredCards);
    }

    /**
     * Assign card to team members
     *
     * @param TeamMember[] $teamMembers
     */
    public function assignCardToTeamMembers(Card $card, array $teamMembers, User $assignedBy): void
    {
        foreach ($teamMembers as $teamMember) {
            // Skip if already assigned
            if ($this->cardAssignmentRepository->isAssignedTo($card, $teamMember)) {
                continue;
            }

            $assignment = new CardAssignment();
            $assignment->setCard($card);
            $assignment->setTeamMember($teamMember);
            $assignment->setAssignedBy($assignedBy);

            $this->entityManager->persist($assignment);
        }

        $this->entityManager->flush();
    }

    /**
     * Unassign card from team member
     */
    public function unassignCardFromTeamMember(Card $card, TeamMember $teamMember): void
    {
        $assignments = $this->cardAssignmentRepository->findByCard($card);
        
        foreach ($assignments as $assignment) {
            if ($assignment->getTeamMember() === $teamMember) {
                $this->entityManager->remove($assignment);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Get card assignments for a card
     *
     * @return CardAssignment[]
     */
    public function getCardAssignments(Card $card): array
    {
        return $this->cardAssignmentRepository->findByCard($card);
    }

    /**
     * Regenerate public access key for a card
     * This will invalidate the previous key
     */
    public function regenerateCardAccessKey(Card $card): void
    {
        $newKey = $this->secureKeyGenerator->generateRandomKey();
        $card->regenerateAccessKey($newKey);
        $this->entityManager->flush();
    }

    /**
     * Validate if provided access key matches card's key
     * Uses constant-time comparison to prevent timing attacks
     */
    public function validateAccessKey(Card $card, ?string $providedKey): bool
    {
        $cardKey = $card->getPublicAccessKey();
        
        // If card has no key set, deny access (for security during migration)
        if ($cardKey === null) {
            return false;
        }
        
        // If no key provided, deny access
        if ($providedKey === null) {
            return false;
        }
        
        // Use constant-time comparison to prevent timing attacks
        return hash_equals($cardKey, $providedKey);
    }
}

