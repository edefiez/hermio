<?php

namespace App\Tests\Unit\Enum;

use App\Enum\TeamRole;
use PHPUnit\Framework\TestCase;

class TeamRoleTest extends TestCase
{
    public function testAdminCanAssignCards(): void
    {
        $this->assertTrue(TeamRole::ADMIN->canAssignCards());
    }

    public function testMemberCannotAssignCards(): void
    {
        $this->assertFalse(TeamRole::MEMBER->canAssignCards());
    }

    public function testAdminCanManageMembers(): void
    {
        $this->assertTrue(TeamRole::ADMIN->canManageMembers());
    }

    public function testMemberCannotManageMembers(): void
    {
        $this->assertFalse(TeamRole::MEMBER->canManageMembers());
    }

    public function testAdminCanViewAllCards(): void
    {
        $this->assertTrue(TeamRole::ADMIN->canViewAllCards());
    }

    public function testMemberCannotViewAllCards(): void
    {
        $this->assertFalse(TeamRole::MEMBER->canViewAllCards());
    }

    public function testDisplayNames(): void
    {
        $this->assertEquals('Administrator', TeamRole::ADMIN->getDisplayName());
        $this->assertEquals('Member', TeamRole::MEMBER->getDisplayName());
    }
}

