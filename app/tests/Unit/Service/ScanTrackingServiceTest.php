<?php

namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Entity\CardScan;
use App\Service\ScanTrackingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

class ScanTrackingServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ScanTrackingService $scanTrackingService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->scanTrackingService = new ScanTrackingService($this->entityManager);
    }

    public function testTrackScanWithoutRequest(): void
    {
        $card = $this->createMock(Card::class);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scan) use ($card) {
                return $scan instanceof CardScan 
                    && $scan->getCard() === $card
                    && $scan->getIpAddress() === null
                    && $scan->getUserAgent() === null;
            }));
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->scanTrackingService->trackScan($card, null);
    }

    public function testTrackScanWithRequest(): void
    {
        $card = $this->createMock(Card::class);
        
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('192.168.1.100');
        
        $headers = $this->createMock(HeaderBag::class);
        $headers->method('get')->with('User-Agent')->willReturn('Mozilla/5.0 Test Browser');
        $request->headers = $headers;
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scan) use ($card) {
                return $scan instanceof CardScan 
                    && $scan->getCard() === $card
                    && $scan->getIpAddress() === '192.168.1.0' // Anonymized
                    && $scan->getUserAgent() === 'Mozilla/5.0 Test Browser';
            }));
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->scanTrackingService->trackScan($card, $request);
    }

    public function testIpAnonymizationIPv4(): void
    {
        $card = $this->createMock(Card::class);
        
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('203.0.113.45');
        
        $headers = $this->createMock(HeaderBag::class);
        $headers->method('get')->willReturn(null);
        $request->headers = $headers;
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scan) {
                // Check that last octet is replaced with .0
                return $scan->getIpAddress() === '203.0.113.0';
            }));
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->scanTrackingService->trackScan($card, $request);
    }

    public function testIpAnonymizationIPv6(): void
    {
        $card = $this->createMock(Card::class);
        
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        
        $headers = $this->createMock(HeaderBag::class);
        $headers->method('get')->willReturn(null);
        $request->headers = $headers;
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scan) {
                // Check that only first 4 groups are kept
                return $scan->getIpAddress() === '2001:0db8:85a3:0000::';
            }));
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->scanTrackingService->trackScan($card, $request);
    }

    public function testUserAgentTruncation(): void
    {
        $card = $this->createMock(Card::class);
        
        $longUserAgent = str_repeat('A', 300); // 300 characters
        
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('192.168.1.1');
        
        $headers = $this->createMock(HeaderBag::class);
        $headers->method('get')->with('User-Agent')->willReturn($longUserAgent);
        $request->headers = $headers;
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scan) {
                // Check that user agent is truncated to 255 characters
                return strlen($scan->getUserAgent()) === 255;
            }));
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->scanTrackingService->trackScan($card, $request);
    }
}
