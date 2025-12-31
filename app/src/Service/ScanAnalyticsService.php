<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CardScanRepository;
use Doctrine\ORM\EntityManagerInterface;

class ScanAnalyticsService
{
    public function __construct(
        private CardScanRepository $cardScanRepository,
        private CardService $cardService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get analytics data for a user's cards
     * 
     * @return array{
     *   totalScans: int,
     *   scansPerDay: array<string, int>,
     *   topCards: array<array{card_id: int, slug: string, name: string, scan_count: int}>,
     *   cardCount: int
     * }
     */
    public function getAnalyticsForUser(User $user, int $days = 30): array
    {
        // Get all accessible cards for the user
        $cards = $this->cardService->getAccessibleCardsForUser($user);
        $cardIds = array_map(fn($card) => $card->getId(), $cards);

        if (empty($cardIds)) {
            return [
                'totalScans' => 0,
                'scansPerDay' => [],
                'topCards' => [],
                'cardCount' => 0,
            ];
        }

        // Calculate date range
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        // Get total scans
        $totalScans = $this->cardScanRepository->getTotalScansForCards($cardIds);

        // Get scans per day
        $scansPerDay = $this->cardScanRepository->getScansPerDayForCards($cardIds, $startDate, $endDate);

        // Fill in missing days with 0
        $scansPerDay = $this->fillMissingDays($scansPerDay, $startDate, $endDate);

        // Get top cards
        $topCards = $this->cardScanRepository->getTopCardsByScans($cardIds, 10);

        // Clean up the name field (remove quotes from JSON_EXTRACT)
        foreach ($topCards as &$card) {
            if (isset($card['name']) && is_string($card['name'])) {
                $card['name'] = trim($card['name'], '"');
            }
        }

        return [
            'totalScans' => $totalScans,
            'scansPerDay' => $scansPerDay,
            'topCards' => $topCards,
            'cardCount' => count($cards),
        ];
    }

    /**
     * Fill in missing days with 0 scans
     * 
     * @param array<string, int> $scansPerDay
     * @return array<string, int>
     */
    private function fillMissingDays(array $scansPerDay, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $result[$dateKey] = $scansPerDay[$dateKey] ?? 0;
            $current->modify('+1 day');
        }

        return $result;
    }

    /**
     * Get analytics for a specific card
     * 
     * @return array{
     *   totalScans: int,
     *   scansPerDay: array<string, int>
     * }
     */
    public function getAnalyticsForCard(int $cardId, int $days = 30): array
    {
        // Calculate date range
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$days} days");

        // Get total scans
        $totalScans = $this->cardScanRepository->getTotalScansForCard($cardId);

        // Get scans per day
        $scansPerDay = $this->cardScanRepository->getScansPerDay($cardId, $startDate, $endDate);

        // Fill in missing days with 0
        $scansPerDay = $this->fillMissingDays($scansPerDay, $startDate, $endDate);

        return [
            'totalScans' => $totalScans,
            'scansPerDay' => $scansPerDay,
        ];
    }
}
