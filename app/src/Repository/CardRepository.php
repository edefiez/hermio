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

    /**
     * Search cards for a user with pagination
     * 
     * @param User $user
     * @param string|null $query Search query (searches in name, email, company)
     * @param int $limit Number of results to return
     * @param int $offset Offset for pagination
     * @return Card[]
     */
    public function searchByUser(User $user, ?string $query, int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($query && trim($query) !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.name\'))', ':query'),
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.email\'))', ':query'),
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.company\'))', ':query'),
                    $qb->expr()->like('LOWER(c.slug)', ':query')
                )
            )->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count cards for a user with optional search query
     * 
     * @param User $user
     * @param string|null $query Search query
     * @return int
     */
    public function countByUser(User $user, ?string $query = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active');

        if ($query && trim($query) !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.name\'))', ':query'),
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.email\'))', ':query'),
                    $qb->expr()->like('LOWER(JSON_EXTRACT(c.content, \'$.company\'))', ':query'),
                    $qb->expr()->like('LOWER(c.slug)', ':query')
                )
            )->setParameter('query', '%' . strtolower($query) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

