<?php

namespace App\Tests\Unit\Service;

use App\Entity\Account;
use App\Repository\TeamMemberRepository;
use App\Service\InvitationRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class InvitationRateLimiterTest extends TestCase
{
    private CacheInterface $cache;
    private TeamMemberRepository $teamMemberRepository;
    private InvitationRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        
        $this->rateLimiter = new InvitationRateLimiter(
            $this->cache,
            $this->teamMemberRepository
        );
    }

    public function testCheckRateLimitThrowsExceptionWhenHourlyLimitExceeded(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')->willReturn(1);

        $this->cache
            ->method('get')
            ->willReturn(10); // Max is 10 per hour

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('team.invite.rate_limit_hour');
        
        $this->rateLimiter->checkRateLimit($account);
    }

    public function testCheckRateLimitThrowsExceptionWhenDailyLimitExceeded(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')->willReturn(1);

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                if (str_contains($key, 'hour')) {
                    return 5; // Under hourly limit
                }
                return 50; // At daily limit
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('team.invite.rate_limit_day');
        
        $this->rateLimiter->checkRateLimit($account);
    }

    public function testCheckRateLimitDoesNotThrowWhenWithinLimits(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')->willReturn(1);

        $this->cache
            ->method('get')
            ->willReturn(5); // Under both limits

        $this->rateLimiter->checkRateLimit($account);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}

