# Quickstart Guide: Stripe Billing Integration

**Feature**: 004-stripe-billing  
**Date**: 2025-12-08  
**Target Audience**: Developers implementing this feature

## Overview

This quickstart guide provides step-by-step instructions for implementing Stripe billing integration. Follow these steps in order to build the feature incrementally.

## Prerequisites

- Symfony 8.0+ installed and configured
- Feature 003 (Account Management / Subscription Model) completed
- Account entity and PlanType enum exist
- Database connection configured (PostgreSQL or MySQL)
- Doctrine ORM 3.x installed
- Stripe account created with API keys (test mode for development)
- Stripe products and prices configured for Pro and Enterprise plans

## Environment Setup

### Step 1: Install Stripe PHP SDK

**Command**: 
```bash
docker-compose exec app composer require stripe/stripe-php
```

**Verification**: Package appears in `composer.json` and `vendor/` directory.

---

### Step 2: Configure Stripe Environment Variables

**File**: `.env` or `.env.local`

Add Stripe configuration:

```env
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**Note**: Use test mode keys for development. Webhook secret is obtained from Stripe Dashboard → Developers → Webhooks.

**Verification**: Environment variables are accessible via `$_ENV['STRIPE_SECRET_KEY']`.

---

## Implementation Steps

### Step 1: Create Stripe Billing Entities

**Files**: 
- `app/src/Entity/StripeCustomer.php`
- `app/src/Entity/Payment.php`
- `app/src/Entity/Subscription.php`
- `app/src/Entity/ProcessedWebhookEvent.php` (optional but recommended)

Create entities following the data model in `data-model.md`. Use Doctrine attributes for mapping.

**Key Points**:
- StripeCustomer: OneToOne with User, stores `stripeCustomerId`
- Payment: ManyToOne with User, stores payment transactions
- Subscription: OneToOne with User, stores active subscription
- ProcessedWebhookEvent: Tracks processed webhooks for idempotency

**Verification**: Entities compile, repositories are auto-generated, relationships are correct.

---

### Step 2: Create Database Migration

**Command**:
```bash
docker-compose exec app php bin/console make:migration
```

**File**: `app/migrations/Version[timestamp].php`

Migration should create:
- `stripe_customers` table
- `payments` table
- `subscriptions` table
- `processed_webhook_events` table (optional)

**Verification**: Migration file created, can be executed without errors.

**Run Migration**:
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

---

### Step 3: Update User Entity

**File**: `app/src/Entity/User.php`

Add relationships to billing entities:

```php
#[ORM\OneToOne(targetEntity: StripeCustomer::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
private ?StripeCustomer $stripeCustomer = null;

#[ORM\OneToOne(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
private ?Subscription $subscription = null;

#[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
private Collection $payments;

public function __construct()
{
    $this->payments = new ArrayCollection();
}
```

**Verification**: User entity compiles, relationships work correctly.

---

### Step 4: Create Stripe Service

**File**: `app/src/Service/StripeService.php`

Create service wrapper for Stripe PHP SDK:

```php
namespace App\Service;

use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    public function createCheckoutSession(array $params): Session
    {
        return $this->stripe->checkout->sessions->create($params);
    }

    public function createCustomerPortalSession(string $customerId, string $returnUrl): PortalSession
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    public function retrieveCustomer(string $customerId)
    {
        return $this->stripe->customers->retrieve($customerId);
    }

    public function retrieveSubscription(string $subscriptionId)
    {
        return $this->stripe->subscriptions->retrieve($subscriptionId);
    }
}
```

**Configuration**: Register service in `services.yaml`:

```yaml
services:
    App\Service\StripeService:
        arguments:
            $stripeSecretKey: '%env(STRIPE_SECRET_KEY)%'
```

**Verification**: Service can be injected, Stripe API calls work in test mode.

---

### Step 5: Create Stripe Checkout Service

**File**: `app/src/Service/StripeCheckoutService.php`

Service for creating Checkout sessions:

```php
namespace App\Service;

use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\StripeCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class StripeCheckoutService
{
    public function __construct(
        private StripeService $stripeService,
        private StripeCustomerRepository $stripeCustomerRepository,
        private EntityManagerInterface $entityManager,
        private string $successUrl,
        private string $cancelUrl
    ) {
    }

    public function createCheckoutSession(User $user, PlanType $planType): string
    {
        // Get or create Stripe customer
        $stripeCustomer = $this->getOrCreateStripeCustomer($user);

        // Get Stripe price ID for plan (from environment or config)
        $priceId = $this->getPriceIdForPlan($planType);

        // Create Checkout session
        $session = $this->stripeService->createCheckoutSession([
            'customer' => $stripeCustomer->getStripeCustomerId(),
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
            'metadata' => [
                'user_id' => $user->getId(),
                'plan_type' => $planType->value,
            ],
        ]);

        return $session->url;
    }

    private function getOrCreateStripeCustomer(User $user): StripeCustomer
    {
        $stripeCustomer = $this->stripeCustomerRepository->findOneBy(['user' => $user]);
        
        if (!$stripeCustomer) {
            // Create Stripe customer
            $customer = $this->stripeService->createCustomer([
                'email' => $user->getEmail(),
                'metadata' => ['user_id' => $user->getId()],
            ]);

            // Create StripeCustomer entity
            $stripeCustomer = new StripeCustomer();
            $stripeCustomer->setUser($user);
            $stripeCustomer->setStripeCustomerId($customer->id);
            $this->entityManager->persist($stripeCustomer);
            $this->entityManager->flush();
        }

        return $stripeCustomer;
    }

    private function getPriceIdForPlan(PlanType $planType): string
    {
        // Get from environment variables or configuration
        return match($planType) {
            PlanType::PRO => $_ENV['STRIPE_PRICE_ID_PRO'],
            PlanType::ENTERPRISE => $_ENV['STRIPE_PRICE_ID_ENTERPRISE'],
            default => throw new \InvalidArgumentException('Invalid plan type for checkout'),
        };
    }
}
```

**Verification**: Service creates Checkout sessions, Stripe customer is created if needed.

---

### Step 6: Create Webhook Service

**File**: `app/src/Service/StripeWebhookService.php`

Service for processing webhook events:

```php
namespace App\Service;

