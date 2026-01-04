<?php

namespace App\Controller;

use App\Repository\ProcessedWebhookEventRepository;
use App\Service\WebhookMonitoringService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/webhooks')]
#[IsGranted('ROLE_ADMIN')]
class AdminWebhookController extends AbstractController
{
    public function __construct(
        private ProcessedWebhookEventRepository $webhookEventRepository,
        private WebhookMonitoringService $monitoringService
    ) {
    }

    #[Route('', name: 'app_admin_webhook_index')]
    public function index(Request $request): Response
    {
        $statusFilter = $request->query->get('status'); // 'success', 'failed', or null
        $eventTypeFilter = $request->query->get('event_type');
        $limit = min((int) ($request->query->get('limit', 50)), 200); // Max 200

        $queryBuilder = $this->webhookEventRepository->createQueryBuilder('e')
            ->orderBy('e.processedAt', 'DESC')
            ->setMaxResults($limit);

        if ($statusFilter === 'success') {
            $queryBuilder->andWhere('e.success = :success')
                ->setParameter('success', true);
        } elseif ($statusFilter === 'failed') {
            $queryBuilder->andWhere('e.success = :success')
                ->setParameter('success', false);
        }

        if ($eventTypeFilter) {
            $queryBuilder->andWhere('e.eventType = :eventType')
                ->setParameter('eventType', $eventTypeFilter);
        }

        $events = $queryBuilder->getQuery()->getResult();

        // Get statistics
        $stats = $this->monitoringService->getStatistics();

        // Get recent failures (last 24 hours)
        $recentFailures = $this->monitoringService->getRecentFailures(24);

        // Get event type distribution
        $eventTypeDistribution = $this->monitoringService->getEventTypeDistribution();

        // Check for critical failures (monitoring/alerting)
        $hasCriticalFailures = $this->monitoringService->hasCriticalFailures(1, 5);
        $failureRateTrend = $this->monitoringService->getFailureRateTrend();

        return $this->render('admin/webhook/index.html.twig', [
            'events' => $events,
            'stats' => $stats,
            'recentFailures' => $recentFailures,
            'eventTypeDistribution' => $eventTypeDistribution,
            'hasCriticalFailures' => $hasCriticalFailures,
            'failureRateTrend' => $failureRateTrend,
            'currentStatusFilter' => $statusFilter,
            'currentEventTypeFilter' => $eventTypeFilter,
            'limit' => $limit,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_webhook_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $event = $this->webhookEventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Webhook event not found');
        }

        return $this->render('admin/webhook/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/failed', name: 'app_admin_webhook_failed')]
    public function failed(): Response
    {
        $failedEvents = $this->webhookEventRepository->createQueryBuilder('e')
            ->where('e.success = :success')
            ->setParameter('success', false)
            ->orderBy('e.processedAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return $this->render('admin/webhook/failed.html.twig', [
            'failedEvents' => $failedEvents,
        ]);
    }
}

