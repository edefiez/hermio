<?php

namespace App\Tests\Unit\Enum;

use App\Enum\PlanType;
use PHPUnit\Framework\TestCase;

class PlanTypeTest extends TestCase
{
    public function testFreePlanHasQuotaLimitOfOne(): void
    {
        $this->assertEquals(1, PlanType::FREE->getQuotaLimit());
    }

    public function testProPlanHasQuotaLimitOfTen(): void
    {
        $this->assertEquals(10, PlanType::PRO->getQuotaLimit());
    }

    public function testEnterprisePlanHasUnlimitedQuota(): void
    {
        $this->assertNull(PlanType::ENTERPRISE->getQuotaLimit());
    }

    public function testFreePlanIsNotUnlimited(): void
    {
        $this->assertFalse(PlanType::FREE->isUnlimited());
    }

    public function testProPlanIsNotUnlimited(): void
    {
        $this->assertFalse(PlanType::PRO->isUnlimited());
    }

    public function testEnterprisePlanIsUnlimited(): void
    {
        $this->assertTrue(PlanType::ENTERPRISE->isUnlimited());
    }

    public function testDisplayNames(): void
    {
        $this->assertEquals('Free', PlanType::FREE->getDisplayName());
        $this->assertEquals('Pro', PlanType::PRO->getDisplayName());
        $this->assertEquals('Enterprise', PlanType::ENTERPRISE->getDisplayName());
    }
}

