<?php

namespace App\Enum;

enum PlanType: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    /**
     * Get the quota limit for this plan type
     * @return int|null Returns null for unlimited plans, otherwise the card limit
     */
    public function getQuotaLimit(): ?int
    {
        return match($this) {
            self::FREE => 1,
            self::PRO => 10,
            self::ENTERPRISE => null, // unlimited
        };
    }

    /**
     * Check if this plan has unlimited quota
     */
    public function isUnlimited(): bool
    {
        return $this->getQuotaLimit() === null;
    }

    /**
     * Get human-readable plan name
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::FREE => 'Free',
            self::PRO => 'Pro',
            self::ENTERPRISE => 'Enterprise',
        };
    }
}

