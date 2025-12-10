<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamMember>
 */
class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    /**
     * Find all team members for an account
     *
     * @return TeamMember[]
     */
    public function findByAccount(Account $account): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->setParameter('account', $account)
            ->orderBy('tm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find team member by account and user (for accepted members only)
     */
    public function findByAccountAndUser(Account $account, User $user): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->andWhere('tm.user = :user')
            ->andWhere('tm.invitationStatus = :status')
            ->setParameter('account', $account)
            ->setParameter('user', $user)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * Find team member by invitation token
     */
    public function findByToken(string $token): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.invitationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find expired invitations
     *
     * @return TeamMember[]
     */
    public function findExpiredInvitations(): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.invitationStatus = :status')
            ->andWhere('tm.invitationExpiresAt < :now')
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all Enterprise accounts where user is a team member
     * Supports multiple Enterprise account membership (T060)
     *
     * @return Account[]
     */
    public function findEnterpriseAccountsForUser(User $user): array
    {
        return $this->createQueryBuilder('tm')
            ->select('IDENTITY(tm.account) as accountId')
            ->where('tm.user = :user')
            ->andWhere('tm.invitationStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all team memberships for a user (across all accounts)
     *
     * @return TeamMember[]
     */
    public function findAllMembershipsForUser(User $user): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.user = :user')
            ->andWhere('tm.invitationStatus = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getResult();
    }
}

