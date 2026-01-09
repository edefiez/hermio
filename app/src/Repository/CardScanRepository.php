<?php

namespace App\Repository;

use App\Entity\CardScan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CardScan>
 */
class CardScanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardScan::class);
    }

    /**
     * Get total scan count for a card
     */
    public function getTotalScansForCard(int $cardId): int
    {
        return (int) $this->createQueryBuilder('cs')
            ->select('COUNT(cs.id)')
            ->where('cs.card = :cardId')
            ->setParameter('cardId', $cardId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get scans per day for a card within a date range
     * 
     * @return array<string, int> Array with date as key and count as value
     */
    public function getScansPerDay(int $cardId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('cs')
            ->select('DATE(cs.scannedAt) as scanDate, COUNT(cs.id) as scanCount')
            ->where('cs.card = :cardId')
            ->andWhere('cs.scannedAt >= :startDate')
            ->andWhere('cs.scannedAt <= :endDate')
            ->setParameter('cardId', $cardId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('scanDate')
            ->orderBy('scanDate', 'ASC')
            ->getQuery()
            ->getResult();

        $scansPerDay = [];
        foreach ($results as $result) {
            $scansPerDay[$result['scanDate']] = (int) $result['scanCount'];
        }

        return $scansPerDay;
    }

    /**
     * Get scans per day for multiple cards within a date range
     * 
     * @param array<int> $cardIds
     * @return array<string, int> Array with date as key and count as value
     */
    public function getScansPerDayForCards(array $cardIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        if (empty($cardIds)) {
            return [];
        }

        $results = $this->createQueryBuilder('cs')
            ->select('DATE(cs.scannedAt) as scanDate, COUNT(cs.id) as scanCount')
            ->where('cs.card IN (:cardIds)')
            ->andWhere('cs.scannedAt >= :startDate')
            ->andWhere('cs.scannedAt <= :endDate')
            ->setParameter('cardIds', $cardIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('scanDate')
            ->orderBy('scanDate', 'ASC')
            ->getQuery()
            ->getResult();

        $scansPerDay = [];
        foreach ($results as $result) {
            $scansPerDay[$result['scanDate']] = (int) $result['scanCount'];
        }

        return $scansPerDay;
    }

    /**
     * Get top N cards by scan count for a user
     * 
     * @param array<int> $cardIds
     * @return array<array{card_id: int, slug: string, name: string, scan_count: int}>
     */
    public function getTopCardsByScans(array $cardIds, int $limit = 10): array
    {
        if (empty($cardIds)) {
            return [];
        }

        return $this->createQueryBuilder('cs')
            ->select('c.id as card_id, c.slug, JSON_EXTRACT(c.content, \'$.name\') as name, COUNT(cs.id) as scan_count')
            ->innerJoin('cs.card', 'c')
            ->where('c.id IN (:cardIds)')
            ->setParameter('cardIds', $cardIds)
            ->groupBy('c.id')
            ->orderBy('scan_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total scans for multiple cards
     * 
     * @param array<int> $cardIds
     */
    public function getTotalScansForCards(array $cardIds): int
    {
        if (empty($cardIds)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('cs')
            ->select('COUNT(cs.id)')
            ->where('cs.card IN (:cardIds)')
            ->setParameter('cardIds', $cardIds)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get monthly scans for multiple cards
     * 
     * @param array<int> $cardIds
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     */
    public function getMonthlyScansForCards(array $cardIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        if (empty($cardIds)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('cs')
            ->select('COUNT(cs.id)')
            ->where('cs.card IN (:cardIds)')
            ->andWhere('cs.scannedAt >= :startDate')
            ->andWhere('cs.scannedAt <= :endDate')
            ->setParameter('cardIds', $cardIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
