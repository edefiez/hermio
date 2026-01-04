<?php

namespace App\Tests\Unit\Service;

use App\Entity\Account;
use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\AccountRepository;
use App\Service\AccountService;
use App\Service\BrandingService;
use App\Service\QuotaService;
use App\Service\TeamService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AccountServiceTest extends TestCase
{
    private AccountRepository $accountRepository;
    private QuotaService $quotaService;
    private EntityManagerInterface $entityManager;
    private AccountService $accountService;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->quotaService = $this->createMock(QuotaService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->accountService = new AccountService(
            $this->accountRepository,
            $this->quotaService,
            $this->entityManager
        );
    }

    public function testCreateDefaultAccountCreatesFreePlan(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getCreatedAt')->willReturn(new \DateTime());

        $this->entityManager->expects($this->once())->method('persist')->with($this->callback(function ($account) {
            return $account instanceof Account && $account->getPlanType() === PlanType::FREE;
        }));
        $this->entityManager->expects($this->once())->method('flush');

        $account = $this->accountService->createDefaultAccount($user);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals(PlanType::FREE, $account->getPlanType());
    }

    public function testChangePlanUpdatesPlanType(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::FREE);
        $account->expects($this->once())->method('setPlanType')->with(PlanType::PRO);
        $account->expects($this->once())->method('setUpdatedAt')->with($this->isInstanceOf(\DateTimeInterface::class));
        $account->expects($this->once())->method('setUpdatedBy')->with(null);

        $this->entityManager->expects($this->once())->method('flush');

        $this->accountService->changePlan($account, PlanType::PRO);
    }

    public function testChangePlanCallsRevokeTeamAccessOnEnterpriseDowngrade(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::ENTERPRISE);
        $account->expects($this->once())->method('setPlanType')->with(PlanType::PRO);
        
        $teamService = $this->createMock(TeamService::class);
        $teamService->expects($this->once())->method('revokeTeamAccess')->with($account);
        
        $accountService = new AccountService(
            $this->accountRepository,
            $this->quotaService,
            $this->entityManager,
            null,
            $teamService
        );

        $this->entityManager->expects($this->once())->method('flush');

        $accountService->changePlan($account, PlanType::PRO);
    }
}

