<?php

namespace App\Tests\Unit\Service;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\TeamRole;
use App\Repository\CardAssignmentRepository;
use App\Repository\TeamMemberRepository;
use App\Service\TeamService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TeamServiceTest extends TestCase
{
    private TeamMemberRepository $teamMemberRepository;
    private CardAssignmentRepository $cardAssignmentRepository;
    private EntityManagerInterface $entityManager;
    private TeamService $teamService;

    protected function setUp(): void
    {
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        $this->cardAssignmentRepository = $this->createMock(CardAssignmentRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->teamService = new TeamService(
            $this->teamMemberRepository,
            $this->cardAssignmentRepository,
            $this->entityManager
        );
    }

    public function testCanManageTeamReturnsTrueForAccountOwner(): void
    {
        $account = $this->createMock(Account::class);
        $user = $this->createMock(User::class);
        $account->method('getUser')->willReturn($user);

        $this->assertTrue($this->teamService->canManageTeam($account, $user));
    }

    public function testCanManageTeamReturnsTrueForAdminMember(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        $user = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getRole')->willReturn(TeamRole::ADMIN);
        
        $this->teamMemberRepository
            ->method('findByAccountAndUser')
            ->with($account, $user)
            ->willReturn($teamMember);

        $this->assertTrue($this->teamService->canManageTeam($account, $user));
    }

    public function testCanManageTeamReturnsFalseForMemberRole(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        $user = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getRole')->willReturn(TeamRole::MEMBER);
        
        $this->teamMemberRepository
            ->method('findByAccountAndUser')
            ->with($account, $user)
            ->willReturn($teamMember);

        $this->assertFalse($this->teamService->canManageTeam($account, $user));
    }

    public function testChangeRoleThrowsExceptionWhenNotOwner(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        $requester = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getAccount')->willReturn($account);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.role.change.denied');
        
        $this->teamService->changeRole($teamMember, TeamRole::ADMIN, $requester);
    }

    public function testChangeRoleThrowsExceptionWhenChangingOwnerRole(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getAccount')->willReturn($account);
        $teamMember->method('getUser')->willReturn($owner);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change account owner role');
        
        $this->teamService->changeRole($teamMember, TeamRole::ADMIN, $owner);
    }

    public function testRemoveTeamMemberThrowsExceptionWhenNotOwner(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        $requester = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getAccount')->willReturn($account);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('team.remove.denied');
        
        $this->teamService->removeTeamMember($teamMember, $requester);
    }

    public function testRemoveTeamMemberThrowsExceptionWhenRemovingOwner(): void
    {
        $account = $this->createMock(Account::class);
        $owner = $this->createMock(User::class);
        
        $account->method('getUser')->willReturn($owner);
        
        $teamMember = $this->createMock(TeamMember::class);
        $teamMember->method('getAccount')->willReturn($account);
        $teamMember->method('getUser')->willReturn($owner);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove account owner');
        
        $this->teamService->removeTeamMember($teamMember, $owner);
    }

    public function testRevokeTeamAccessSetsAllMembersToRevoked(): void
    {
        $account = $this->createMock(Account::class);
        
        $member1 = $this->createMock(TeamMember::class);
        $member2 = $this->createMock(TeamMember::class);
        
        $this->teamMemberRepository
            ->method('findByAccount')
            ->with($account)
            ->willReturn([$member1, $member2]);

        $member1->expects($this->once())->method('setInvitationStatus')->with('revoked');
        $member2->expects($this->once())->method('setInvitationStatus')->with('revoked');
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->teamService->revokeTeamAccess($account);
    }
}

