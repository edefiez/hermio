<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccountService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private QuotaService $quotaService,
        private EntityManagerInterface $entityManager,
        private ?BrandingService $brandingService = null,
        private ?TeamService $teamService = null
    ) {
    }

    /**
     * Create default account for a new user with FREE plan
     */
    public function createDefaultAccount(User $user): Account
    {
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType(PlanType::FREE);
        $account->setCreatedAt($user->getCreatedAt() ?? new \DateTime());
        
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        
        return $account;
    }

    /**
     * Change user's subscription plan
     */
    public function changePlan(Account $account, PlanType $newPlan, bool $confirmDowngrade = false, ?string $updatedBy = null): void
    {
        $currentPlan = $account->getPlanType();
        
        // Check if downgrading
        if ($this->isDowngrade($currentPlan, $newPlan)) {
            $user = $account->getUser();
            $currentUsage = $this->quotaService->getCurrentUsage($user);
            $newLimit = $newPlan->getQuotaLimit();
            
            if ($newLimit !== null && $currentUsage > $newLimit && !$confirmDowngrade) {
                throw new \InvalidArgumentException(
                    "Cannot downgrade: user has {$currentUsage} cards, but {$newPlan->getDisplayName()} plan only allows {$newLimit} cards. Please confirm the downgrade."
                );
            }
        }
        
        $oldPlan = $account->getPlanType();
        $account->setPlanType($newPlan);
        $account->setUpdatedAt(new \DateTime());
        $account->setUpdatedBy($updatedBy);
        
        $this->entityManager->flush();
        
        // Handle branding plan downgrade if applicable
        if ($this->brandingService && $this->isDowngrade($oldPlan, $newPlan)) {
            $this->brandingService->handlePlanDowngrade($account, $oldPlan, $newPlan);
        }

        // Handle team access revocation when downgrading from Enterprise
        if ($oldPlan === PlanType::ENTERPRISE && $newPlan !== PlanType::ENTERPRISE) {
            if ($this->teamService) {
                $this->teamService->revokeTeamAccess($account);
            }
        }
    }

    /**
     * Check if plan change is a downgrade
     */
    private function isDowngrade(PlanType $current, PlanType $new): bool
    {
        $order = [PlanType::FREE, PlanType::PRO, PlanType::ENTERPRISE];
        $currentIndex = array_search($current, $order);
        $newIndex = array_search($new, $order);
        
        return $newIndex < $currentIndex;
    }
}

