<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private AccountService $accountService,
        private StripeService $stripeService,
        private LoggerInterface $logger
    ) {
    }

    public function syncSubscriptionFromStripe(string $subscriptionId): Subscription
    {
        $this->logger->info('Syncing subscription from Stripe', ['subscription_id' => $subscriptionId]);

        try {
            $stripeSubscription = $this->stripeService->retrieveSubscription($subscriptionId);
            
            $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $subscriptionId]);
            if (!$subscription) {
                $this->logger->error('Subscription not found in database', ['subscription_id' => $subscriptionId]);
                throw new \RuntimeException('Subscription not found in database');
            }

            // Update subscription from Stripe data
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setCurrentPeriodStart(new \DateTime('@' . $stripeSubscription->current_period_start));
            $subscription->setCurrentPeriodEnd(new \DateTime('@' . $stripeSubscription->current_period_end));

            // Determine plan type
            $planType = $this->determinePlanType($stripeSubscription);
            $subscription->setPlanType($planType);

            // Update Account
            $this->updateAccountFromSubscription($subscription);

            $this->logger->info('Subscription synced successfully', [
                'subscription_id' => $subscriptionId,
                'status' => $subscription->getStatus(),
                'plan_type' => $planType->value,
            ]);

            return $subscription;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync subscription from Stripe', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function updateAccountFromSubscription(Subscription $subscription): void
    {
        $user = $subscription->getUser();
        $account = $user->getAccount();
        
        if ($account && $account->getPlanType() !== $subscription->getPlanType()) {
            $this->accountService->changePlan($account, $subscription->getPlanType(), false, 'stripe_sync');
        }
    }

    public function downgradeAccountToFree(User $user): void
    {
        $account = $user->getAccount();
        if ($account) {
            $this->accountService->changePlan($account, PlanType::FREE, true, 'stripe_downgrade');
        }
    }

    private function determinePlanType($stripeSubscription): PlanType
    {
        // Extract plan type from subscription items
        if (isset($stripeSubscription->items->data[0]->price->metadata->plan_type)) {
            return PlanType::from($stripeSubscription->items->data[0]->price->metadata->plan_type);
        }

        // Fallback: try to determine from price ID
        if (isset($stripeSubscription->items->data[0]->price->id)) {
            $priceId = $stripeSubscription->items->data[0]->price->id;
            if ($priceId === $_ENV['STRIPE_PRICE_ID_PRO'] ?? null) {
                return PlanType::PRO;
            }
            if ($priceId === $_ENV['STRIPE_PRICE_ID_ENTERPRISE'] ?? null) {
                return PlanType::ENTERPRISE;
            }
        }

        // Fallback: use metadata
        if (isset($stripeSubscription->metadata->plan_type)) {
            return PlanType::from($stripeSubscription->metadata->plan_type);
        }

        throw new \RuntimeException('Unable to determine plan type from subscription data');
    }
}

