<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\AccountBranding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountBranding>
 */
class AccountBrandingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountBranding::class);
    }

    /**
     * Find branding for an account with optimized query.
     * Uses JOIN to avoid N+1 queries when fetching account with branding.
     */
    public function findOneByAccount(Account $account): ?AccountBranding
    {
        return $this->createQueryBuilder('ab')
            ->where('ab.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

