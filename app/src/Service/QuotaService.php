<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\QuotaExceededException;

class QuotaService
{
    public function __construct(
        // CardRepository will be injected when Card entity exists
        // For now, we'll use a placeholder approach
        private ?object $cardRepository = null
    ) {
    }

    /**
     * Check if user can create content within quota limits
     */
    public function canCreateContent(User $user, int $quantity = 1): bool
    {
        $account = $user->getAccount();
        if (!$account) {
            return false;
        }

        $quotaLimit = $account->getPlanType()->getQuotaLimit();
        if ($quotaLimit === null) {
            return true; // Unlimited
        }

        $currentUsage = $this->getCurrentUsage($user);
        return ($currentUsage + $quantity) <= $quotaLimit;
    }

    /**
     * Validate quota and throw exception if limit would be exceeded
     */
    public function validateQuota(User $user, int $quantity = 1): void
    {
        if (!$this->canCreateContent($user, $quantity)) {
            $account = $user->getAccount();
            if (!$account) {
                throw new QuotaExceededException("No account found for user.");
            }
            
            $planType = $account->getPlanType();
            $limit = $planType->getQuotaLimit();
            $currentUsage = $this->getCurrentUsage($user);
            
            // Store message data in exception for translation
            $messageData = $this->getQuotaMessageData($planType, $currentUsage, $limit);
            
            throw new QuotaExceededException(
                $this->getQuotaMessage($planType, $currentUsage, $limit),
                0,
                null,
                $messageData
            );
        }
    }

    /**
     * Get current usage count for user
     * Returns 0 if Card entity doesn't exist yet
     */
    public function getCurrentUsage(User $user): int
    {
        // If CardRepository is not available, return 0 (no cards created yet)
        if (!$this->cardRepository || !method_exists($this->cardRepository, 'count')) {
            return 0;
        }

        // When Card entity exists, this will count user's cards
        // For now, return 0 as placeholder
        try {
            return $this->cardRepository->count(['user' => $user]);
        } catch (\Exception $e) {
            // Card entity doesn't exist yet, return 0
            return 0;
        }
    }

    /**
     * Generate user-friendly quota exceeded message with upgrade suggestions
     */
    public function getQuotaMessage(\App\Enum\PlanType $planType, int $currentUsage, ?int $limit): string
    {
        $planName = $planType->getDisplayName();
        
        if ($limit === null) {
            return "You have unlimited quota on the {$planName} plan.";
        }

        $message = "You have reached your quota limit of {$limit} card(s). ";
        $message .= "You currently have {$currentUsage} card(s). ";

        // Add upgrade suggestions based on current plan
        if ($planType === \App\Enum\PlanType::FREE) {
            $message .= "Upgrade to Pro (10 cards) or Enterprise (unlimited) to create more cards.";
        } elseif ($planType === \App\Enum\PlanType::PRO) {
            $message .= "Upgrade to Enterprise (unlimited) to create more cards.";
        }

        return $message;
    }

    /**
     * Get quota message data for translation
     */
    public function getQuotaMessageData(\App\Enum\PlanType $planType, int $currentUsage, ?int $limit): array
    {
        return [
            'plan' => $planType->getDisplayName(),
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'is_unlimited' => $limit === null,
        ];
    }
}

