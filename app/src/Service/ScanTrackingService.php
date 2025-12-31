<?php

namespace App\Service;

use App\Entity\Card;
use App\Entity\CardScan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ScanTrackingService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Track a card scan
     */
    public function trackScan(Card $card, ?Request $request = null): void
    {
        $scan = new CardScan();
        $scan->setCard($card);

        if ($request) {
            // Anonymize IP address (store only first 3 octets for IPv4, first 4 groups for IPv6)
            $ipAddress = $request->getClientIp();
            if ($ipAddress) {
                $scan->setIpAddress($this->anonymizeIp($ipAddress));
            }

            // Store user agent
            $userAgent = $request->headers->get('User-Agent');
            if ($userAgent) {
                $scan->setUserAgent(substr($userAgent, 0, 255));
            }
        }

        $this->entityManager->persist($scan);
        $this->entityManager->flush();
    }

    /**
     * Anonymize IP address for privacy
     */
    private function anonymizeIp(string $ip): string
    {
        // Check if IPv6
        if (str_contains($ip, ':')) {
            // Keep first 4 groups of IPv6
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }

        // IPv4: Keep first 3 octets
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return implode('.', array_slice($parts, 0, 3)) . '.0';
        }

        return $ip;
    }
}
