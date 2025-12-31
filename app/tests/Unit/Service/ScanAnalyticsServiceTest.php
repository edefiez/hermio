<?php

namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\CardScanRepository;
use App\Service\CardService;
use App\Service\ScanAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ScanAnalyticsServiceTest extends TestCase
{
    private CardScanRepository $cardScanRepository;
    private CardService $cardService;
    private EntityManagerInterface $entityManager;
    private ScanAnalyticsService $scanAnalyticsService;

    protected function setUp(): void
    {
        $this->cardScanRepository = $this->createMock(CardScanRepository::class);
        $this->cardService = $this->createMock(CardService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->scanAnalyticsService = new ScanAnalyticsService(
            $this->cardScanRepository,
            $this->cardService,
            $this->entityManager
        );
    }

    public function testGetAnalyticsForUserWithNoCards(): void
    {
        $user = $this->createMock(User::class);
        $this->cardService->method('getAccessibleCardsForUser')->willReturn([]);

        $analytics = $this->scanAnalyticsService->getAnalyticsForUser($user, 30);

        $this->assertIsArray($analytics);
        $this->assertEquals(0, $analytics['totalScans']);
        $this->assertEmpty($analytics['scansPerDay']);
        $this->assertEmpty($analytics['topCards']);
        $this->assertEquals(0, $analytics['cardCount']);
    }

    public function testGetAnalyticsForUserWithCards(): void
    {
        $user = $this->createMock(User::class);
        
        $card1 = $this->createMock(Card::class);
        $card1->method('getId')->willReturn(1);
        
        $card2 = $this->createMock(Card::class);
        $card2->method('getId')->willReturn(2);
        
        $cards = [$card1, $card2];
        
        $this->cardService->method('getAccessibleCardsForUser')->willReturn($cards);
        
        $this->cardScanRepository->method('getTotalScansForCards')
            ->with([1, 2])
            ->willReturn(150);

        $scansPerDay = [
            '2024-01-01' => 10,
            '2024-01-02' => 15,
            '2024-01-03' => 8,
        ];
        
        $this->cardScanRepository->method('getScansPerDayForCards')
            ->willReturn($scansPerDay);

        $topCards = [
            ['card_id' => 1, 'slug' => 'card1', 'name' => '"John Doe"', 'scan_count' => 100],
            ['card_id' => 2, 'slug' => 'card2', 'name' => '"Jane Smith"', 'scan_count' => 50],
        ];
        
        $this->cardScanRepository->method('getTopCardsByScans')
            ->with([1, 2], 10)
            ->willReturn($topCards);

        $analytics = $this->scanAnalyticsService->getAnalyticsForUser($user, 30);

        $this->assertIsArray($analytics);
        $this->assertEquals(150, $analytics['totalScans']);
        $this->assertCount(30, $analytics['scansPerDay']); // Should fill 30 days
        $this->assertCount(2, $analytics['topCards']);
        $this->assertEquals('John Doe', $analytics['topCards'][0]['name']); // Quotes removed
        $this->assertEquals('Jane Smith', $analytics['topCards'][1]['name']); // Quotes removed
        $this->assertEquals(2, $analytics['cardCount']);
    }

    public function testGetAnalyticsForCard(): void
    {
        $cardId = 1;
        
        $this->cardScanRepository->method('getTotalScansForCard')
            ->with($cardId)
            ->willReturn(50);

        $scansPerDay = [
            '2024-01-01' => 5,
            '2024-01-02' => 10,
        ];
        
        $this->cardScanRepository->method('getScansPerDay')
            ->willReturn($scansPerDay);

        $analytics = $this->scanAnalyticsService->getAnalyticsForCard($cardId, 30);

        $this->assertIsArray($analytics);
        $this->assertEquals(50, $analytics['totalScans']);
        $this->assertCount(30, $analytics['scansPerDay']); // Should fill 30 days
    }

    public function testFillMissingDaysCreates30DaysOfData(): void
    {
        $user = $this->createMock(User::class);
        
        $card = $this->createMock(Card::class);
        $card->method('getId')->willReturn(1);
        
        $this->cardService->method('getAccessibleCardsForUser')->willReturn([$card]);
        
        $this->cardScanRepository->method('getTotalScansForCards')->willReturn(5);
        
        // Only return data for 2 days
        $partialScansPerDay = [
            (new \DateTime())->format('Y-m-d') => 3,
            (new \DateTime('-1 day'))->format('Y-m-d') => 2,
        ];
        
        $this->cardScanRepository->method('getScansPerDayForCards')
            ->willReturn($partialScansPerDay);
        
        $this->cardScanRepository->method('getTopCardsByScans')->willReturn([]);

        $analytics = $this->scanAnalyticsService->getAnalyticsForUser($user, 30);

        // Should have 30 days of data
        $this->assertCount(30, $analytics['scansPerDay']);
        
        // Days with data should have counts
        $today = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');
        $this->assertEquals(3, $analytics['scansPerDay'][$today]);
        $this->assertEquals(2, $analytics['scansPerDay'][$yesterday]);
        
        // Days without data should have 0
        $twoDaysAgo = (new \DateTime('-2 days'))->format('Y-m-d');
        $this->assertEquals(0, $analytics['scansPerDay'][$twoDaysAgo]);
    }
}
