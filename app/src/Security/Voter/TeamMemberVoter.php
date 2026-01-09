<?php

namespace App\Security\Voter;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TeamMemberVoter extends Voter
{
    public const TEAM_ASSIGN_CARD = 'TEAM_ASSIGN_CARD';
    public const TEAM_MANAGE_MEMBERS = 'TEAM_MANAGE_MEMBERS';
    public const TEAM_VIEW_ALL = 'TEAM_VIEW_ALL';

    public function __construct(
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::TEAM_ASSIGN_CARD,
            self::TEAM_MANAGE_MEMBERS,
            self::TEAM_VIEW_ALL,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $account = $user->getAccount();
        if (!$account) {
            return false;
        }

        // Account owner has all permissions
        if ($account->getUser() === $user) {
            return true;
        }

        // Check team membership
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return false;
        }

        return match($attribute) {
            self::TEAM_ASSIGN_CARD => $teamMember->getRole() === TeamRole::ADMIN,
            self::TEAM_MANAGE_MEMBERS => $teamMember->getRole() === TeamRole::ADMIN,
            self::TEAM_VIEW_ALL => $teamMember->getRole() === TeamRole::ADMIN,
            default => false,
        };
    }
}

