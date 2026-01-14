<?php

namespace App\Repository;

use App\Entity\CardView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardView>
 */
class CardViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardView::class);
    }

    /**
     * Get total view count for a card
     */
    public function getTotalViewsForCard(int $cardId): int
    {
        return (int) $this->createQueryBuilder('cv')
            ->select('COUNT(cv.id)')
            ->where('cv.card = :cardId')
            ->setParameter('cardId', $cardId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get views per day for a card within a date range
     * 
     * @return array<string, int> Array with date as key and count as value
     */
    public function getViewsPerDay(int $cardId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('cv')
            ->select('DATE(cv.viewedAt) as viewDate, COUNT(cv.id) as viewCount')
            ->where('cv.card = :cardId')
            ->andWhere('cv.viewedAt >= :startDate')
            ->andWhere('cv.viewedAt <= :endDate')
            ->setParameter('cardId', $cardId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('viewDate')
            ->orderBy('viewDate', 'ASC')
            ->getQuery()
            ->getResult();

        $viewsPerDay = [];
        foreach ($results as $result) {
            $viewsPerDay[$result['viewDate']] = (int) $result['viewCount'];
        }

        return $viewsPerDay;
    }

    /**
     * Get views per day for multiple cards within a date range
     * 
     * @param array<int> $cardIds
     * @return array<string, int> Array with date as key and count as value
     */
    public function getViewsPerDayForCards(array $cardIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        if (empty($cardIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('cv')
            ->select('DATE(cv.viewedAt) as viewDate, COUNT(cv.id) as viewCount')
            ->where('cv.card IN (:cardIds)')
            ->andWhere('cv.viewedAt >= :startDate')
            ->andWhere('cv.viewedAt <= :endDate')
            ->setParameter('cardIds', $cardIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('viewDate')
            ->orderBy('viewDate', 'ASC')
            ->getQuery()
            ->getResult();

        $viewsPerDay = [];
        foreach ($results as $result) {
            $viewsPerDay[$result['viewDate']] = (int) $result['viewCount'];
        }

        return $viewsPerDay;
    }

    /**
     * Get top N cards by view count for a user
     * 
     * @param array<int> $cardIds
     * @return array<array{card_id: int, slug: string, name: string, view_count: int}>
     */
    public function getTopCardsByViews(array $cardIds, int $limit = 10): array
    {
        if (empty($cardIds)) {
            return [];
        }

        return $this->createQueryBuilder('cv')
            ->select('c.id as card_id, c.slug, JSON_EXTRACT(c.content, \'$.name\') as name, COUNT(cv.id) as view_count')
            ->innerJoin('cv.card', 'c')
            ->where('c.id IN (:cardIds)')
            ->setParameter('cardIds', $cardIds)
            ->groupBy('c.id')
            ->orderBy('view_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total views for multiple cards
     * 
     * @param array<int> $cardIds
     */
    public function getTotalViewsForCards(array $cardIds): int
    {
        if (empty($cardIds)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('cv')
            ->select('COUNT(cv.id)')
            ->where('cv.card IN (:cardIds)')
            ->setParameter('cardIds', $cardIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get monthly views for multiple cards
     * 
     * @param array<int> $cardIds
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     */
    public function getMonthlyViewsForCards(array $cardIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        if (empty($cardIds)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('cv')
            ->select('COUNT(cv.id)')
            ->where('cv.card IN (:cardIds)')
            ->andWhere('cv.viewedAt >= :startDate')
            ->andWhere('cv.viewedAt <= :endDate')
            ->setParameter('cardIds', $cardIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

