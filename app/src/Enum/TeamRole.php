<?php

namespace App\Enum;

enum TeamRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MEMBER => 'Member',
        };
    }

    public function canAssignCards(): bool
    {
        return $this === self::ADMIN;
    }

    public function canManageMembers(): bool
    {
        return $this === self::ADMIN;
    }

    public function canViewAllCards(): bool
    {
        return $this === self::ADMIN;
    }
}

