<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\TeamRole;
use App\Repository\CardAssignmentRepository;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamService
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository,
        private CardAssignmentRepository $cardAssignmentRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function canManageTeam(Account $account, User $user): bool
    {
        if ($account->getUser() === $user) {
            return true; // Account owner
        }

        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        return $teamMember && $teamMember->getRole() === TeamRole::ADMIN;
    }

    /**
     * Get all team members for an account
     * Optimized query with eager loading to avoid N+1
     *
     * @return TeamMember[]
     */
    public function getTeamMembers(Account $account): array
    {
        return $this->teamMemberRepository->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->setParameter('account', $account)
            ->orderBy('tm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function changeRole(TeamMember $teamMember, TeamRole $newRole, User $requester): void
    {
        $account = $teamMember->getAccount();

        // Only account owner can change roles
        if ($account->getUser() !== $requester) {
            throw new \InvalidArgumentException('team.role.change.denied');
        }

        // Cannot change account owner's role
        if ($teamMember->getUser() === $account->getUser()) {
            throw new \InvalidArgumentException('Cannot change account owner role');
        }

        $teamMember->setRole($newRole);
        $this->entityManager->flush();
    }

    public function removeTeamMember(TeamMember $teamMember, User $requester): void
    {
        $account = $teamMember->getAccount();

        // Only account owner can remove team members
        if ($account->getUser() !== $requester) {
            throw new \InvalidArgumentException('team.remove.denied');
        }

        // Cannot remove account owner
        if ($teamMember->getUser() === $account->getUser()) {
            throw new \InvalidArgumentException('Cannot remove account owner');
        }

        $this->entityManager->remove($teamMember);
        $this->entityManager->flush();
    }

    public function revokeTeamAccess(Account $account): void
    {
        $teamMembers = $this->teamMemberRepository->findByAccount($account);

        foreach ($teamMembers as $member) {
            $member->setInvitationStatus('revoked');
        }

        $this->entityManager->flush();
    }

    /**
     * Get team overview with card assignment counts per member
     * Optimized query to avoid N+1 by using a single query with COUNT
     *
     * @return array{members: TeamMember[], assignmentCounts: array<int, int>}
     */
    public function getTeamOverview(Account $account): array
    {
        $teamMembers = $this->teamMemberRepository->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->setParameter('account', $account)
            ->orderBy('tm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $assignmentCounts = [];
        
        if (!empty($teamMembers)) {
            $memberIds = array_map(fn($m) => $m->getId(), $teamMembers);
            
            // Single query to get all assignment counts
            $counts = $this->cardAssignmentRepository->createQueryBuilder('ca')
                ->select('IDENTITY(ca.teamMember) as memberId, COUNT(ca.id) as count')
                ->where('ca.teamMember IN (:memberIds)')
                ->setParameter('memberIds', $memberIds)
                ->groupBy('ca.teamMember')
                ->getQuery()
                ->getResult();

            // Initialize all counts to 0
            foreach ($teamMembers as $member) {
                $assignmentCounts[$member->getId()] = 0;
            }

            // Set actual counts
            foreach ($counts as $count) {
                $assignmentCounts[$count['memberId']] = (int)$count['count'];
            }
        }

        return [
            'members' => $teamMembers,
            'assignmentCounts' => $assignmentCounts,
        ];
    }

    /**
     * Get team member details with assigned cards
     *
     * @return array{teamMember: TeamMember, assignedCards: Card[]}
     */
    public function getTeamMemberDetails(TeamMember $teamMember): array
    {
        $assignments = $this->cardAssignmentRepository->findByTeamMember($teamMember);
        $assignedCards = array_map(fn($assignment) => $assignment->getCard(), $assignments);

        return [
            'teamMember' => $teamMember,
            'assignedCards' => $assignedCards,
        ];
    }
}

