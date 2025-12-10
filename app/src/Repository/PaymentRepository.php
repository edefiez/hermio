<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Find payments for a user ordered by paid date descending
     *
     * @return Payment[]
     */
    public function findByUserOrderedByDate(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

