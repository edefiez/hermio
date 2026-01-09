<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\TeamMember;
use App\Exception\QuotaExceededException;
use App\Form\CardAssignmentFormType;
use App\Form\CardFormType;
use App\Repository\CardAssignmentRepository;
use App\Repository\CardRepository;
use App\Repository\CardScanRepository;
use App\Repository\CardViewRepository;
use App\Repository\TeamMemberRepository;
use App\Service\CardService;
use App\Service\QrCodeService;
use App\Service\QuotaService;
use App\Service\TeamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cards')]
#[IsGranted('ROLE_USER')]
class CardController extends AbstractController
{
    public function __construct(
        private CardService $cardService,
        private QuotaService $quotaService,
        private QrCodeService $qrCodeService,
        private CardRepository $cardRepository,
        private CardAssignmentRepository $cardAssignmentRepository,
        private CardScanRepository $cardScanRepository,
        private CardViewRepository $cardViewRepository,
        private TeamService $teamService,
        private TeamMemberRepository $teamMemberRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_card_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();

        $quotaLimit = $account?->getPlanType()?->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $canCreateMore = $quotaLimit === null || $currentUsage < $quotaLimit;

        // Get first 10 cards for initial load
        $cards = $this->cardService->searchAccessibleCardsForUser($user, null, 10, 0);
        $totalCards = $this->cardService->countAccessibleCardsForUser($user);

        // Get all accessible card IDs for statistics
        $allCardIds = [];
        if ($totalCards > 0) {
            $allAccessibleCards = $this->cardService->searchAccessibleCardsForUser($user, null, 1000, 0);
            $allCardIds = array_map(fn($c) => $c->getId(), $allAccessibleCards);
        }

        // Calculate statistics
        $totalScans = 0;
        $monthlyViews = 0;
        $monthlyScans = 0;
        
        if (!empty($allCardIds)) {
            // Total scans for all cards
            $totalScans = $this->cardScanRepository->getTotalScansForCards($allCardIds);
            
            // Monthly views and scans (current month)
            $startOfMonth = new \DateTime('first day of this month');
            $startOfMonth->setTime(0, 0, 0);
            $endOfMonth = new \DateTime('last day of this month');
            $endOfMonth->setTime(23, 59, 59);
            
            // Monthly views
            $monthlyViews = $this->cardViewRepository->getMonthlyViewsForCards($allCardIds, $startOfMonth, $endOfMonth);
            
            // Monthly scans
            $monthlyScans = $this->cardScanRepository->getMonthlyScansForCards($allCardIds, $startOfMonth, $endOfMonth);
        }

        // Get assignments for each card (for display) - optimized to avoid N+1
        $cardAssignments = [];
        if (!empty($cards)) {
            $cardIds = array_map(fn($c) => $c->getId(), $cards);

            // Single query to get all assignments for all cards
            $allAssignments = $this->cardAssignmentRepository->createQueryBuilder('ca')
                ->where('ca.card IN (:cardIds)')
                ->setParameter('cardIds', $cardIds)
                ->getQuery()
                ->getResult();

            // Group assignments by card ID
            foreach ($allAssignments as $assignment) {
                $cardId = $assignment->getCard()->getId();
                if (!isset($cardAssignments[$cardId])) {
                    $cardAssignments[$cardId] = [];
                }
                $cardAssignments[$cardId][] = $assignment;
            }
        }

        $canManageAssignments = false;
        if ($account && $account->getPlanType()->value === 'enterprise') {
            $canManageAssignments = $this->teamService->canManageTeam($account, $user);
        }

        return $this->render('card/index.html.twig', [
            'cards' => $cards,
            'cardAssignments' => $cardAssignments,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'canCreateMore' => $canCreateMore,
            'canManageAssignments' => $canManageAssignments,
            'isEnterprise' => $account && $account->getPlanType()->value === 'enterprise',
            'totalCards' => $totalCards,
            'totalScans' => $totalScans,
            'monthlyViews' => $monthlyViews,
            'monthlyScans' => $monthlyScans,
        ]);
    }

