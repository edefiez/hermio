<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findOneBySlug(string $slug): ?Card
    {
        return $this->findOneBy(['slug' => $slug, 'status' => 'active']);
    }

    /**
     * @return Card[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'status' => 'active'], ['createdAt' => 'DESC']);
    }

    public function slugExists(string $slug): bool
    {
        return $this->findOneBy(['slug' => $slug]) !== null;
    }
}

