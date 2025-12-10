<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\QuotaExceededException;
use App\Repository\CardRepository;

class QuotaService
{
    public function __construct(
        private CardRepository $cardRepository
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
     */
    public function getCurrentUsage(User $user): int
    {
        return $this->cardRepository->count(['user' => $user, 'status' => 'active']);
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

