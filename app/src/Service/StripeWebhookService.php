<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\ProcessedWebhookEvent;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\ProcessedWebhookEventRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;

class StripeWebhookService
{
    private array $allowedPriceIds = [];

    public function __construct(
        private ProcessedWebhookEventRepository $processedEventRepository,
        private SubscriptionRepository $subscriptionRepository,
        private PaymentRepository $paymentRepository,
        private UserRepository $userRepository,
        private AccountService $accountService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        // Initialize allowed price IDs from environment
        $this->allowedPriceIds = array_filter([
            $_ENV['STRIPE_PRICE_ID_PRO'] ?? null,
            $_ENV['STRIPE_PRICE_ID_ENTERPRISE'] ?? null,
        ]);
    }

    public function processEvent(Event $event): void
    {
        // Check if event already processed (idempotency)
        if ($this->isEventProcessed($event->id)) {
            $this->logger->info('Webhook event already processed', ['event_id' => $event->id]);
            return;
        }

        // Filter events by product/price ID (only process events for our products)
        if (!$this->isEventForOurProducts($event)) {
            $this->logger->info('Webhook event ignored - not for our products', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);
            // Mark as processed to avoid reprocessing
            $this->markEventProcessed($event->id, $event->type, true);
            return;
        }

        try {
            switch ($event->type) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionEvent($event);
                    break;
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event);
                    break;
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event);
                    break;
                default:
                    $this->logger->info('Unhandled webhook event type', ['event_type' => $event->type, 'event_id' => $event->id]);
            }

            $this->markEventProcessed($event->id, $event->type, true);
        } catch (\Exception $e) {
            $this->logger->error('Error processing webhook event', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);
            $this->markEventProcessed($event->id, $event->type, false, $e->getMessage());
            throw $e;
        }
    }

    private function handleSubscriptionEvent(Event $event): void
    {
        $subscriptionData = $event->data->object;
        $userId = $subscriptionData->metadata->user_id ?? null;

        if (!$userId) {
            throw new \RuntimeException('User ID not found in subscription metadata');
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        // Create or update Subscription entity
        $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->setUser($user);
        }

        $subscription->setStripeSubscriptionId($subscriptionData->id);
        $subscription->setStatus($subscriptionData->status);
        $subscription->setCurrentPeriodStart(new \DateTime('@' . $subscriptionData->current_period_start));
        $subscription->setCurrentPeriodEnd(new \DateTime('@' . $subscriptionData->current_period_end));

        // Determine plan type from subscription items
        $planType = $this->determinePlanType($subscriptionData);
        $subscription->setPlanType($planType);

        if (isset($subscriptionData->cancel_at_period_end) && $subscriptionData->cancel_at_period_end) {
            $subscription->setCancelAtPeriodEnd(new \DateTime('@' . $subscriptionData->current_period_end));
        }

        if (isset($subscriptionData->canceled_at)) {
            $subscription->setCanceledAt(new \DateTime('@' . $subscriptionData->canceled_at));
        }

        $this->entityManager->persist($subscription);
        
        // Update Account.planType
        $account = $user->getAccount();
        if ($account) {
            $this->accountService->changePlan($account, $planType, false, 'stripe_webhook');
        }

        $this->entityManager->flush();
    }

    private function handleSubscriptionDeleted(Event $event): void
    {
        $subscriptionData = $event->data->object;
        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $subscriptionData->id]);
        
        if ($subscription) {
            $user = $subscription->getUser();
            $this->entityManager->remove($subscription);
            
            // Downgrade Account to FREE
            $account = $user->getAccount();
            if ($account) {
                $this->accountService->changePlan($account, PlanType::FREE, true, 'stripe_webhook');
            }
            
            $this->entityManager->flush();
        }
    }

    private function handlePaymentSucceeded(Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        // Extract user ID from metadata
        $userId = $paymentIntent->metadata->user_id ?? null;
        if (!$userId) {
            $this->logger->warning('Payment intent missing user_id in metadata', ['payment_intent_id' => $paymentIntent->id]);
            return;
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->warning('User not found for payment intent', ['payment_intent_id' => $paymentIntent->id, 'user_id' => $userId]);
            return;
        }

        // Check if payment already exists
        $existingPayment = $this->paymentRepository->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);
        if ($existingPayment) {
            $this->logger->info('Payment already exists', ['payment_intent_id' => $paymentIntent->id]);
            return;
        }

        // Create Payment entity
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setStripePaymentIntentId($paymentIntent->id);
        $payment->setStatus('succeeded');
        $payment->setAmount($paymentIntent->amount);
        $payment->setCurrency($paymentIntent->currency);
        $payment->setPaidAt(new \DateTime('@' . $paymentIntent->created));
        
        // Determine plan type from metadata if available
        if (isset($paymentIntent->metadata->plan_type)) {
            $planType = PlanType::from($paymentIntent->metadata->plan_type);
            $payment->setPlanType($planType);
        }

        // Store event data for reference
        $payment->setStripeEventData(json_encode($event->toArray()));

        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    private function handlePaymentFailed(Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        $this->logger->warning('Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'user_id' => $paymentIntent->metadata->user_id ?? null,
        ]);

        // Log failure but don't downgrade plan (grace period)
        // Could send notification to user here
    }

    private function determinePlanType($subscriptionData): PlanType
    {
        // Extract plan type from subscription items
        if (isset($subscriptionData->items->data[0]->price->metadata->plan_type)) {
            return PlanType::from($subscriptionData->items->data[0]->price->metadata->plan_type);
        }

        // Fallback: try to determine from price ID
        if (isset($subscriptionData->items->data[0]->price->id)) {
            $priceId = $subscriptionData->items->data[0]->price->id;
            if ($priceId === $_ENV['STRIPE_PRICE_ID_PRO'] ?? null) {
                return PlanType::PRO;
            }
            if ($priceId === $_ENV['STRIPE_PRICE_ID_ENTERPRISE'] ?? null) {
                return PlanType::ENTERPRISE;
            }
        }

        // Fallback: use metadata
        if (isset($subscriptionData->metadata->plan_type)) {
            return PlanType::from($subscriptionData->metadata->plan_type);
        }

        throw new \RuntimeException('Unable to determine plan type from subscription data');
    }

    private function isEventProcessed(string $eventId): bool
    {
        return $this->processedEventRepository->findOneBy(['stripeEventId' => $eventId]) !== null;
    }

    private function markEventProcessed(string $eventId, string $eventType, bool $success, ?string $errorMessage = null): void
    {
        $processed = new ProcessedWebhookEvent();
        $processed->setStripeEventId($eventId);
        $processed->setEventType($eventType);
        $processed->setProcessedAt(new \DateTime());
        $processed->setSuccess($success);
        if ($errorMessage) {
            $processed->setErrorMessage($errorMessage);
        }
        $this->entityManager->persist($processed);
        $this->entityManager->flush();
    }

    /**
     * Check if webhook event is for our products (Pro or Enterprise plans)
     * This filters out events from other Stripe products that might be configured
     */
    private function isEventForOurProducts(Event $event): bool
    {
        // If no price IDs configured, accept all events (backward compatibility)
        if (empty($this->allowedPriceIds)) {
            return true;
        }

        $object = $event->data->object;

        // For subscription events, check the price ID in subscription items
        if (in_array($event->type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'])) {
            if (isset($object->items->data[0]->price->id)) {
                $priceId = $object->items->data[0]->price->id;
                return in_array($priceId, $this->allowedPriceIds);
            }
            // If no price ID found, check metadata as fallback
            if (isset($object->metadata->plan_type)) {
                return in_array($object->metadata->plan_type, ['pro', 'enterprise']);
            }
            return false;
        }

        // For payment events, check metadata or invoice line items
        if (in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'])) {
            // Check metadata first
            if (isset($object->metadata->plan_type)) {
                return in_array($object->metadata->plan_type, ['pro', 'enterprise']);
            }
            
            // Check invoice line items if available
            if (isset($object->invoice)) {
                // Would need to fetch invoice, but for now accept if metadata exists
                return isset($object->metadata->user_id);
            }
            
            // If payment intent has user_id metadata, likely ours
            return isset($object->metadata->user_id);
        }

        // For other event types, accept by default (they might not have price info)
        return true;
    }
}