use App\Entity\ProcessedWebhookEvent;
use App\Entity\Subscription;
use App\Entity\Payment;
use App\Repository\ProcessedWebhookEventRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\PaymentRepository;
use App\Repository\UserRepository;
use App\Service\AccountService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;

class StripeWebhookService
{
    public function __construct(
        private ProcessedWebhookEventRepository $processedEventRepository,
        private SubscriptionRepository $subscriptionRepository,
        private PaymentRepository $paymentRepository,
        private UserRepository $userRepository,
        private AccountService $accountService,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function processEvent(Event $event): void
    {
        // Check if event already processed (idempotency)
        if ($this->isEventProcessed($event->id)) {
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
            }

            $this->markEventProcessed($event->id, true);
        } catch (\Exception $e) {
            $this->markEventProcessed($event->id, false, $e->getMessage());
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
        // Create Payment entity record
        // Implementation details...
    }

    private function isEventProcessed(string $eventId): bool
    {
        return $this->processedEventRepository->findOneBy(['stripeEventId' => $eventId]) !== null;
    }

    private function markEventProcessed(string $eventId, bool $success, ?string $errorMessage = null): void
    {
        $processed = new ProcessedWebhookEvent();
        $processed->setStripeEventId($eventId);
        $processed->setSuccess($success);
        $processed->setProcessedAt(new \DateTime());
        if ($errorMessage) {
            $processed->setErrorMessage($errorMessage);
        }
        $this->entityManager->persist($processed);
        $this->entityManager->flush();
    }
}
```

**Verification**: Webhook events are processed, idempotency works, Account is updated.

---

### Step 7: Create Controllers

**Files**:
- `app/src/Controller/StripeCheckoutController.php`
- `app/src/Controller/StripeWebhookController.php`
- `app/src/Controller/SubscriptionController.php`

Follow route definitions in `contracts/routes.md`.

**Key Points**:
- CheckoutController: Creates sessions, handles success/cancel callbacks
- WebhookController: Validates signatures, processes events
- SubscriptionController: Displays subscription management UI

**Verification**: Routes work, controllers return correct responses.

---

### Step 8: Create Templates

**Files**:
- `app/templates/subscription/upgrade.html.twig`
- `app/templates/subscription/manage.html.twig`
- `app/templates/subscription/history.html.twig`

Update existing:
- `app/templates/account/my_plan.html.twig` (add upgrade buttons)

**Verification**: Templates render correctly, translations work.

---

### Step 9: Configure Stripe Webhook Endpoint

**In Stripe Dashboard**:
1. Go to Developers → Webhooks
2. Add endpoint: `https://your-domain.com/stripe/webhook`
3. Select events to listen for:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
4. Copy webhook signing secret to `STRIPE_WEBHOOK_SECRET` environment variable

**For Local Development**: Use Stripe CLI to forward webhooks:
```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

**Verification**: Webhooks are received and processed correctly.

---

## Testing Checklist

- [ ] Stripe Checkout session creation works
- [ ] User can complete payment and plan upgrades
- [ ] Webhook events are received and processed
- [ ] Account.planType is synchronized with Stripe subscription
- [ ] Payment history displays correctly
- [ ] Customer Portal access works
- [ ] Subscription cancellation downgrades account
- [ ] Idempotent webhook processing prevents duplicates
- [ ] Error handling works for failed payments
- [ ] Translations are complete (EN/FR)

---

## Next Steps

After completing this quickstart:

1. Add comprehensive error handling
2. Implement payment retry logic
3. Add admin interface for subscription management
4. Set up monitoring for webhook processing
5. Add logging for all Stripe operations
6. Implement reconciliation process for failed events

---

## Troubleshooting

**Webhook signature validation fails**:
- Verify `STRIPE_WEBHOOK_SECRET` is correct
- Check that raw request body is used (not parsed JSON)
- Ensure webhook secret matches the endpoint in Stripe Dashboard

**Checkout session creation fails**:
- Verify Stripe API keys are correct
- Check that Stripe products and prices exist
- Ensure customer creation works

**Account not updating after payment**:
- Check webhook processing logs
- Verify webhook events are being received
- Check that Account entity relationship is correct