    #[Route('/api/search', name: 'app_card_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();
        
        $query = $request->query->get('q');
        $offset = (int) $request->query->get('offset', 0);
        $limit = 10;

        $cards = $this->cardService->searchAccessibleCardsForUser($user, $query, $limit, $offset);
        $totalCards = $this->cardService->countAccessibleCardsForUser($user, $query);

        // Get assignments for each card
        $cardAssignments = [];
        if (!empty($cards)) {
            $cardIds = array_map(fn($c) => $c->getId(), $cards);

            $allAssignments = $this->cardAssignmentRepository->createQueryBuilder('ca')
                ->where('ca.card IN (:cardIds)')
                ->setParameter('cardIds', $cardIds)
                ->getQuery()
                ->getResult();

            foreach ($allAssignments as $assignment) {
                $cardId = $assignment->getCard()->getId();
                if (!isset($cardAssignments[$cardId])) {
                    $cardAssignments[$cardId] = [];
                }
                $cardAssignments[$cardId][] = $assignment;
            }
        }

        $canManageAssignments = false;
        if ($account && $account->getPlanType()->value === 'enterprise') {
            $canManageAssignments = $this->teamService->canManageTeam($account, $user);
        }

        $hasMore = ($offset + count($cards)) < $totalCards;
        
        return $this->render('card/_card_list.html.twig', [
            'cards' => $cards,
            'cardAssignments' => $cardAssignments,
            'canManageAssignments' => $canManageAssignments,
            'isEnterprise' => $account && $account->getPlanType()->value === 'enterprise',
            'hasMore' => $hasMore,
            'totalCards' => $totalCards,
        ]);
    }

    #[Route('/create', name: 'app_card_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();

        $quotaLimit = $account?->getPlanType()?->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $canCreateMore = $quotaLimit === null || $currentUsage < $quotaLimit;

        if (!$canCreateMore) {
            $this->addFlash('error', 'card.quota.exceeded');
            return $this->redirectToRoute('app_card_index');
        }

        $card = new Card();
        $form = $this->createForm(CardFormType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Transform form data to card content
                $content = $this->buildCardContentFromForm($form);
                $card->setContent($content);

                $card = $this->cardService->createCard($card, $user);

                $this->addFlash('success', 'card.created.success', [
                    'slug' => $card->getSlug(),
                ]);

                return $this->redirectToRoute('app_card_index');
            } catch (QuotaExceededException $e) {
                $this->addFlash('error', 'card.quota.exceeded');
            }
        }

        return $this->render('card/create.html.twig', [
            'form' => $form,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // Check access using CardService
        if (!$this->cardService->canAccessCard($card, $user)) {
            throw $this->createAccessDeniedException('card.access.denied');
        }

        // Update lastActivityAt for team members
        $account = $user->getAccount();
        if ($account && $account->getPlanType()->value === 'enterprise') {
            $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
            if ($teamMember && $teamMember->getInvitationStatus() === 'accepted') {
                $teamMember->setLastActivityAt(new \DateTime());
                $this->entityManager->flush();
            }
        }

        // Pre-populate form with card content
        $content = $card->getContent();
        $form = $this->createForm(CardFormType::class, $card);
        $form->get('name')->setData($content['name'] ?? '');
        $form->get('email')->setData($content['email'] ?? '');
        $form->get('phone')->setData($content['phone'] ?? '');
        $form->get('company')->setData($content['company'] ?? '');
        $form->get('title')->setData($content['title'] ?? '');
        $form->get('bio')->setData($content['bio'] ?? '');
        $form->get('website')->setData($content['website'] ?? '');
        // All social fields in social object
        $form->get('linkedin')->setData($content['social']['linkedin'] ?? '');
        $form->get('instagram')->setData($content['social']['instagram'] ?? '');
        $form->get('tiktok')->setData($content['social']['tiktok'] ?? '');
        $form->get('facebook')->setData($content['social']['facebook'] ?? '');
        $form->get('x')->setData($content['social']['x'] ?? '');
        $form->get('bluebirds')->setData($content['social']['bluebirds'] ?? '');
        $form->get('snapchat')->setData($content['social']['snapchat'] ?? '');
        $form->get('planity')->setData($content['social']['planity'] ?? '');
        $form->get('other')->setData($content['social']['other'] ?? '');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Transform form data to card content
            $content = $this->buildCardContentFromForm($form);
            $card->setContent($content);

            $this->cardService->updateCard($card);

            $this->addFlash('success', 'card.updated.success');

            return $this->redirectToRoute('app_card_index');
        }

        $account = $user->getAccount();
        $canManageAssignments = false;
        $assignmentForm = null;
        $currentAssignments = [];

        if ($account && $account->getPlanType()->value === 'enterprise') {
            $canManageAssignments = $this->teamService->canManageTeam($account, $user);

            if ($canManageAssignments) {
                $currentAssignments = $this->cardAssignmentRepository->findByCard($card);

                $assignmentForm = $this->createForm(CardAssignmentFormType::class, null, [
                    'account' => $account,
                ]);

                // Pre-select current assignments
                $currentTeamMemberIds = array_map(
                    fn($assignment) => $assignment->getTeamMember()->getId(),
                    $currentAssignments
                );
                $assignmentForm->get('teamMembers')->setData(
                    $this->teamMemberRepository->findBy(['id' => $currentTeamMemberIds])
                );

                $assignmentForm->handleRequest($request);

                if ($assignmentForm->isSubmitted() && $assignmentForm->isValid()) {
                    $selectedTeamMembers = $assignmentForm->get('teamMembers')->getData();

                    // Remove all existing assignments
                    foreach ($currentAssignments as $assignment) {
                        $this->cardService->unassignCardFromTeamMember($card, $assignment->getTeamMember());
                    }

                    // Add new assignments
                    if (!empty($selectedTeamMembers)) {
                        $this->cardService->assignCardToTeamMembers($card, $selectedTeamMembers, $user);
                    }

                    $this->addFlash('success', 'card.assignments.success');
                    return $this->redirectToRoute('app_card_edit', ['id' => $card->getId()]);
                }
            }
        }

        return $this->render('card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
            'assignmentForm' => $assignmentForm?->createView(),
            'currentAssignments' => $currentAssignments,
            'canManageAssignments' => $canManageAssignments,
            'isEnterprise' => $account && $account->getPlanType()->value === 'enterprise',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // Check access using CardService
        if (!$this->cardService->canAccessCard($card, $user)) {
            throw $this->createAccessDeniedException('card.access.denied');
        }

        // Only card owner can delete
        if ($card->getUser() !== $user) {
            throw $this->createAccessDeniedException('card.delete.denied');
        }

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $request->request->get('_token'))) {
            $this->cardService->deleteCard($card);
            $this->addFlash('success', 'card.deleted.success');
        }

        return $this->redirectToRoute('app_card_index');
    }

    #[Route('/{id}/assign', name: 'app_card_assign', methods: ['POST'])]
    public function assign(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);
        $account = $user->getAccount();

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        if (!$account || $account->getPlanType()->value !== 'enterprise') {
            throw $this->createAccessDeniedException('card.assignments.access_denied');
        }

        if (!$this->teamService->canManageTeam($account, $user)) {
            throw $this->createAccessDeniedException('card.assignments.access_denied');
        }

        $assignmentForm = $this->createForm(CardAssignmentFormType::class, null, [
            'account' => $account,
        ]);
        $assignmentForm->handleRequest($request);

        if ($assignmentForm->isSubmitted() && $assignmentForm->isValid()) {
            $selectedTeamMembers = $assignmentForm->get('teamMembers')->getData();

            if (!empty($selectedTeamMembers)) {
                $this->cardService->assignCardToTeamMembers($card, $selectedTeamMembers, $user);
                $this->addFlash('success', 'card.assignments.success');
            }
        }

        return $this->redirectToRoute('app_card_edit', ['id' => $card->getId()]);
    }

    #[Route('/{id}/unassign/{teamMemberId}', name: 'app_card_unassign', methods: ['POST'])]
    public function unassign(int $id, int $teamMemberId, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);
        $account = $user->getAccount();

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        if (!$account || $account->getPlanType()->value !== 'enterprise') {
            throw $this->createAccessDeniedException('card.assignments.access_denied');
        }

        if (!$this->teamService->canManageTeam($account, $user)) {
            throw $this->createAccessDeniedException('card.assignments.access_denied');
        }

        $teamMember = $this->teamMemberRepository->find($teamMemberId);
        if (!$teamMember || $teamMember->getAccount() !== $account) {
            throw $this->createNotFoundException('Team member not found');
        }

        if ($this->isCsrfTokenValid('unassign' . $card->getId() . $teamMemberId, $request->request->get('_token'))) {
            $this->cardService->unassignCardFromTeamMember($card, $teamMember);
            $this->addFlash('success', 'card.assignments.removed');
        }

        return $this->redirectToRoute('app_card_edit', ['id' => $card->getId()]);
    }

    #[Route('/{id}/qr-code', name: 'app_card_qr_code', methods: ['GET'])]
    public function qrCode(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        if (!$this->cardService->canAccessCard($card, $user)) {
            throw $this->createAccessDeniedException('card.access.denied');
        }

        // Generate URL with qr=1 parameter to track scans
        $baseUrl = $card->getPublicUrl();
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $publicUrl = $request->getSchemeAndHttpHost() . $baseUrl . $separator . 'qr=1';
        $qrCodeData = $this->qrCodeService->generateQrCodeBase64($publicUrl);

        return $this->render('card/qr_code.html.twig', [
            'card' => $card,
            'qrCodeData' => $qrCodeData,
            'publicUrl' => $publicUrl,
        ]);
    }

    #[Route('/{id}/regenerate-key', name: 'app_card_regenerate_key', methods: ['POST'])]
    public function regenerateKey(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $card = $this->cardRepository->find($id);

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // Check access using CardService
        if (!$this->cardService->canAccessCard($card, $user)) {
            throw $this->createAccessDeniedException('card.access.denied');
        }

        // Only card owner can regenerate key
        if ($card->getUser() !== $user) {
            throw $this->createAccessDeniedException('card.regenerate_key.denied');
        }

        if ($this->isCsrfTokenValid('regenerate' . $card->getId(), $request->request->get('_token'))) {
            $this->cardService->regenerateCardAccessKey($card);
            $this->addFlash('success', 'card.security.regenerate_key_success');
        } else {
            $this->addFlash('error', 'Invalid CSRF token');
        }

        return $this->redirectToRoute('app_card_edit', ['id' => $card->getId()]);
    }

    /**
     * Transform form data to card content array
     */
    private function buildCardContentFromForm($form): array
    {
        return [
            'name' => $form->get('name')->getData() ?? '',
            'email' => $form->get('email')->getData() ?? '',
            'phone' => $form->get('phone')->getData() ?? '',
            'company' => $form->get('company')->getData() ?? '',
            'title' => $form->get('title')->getData() ?? '',
            'bio' => $form->get('bio')->getData() ?? '',
            'website' => $form->get('website')->getData() ?? '',
            // All social fields in social object
            'social' => [
                'linkedin' => $form->get('linkedin')->getData() ?? '',
                'instagram' => $form->get('instagram')->getData() ?? '',
                'tiktok' => $form->get('tiktok')->getData() ?? '',
                'facebook' => $form->get('facebook')->getData() ?? '',
                'x' => $form->get('x')->getData() ?? '',
                'bluebirds' => $form->get('bluebirds')->getData() ?? '',
                'snapchat' => $form->get('snapchat')->getData() ?? '',
                'planity' => $form->get('planity')->getData() ?? '',
                'other' => $form->get('other')->getData() ?? '',
            ],
        ];
    }
}

