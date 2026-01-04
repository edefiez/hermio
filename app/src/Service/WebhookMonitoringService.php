<?php

namespace App\Service;

use App\Repository\ProcessedWebhookEventRepository;
use Psr\Log\LoggerInterface;

class WebhookMonitoringService
{
    public function __construct(
        private ProcessedWebhookEventRepository $webhookEventRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get webhook processing statistics
     */
    public function getStatistics(): array
    {
        $total = $this->webhookEventRepository->count([]);
        $successful = $this->webhookEventRepository->count(['success' => true]);
        $failed = $this->webhookEventRepository->count(['success' => false]);

        // Last 24 hours
        $last24Hours = new \DateTime('-24 hours');
        $recentTotal = $this->webhookEventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.processedAt >= :date')
            ->setParameter('date', $last24Hours)
            ->getQuery()
            ->getSingleScalarResult();

        $recentFailed = $this->webhookEventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.processedAt >= :date')
            ->andWhere('e.success = :success')
            ->setParameter('date', $last24Hours)
            ->setParameter('success', false)
            ->getQuery()
            ->getSingleScalarResult();

        $successRate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;
        $recentSuccessRate = $recentTotal > 0 ? round((($recentTotal - $recentFailed) / $recentTotal) * 100, 2) : 0;

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $successRate,
            'recent_total' => (int) $recentTotal,
            'recent_failed' => (int) $recentFailed,
            'recent_success_rate' => $recentSuccessRate,
        ];
    }

    /**
     * Get recent failures within specified hours
     */
    public function getRecentFailures(int $hours = 24): array
    {
        $since = new \DateTime("-{$hours} hours");

        return $this->webhookEventRepository->createQueryBuilder('e')
            ->where('e.success = :success')
            ->andWhere('e.processedAt >= :date')
            ->setParameter('success', false)
            ->setParameter('date', $since)
            ->orderBy('e.processedAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get event type distribution
     */
    public function getEventTypeDistribution(): array
    {
        $results = $this->webhookEventRepository->createQueryBuilder('e')
            ->select('e.eventType, COUNT(e.id) as count')
            ->groupBy('e.eventType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $distribution = [];
        foreach ($results as $result) {
            $distribution[$result['eventType']] = (int) $result['count'];
        }

        return $distribution;
    }

    /**
     * Check if there are critical failures that need attention
     */
    public function hasCriticalFailures(int $hours = 1, int $threshold = 5): bool
    {
        $since = new \DateTime("-{$hours} hours");

        $failureCount = $this->webhookEventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.success = :success')
            ->andWhere('e.processedAt >= :date')
            ->setParameter('success', false)
            ->setParameter('date', $since)
            ->getQuery()
            ->getSingleScalarResult();

        if ($failureCount >= $threshold) {
            $this->logger->warning('Critical webhook failures detected', [
                'failures' => $failureCount,
                'hours' => $hours,
                'threshold' => $threshold,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get failure rate trend (last 7 days)
     */
    public function getFailureRateTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateStart = new \DateTime("-{$i} days");
            $dateStart->setTime(0, 0, 0);
            $dateEnd = clone $dateStart;
            $dateEnd->setTime(23, 59, 59);

            $total = $this->webhookEventRepository->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.processedAt >= :start')
                ->andWhere('e.processedAt <= :end')
                ->setParameter('start', $dateStart)
                ->setParameter('end', $dateEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $failed = $this->webhookEventRepository->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.processedAt >= :start')
                ->andWhere('e.processedAt <= :end')
                ->andWhere('e.success = :success')
                ->setParameter('start', $dateStart)
                ->setParameter('end', $dateEnd)
                ->setParameter('success', false)
                ->getQuery()
                ->getSingleScalarResult();

            $trend[] = [
                'date' => $dateStart->format('Y-m-d'),
                'total' => (int) $total,
                'failed' => (int) $failed,
                'success_rate' => $total > 0 ? round((($total - $failed) / $total) * 100, 2) : 100,
            ];
        }

        return $trend;
    }
}

