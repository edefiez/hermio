<?php

namespace App\Tests\Unit\Service;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\User;
use App\Enum\PlanType;
use App\Exception\QuotaExceededException;
use App\Repository\CardRepository;
use App\Service\QuotaService;
use PHPUnit\Framework\TestCase;

class QuotaServiceTest extends TestCase
{
    private CardRepository $cardRepository;
    private QuotaService $quotaService;

    protected function setUp(): void
    {
        $this->cardRepository = $this->createMock(CardRepository::class);
        $this->quotaService = new QuotaService($this->cardRepository);
    }

    public function testCanCreateContentWithFreePlanAndNoCards(): void
    {
        $user = $this->createUserWithPlan(PlanType::FREE);
        $this->cardRepository->method('count')->willReturn(0);

        $this->assertTrue($this->quotaService->canCreateContent($user));
    }

    public function testCannotCreateContentWithFreePlanAndOneCard(): void
    {
        $user = $this->createUserWithPlan(PlanType::FREE);
        $this->cardRepository->method('count')->willReturn(1);

        $this->assertFalse($this->quotaService->canCreateContent($user));
    }

    public function testCanCreateContentWithProPlanAndLessThanTenCards(): void
    {
        $user = $this->createUserWithPlan(PlanType::PRO);
        $this->cardRepository->method('count')->willReturn(5);

        $this->assertTrue($this->quotaService->canCreateContent($user));
    }

    public function testCannotCreateContentWithProPlanAndTenCards(): void
    {
        $user = $this->createUserWithPlan(PlanType::PRO);
        $this->cardRepository->method('count')->willReturn(10);

        $this->assertFalse($this->quotaService->canCreateContent($user));
    }

    public function testCanCreateContentWithEnterprisePlanUnlimited(): void
    {
        $user = $this->createUserWithPlan(PlanType::ENTERPRISE);
        $this->cardRepository->method('count')->willReturn(100);

        $this->assertTrue($this->quotaService->canCreateContent($user));
    }

    public function testValidateQuotaThrowsExceptionWhenLimitExceeded(): void
    {
        $user = $this->createUserWithPlan(PlanType::FREE);
        $this->cardRepository->method('count')->willReturn(1);

        $this->expectException(QuotaExceededException::class);
        $this->quotaService->validateQuota($user);
    }

    public function testValidateQuotaDoesNotThrowWhenWithinLimit(): void
    {
        $user = $this->createUserWithPlan(PlanType::FREE);
        $this->cardRepository->method('count')->willReturn(0);

        $this->quotaService->validateQuota($user);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testGetCurrentUsageReturnsCorrectCount(): void
    {
        $user = $this->createUserWithPlan(PlanType::FREE);
        $this->cardRepository->method('count')->willReturn(5);

        $this->assertEquals(5, $this->quotaService->getCurrentUsage($user));
    }

    public function testCanCreateContentReturnsFalseWhenNoAccount(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getAccount')->willReturn(null);

        $this->assertFalse($this->quotaService->canCreateContent($user));
    }

    private function createUserWithPlan(PlanType $planType): User
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn($planType);

        $user = $this->createMock(User::class);
        $user->method('getAccount')->willReturn($account);

        return $user;
    }
}

