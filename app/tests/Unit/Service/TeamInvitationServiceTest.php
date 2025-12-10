<?php

namespace App\Tests\Unit\Service;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\PlanType;
use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use App\Service\InvitationRateLimiter;
use App\Service\TeamInvitationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class TeamInvitationServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TeamMemberRepository $teamMemberRepository;
    private MailerInterface $mailer;
    private TeamInvitationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        
        $this->service = new TeamInvitationService(
            $this->entityManager,
            $this->teamMemberRepository,
            $this->mailer
        );
    }

    public function testCreateInvitationThrowsExceptionForNonEnterprisePlan(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::FREE);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.access_denied');
        
        $this->service->createInvitation($account, 'test@example.com', TeamRole::MEMBER);
    }

    public function testCreateInvitationThrowsExceptionForDuplicatePendingInvitation(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::ENTERPRISE);

        $existingMember = $this->createMock(TeamMember::class);
        $existingMember->method('getInvitationStatus')->willReturn('pending');

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getOneOrNullResult')->willReturn($existingMember);

        $this->teamMemberRepository
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.invite.duplicate');
        
        $this->service->createInvitation($account, 'test@example.com', TeamRole::MEMBER);
    }

    public function testCreateInvitationCreatesTeamMemberWithToken(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::ENTERPRISE);

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getOneOrNullResult')->willReturn(null);

        $this->teamMemberRepository
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $teamMember = $this->service->createInvitation($account, 'test@example.com', TeamRole::MEMBER);

        $this->assertInstanceOf(TeamMember::class, $teamMember);
        $this->assertEquals('test@example.com', $teamMember->getEmail());
        $this->assertEquals(TeamRole::MEMBER, $teamMember->getRole());
        $this->assertEquals('pending', $teamMember->getInvitationStatus());
        $this->assertNotNull($teamMember->getInvitationToken());
        $this->assertNotNull($teamMember->getInvitationExpiresAt());
    }

    public function testResendInvitationThrowsExceptionForNonPendingStatus(): void
    {
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getInvitationStatus')->willReturn('accepted');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.invite.cannot_resend');
        
        $this->service->resendInvitation($teamMember, 'https://example.com/accept');
    }

    public function testAcceptInvitationThrowsExceptionForInvalidToken(): void
    {
        $this->teamMemberRepository
            ->method('findByToken')
            ->with('invalid-token')
            ->willReturn(null);

        $user = $this->createMock(User::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.invitation.invalid');
        
        $this->service->acceptInvitation('invalid-token', $user);
    }

    public function testAcceptInvitationThrowsExceptionForExpiredInvitation(): void
    {
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getInvitationStatus')->willReturn('pending');
        $teamMember->method('getInvitationExpiresAt')->willReturn(new \DateTime('-1 day'));

        $this->teamMemberRepository
            ->method('findByToken')
            ->with('expired-token')
            ->willReturn($teamMember);

        $user = $this->createMock(User::class);

        $this->entityManager->expects($this->once())->method('flush');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.invitation.expired');
        
        $this->service->acceptInvitation('expired-token', $user);
    }

    public function testAcceptInvitationThrowsExceptionForEmailMismatch(): void
    {
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getInvitationStatus')->willReturn('pending');
        $teamMember->method('getInvitationExpiresAt')->willReturn(new \DateTime('+7 days'));
        $teamMember->method('getEmail')->willReturn('invited@example.com');

        $this->teamMemberRepository
            ->method('findByToken')
            ->with('valid-token')
            ->willReturn($teamMember);

        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('different@example.com');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.invitation.email_mismatch');
        
        $this->service->acceptInvitation('valid-token', $user);
    }
}

