<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\TeamMemberRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class InvitationRateLimiter
{
    private const RATE_LIMIT_KEY_PREFIX = 'invitation_rate_limit_';
    private const MAX_INVITATIONS_PER_HOUR = 10;
    private const MAX_INVITATIONS_PER_DAY = 50;
    private const CACHE_TTL_HOUR = 3600; // 1 hour
    private const CACHE_TTL_DAY = 86400; // 24 hours

    public function __construct(
        private CacheInterface $cache,
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    /**
     * Check if account can send more invitations
     *
     * @throws \RuntimeException if rate limit exceeded
     */
    public function checkRateLimit(Account $account): void
    {
        $accountId = $account->getId();
        $hourKey = self::RATE_LIMIT_KEY_PREFIX . 'hour_' . $accountId;
        $dayKey = self::RATE_LIMIT_KEY_PREFIX . 'day_' . $accountId;

        // Check hourly limit
        $hourCount = $this->cache->get($hourKey, function (ItemInterface $item) use ($accountId) {
            $item->expiresAfter(self::CACHE_TTL_HOUR);
            // Count invitations created in the last hour from database
            return $this->countRecentInvitations($accountId, 1);
        });

        if ($hourCount >= self::MAX_INVITATIONS_PER_HOUR) {
            throw new \RuntimeException('team.invite.rate_limit_hour');
        }

        // Check daily limit
        $dayCount = $this->cache->get($dayKey, function (ItemInterface $item) use ($accountId) {
            $item->expiresAfter(self::CACHE_TTL_DAY);
            // Count invitations created in the last 24 hours from database
            return $this->countRecentInvitations($accountId, 24);
        });

        if ($dayCount >= self::MAX_INVITATIONS_PER_DAY) {
            throw new \RuntimeException('team.invite.rate_limit_day');
        }
    }

    /**
     * Increment rate limit counters after sending invitation
     */
    public function incrementRateLimit(Account $account): void
    {
        $accountId = $account->getId();
        $hourKey = self::RATE_LIMIT_KEY_PREFIX . 'hour_' . $accountId;
        $dayKey = self::RATE_LIMIT_KEY_PREFIX . 'day_' . $accountId;

        // Increment hourly counter
        $this->cache->get($hourKey, function (ItemInterface $item) use ($accountId) {
            $item->expiresAfter(self::CACHE_TTL_HOUR);
            return $this->countRecentInvitations($accountId, 1);
        });
        $this->cache->set($hourKey, $this->countRecentInvitations($accountId, 1) + 1, self::CACHE_TTL_HOUR);

        // Increment daily counter
        $this->cache->get($dayKey, function (ItemInterface $item) use ($accountId) {
            $item->expiresAfter(self::CACHE_TTL_DAY);
            return $this->countRecentInvitations($accountId, 24);
        });
        $this->cache->set($dayKey, $this->countRecentInvitations($accountId, 24) + 1, self::CACHE_TTL_DAY);
    }

    /**
     * Count invitations created recently for an account
     */
    private function countRecentInvitations(int $accountId, int $hours): int
    {
        $since = new \DateTime("-{$hours} hours");
        
        return $this->teamMemberRepository->createQueryBuilder('tm')
            ->select('COUNT(tm.id)')
            ->where('tm.account = :accountId')
            ->andWhere('tm.createdAt >= :since')
            ->setParameter('accountId', $accountId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

