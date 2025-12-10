# Data Model: Stripe Billing Integration

**Date**: December 8, 2025  
**Feature**: Stripe Billing Integration  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The Stripe billing integration introduces three new entities (`StripeCustomer`, `Payment`, `Subscription`) that extend the existing `Account` and `User` entities with billing and payment tracking capabilities. These entities maintain relationships with User and Account to synchronize subscription status and provide payment history.

## Entities

### 1. StripeCustomer Entity

**Purpose**: Links a User to their Stripe customer record, enabling all Stripe billing operations

```php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StripeCustomerRepository::class)]
#[ORM\Table(name: 'stripe_customers')]
#[ORM\UniqueConstraint(name: 'user_stripe_customer_unique', columns: ['user_id'])]
#[ORM\HasLifecycleCallbacks]
class StripeCustomer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'stripeCustomer')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    private string $stripeCustomerId; // Stripe customer ID (cus_xxx)

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `user`: OneToOne relationship to User entity (bidirectional)
- `stripeCustomerId`: Stripe customer ID (e.g., `cus_xxx`) - unique, required
- `createdAt`: Timestamp when Stripe customer was created
- `updatedAt`: Timestamp when record was last updated (nullable)

**Validation Rules**:
- `stripeCustomerId`: Required, must be unique, max 255 characters
- `user`: Required, must be unique (one Stripe customer per user)

**Business Rules**:
- One Stripe customer record per user (created when first payment is initiated)
- Stripe customer ID is immutable once created
- Record is deleted when user is deleted (CASCADE)
- Stripe customer can exist without active subscription (for future payments)

---

### 2. Payment Entity

**Purpose**: Tracks all payment transactions processed through Stripe, providing audit trail

```php
namespace App\Entity;

use App\Enum\PlanType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
#[ORM\UniqueConstraint(name: 'stripe_payment_intent_unique', columns: ['stripe_payment_intent_id'])]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    private string $stripePaymentIntentId; // Stripe payment intent ID (pi_xxx)

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    private string $status; // succeeded, failed, refunded, pending

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    private int $amount; // Amount in cents (e.g., 2999 = $29.99)

    #[ORM\Column(type: Types::STRING, length: 3)]
    #[Assert\Length(min: 3, max: 3)]
    private string $currency; // e.g., 'usd', 'eur'

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PlanType::class, nullable: true)]
    private ?PlanType $planType = null; // Plan associated with this payment

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $paidAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $stripeEventData = null; // JSON of Stripe event for reference
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `user`: ManyToOne relationship to User (one user can have many payments)
- `stripePaymentIntentId`: Stripe payment intent ID (e.g., `pi_xxx`) - unique, required
- `status`: Payment status (succeeded, failed, refunded, pending)
- `amount`: Payment amount in cents (e.g., 2999 = $29.99)
- `currency`: Currency code (3 characters, e.g., 'usd')
- `planType`: Associated plan type (nullable, for upgrade payments)
- `paidAt`: Timestamp when payment was completed
- `createdAt`: Timestamp when payment record was created
- `stripeEventData`: JSON of Stripe event data (for audit/reference)

**Validation Rules**:
- `stripePaymentIntentId`: Required, must be unique
- `status`: Required, must be valid status value
- `amount`: Required, must be positive or zero
- `currency`: Required, exactly 3 characters
- `planType`: Optional, must be valid PlanType enum if provided

**Business Rules**:
- Payment records persist even if subscription is cancelled
- Multiple payments can exist for one subscription (renewals, upgrades)
- Payment status can be updated via webhooks
- Refunded payments keep original record with updated status

---

### 3. Subscription Entity

**Purpose**: Tracks active Stripe subscriptions and synchronizes with Account entity

```php
namespace App\Entity;

use App\Enum\PlanType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscriptions')]
#[ORM\UniqueConstraint(name: 'user_subscription_unique', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'stripe_subscription_unique', columns: ['stripe_subscription_id'])]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'subscription')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank]
    private string $stripeSubscriptionId; // Stripe subscription ID (sub_xxx)

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PlanType::class)]
    #[Assert\NotBlank]
    private PlanType $planType; // Current plan (synced with Account)

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    private string $status; // active, cancelled, past_due, trialing, etc.

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $currentPeriodStart;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $currentPeriodEnd;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $canceledAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelAtPeriodEnd = null; // If true, cancel at period end

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `user`: OneToOne relationship to User (one active subscription per user)
- `stripeSubscriptionId`: Stripe subscription ID (e.g., `sub_xxx`) - unique, required
- `planType`: Current plan type (synced with Account.planType)
- `status`: Subscription status (active, cancelled, past_due, trialing, etc.)
- `currentPeriodStart`: Start of current billing period
- `currentPeriodEnd`: End of current billing period
- `canceledAt`: Timestamp when subscription was cancelled (nullable)
- `cancelAtPeriodEnd`: If true, subscription will cancel at period end (nullable)
- `createdAt`: Timestamp when subscription was created
- `updatedAt`: Timestamp when subscription was last updated

**Validation Rules**:
- `stripeSubscriptionId`: Required, must be unique
- `planType`: Required, must be valid PlanType enum
- `status`: Required, must be valid status value
- `currentPeriodStart` and `currentPeriodEnd`: Required, must be valid dates

**Business Rules**:
- One active subscription per user (enforced by unique constraint)
- Subscription status is synchronized with Stripe via webhooks
- When subscription is cancelled, it remains active until `currentPeriodEnd`
- Plan type must match Account.planType (synchronized on webhook events)

---

### 4. ProcessedWebhookEvent Entity (Optional but Recommended)

**Purpose**: Tracks processed webhook events for idempotency

```php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcessedWebhookEventRepository::class)]
#[ORM\Table(name: 'processed_webhook_events')]
#[ORM\UniqueConstraint(name: 'stripe_event_id_unique', columns: ['stripe_event_id'])]
class ProcessedWebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $stripeEventId; // Stripe event ID (evt_xxx)

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $eventType; // e.g., 'payment_intent.succeeded'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $processedAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $success; // Whether processing succeeded

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null; // Error message if processing failed
}
```

**Purpose**: Prevents duplicate webhook processing by storing processed event IDs

---

## Entity Relationships

### Relationship Map

```
User (1) ←→ (1) StripeCustomer
User (1) ←→ (1) Subscription
User (1) ←→ (*) Payment
User (1) ←→ (1) Account (existing)
```

**Relationship Details**:

1. **User ↔ StripeCustomer**: OneToOne bidirectional
   - Owning side: StripeCustomer (contains `user_id` foreign key)
   - Optional: StripeCustomer only created when first payment initiated
   - Cascade: DELETE (customer deleted when user deleted)

2. **User ↔ Subscription**: OneToOne bidirectional
   - Owning side: Subscription (contains `user_id` foreign key)
   - Optional: Subscription only exists when active subscription exists
   - Cascade: DELETE (subscription deleted when user deleted)
   - Unique: One active subscription per user

3. **User ↔ Payment**: OneToMany (User has many Payments)
   - Owning side: Payment (contains `user_id` foreign key)
   - Cascade: DELETE (payments deleted when user deleted)
   - Multiple payments per user (renewals, upgrades)

4. **User ↔ Account**: OneToOne (existing from Feature 003)
   - Account.planType must be synchronized with Subscription.planType

---

## Database Schema

### stripe_customers Table

```sql
CREATE TABLE stripe_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    stripe_customer_id VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_stripe_customer_id (stripe_customer_id)
);
```

### payments Table

```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stripe_payment_intent_id VARCHAR(255) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    amount INT NOT NULL,
    currency VARCHAR(3) NOT NULL,
    plan_type VARCHAR(20) NULL,
    paid_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    stripe_event_data TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_paid_at (paid_at),
    INDEX idx_stripe_payment_intent_id (stripe_payment_intent_id)
);
```

### subscriptions Table

```sql
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    stripe_subscription_id VARCHAR(255) NOT NULL UNIQUE,
    plan_type VARCHAR(20) NOT NULL,
    status VARCHAR(50) NOT NULL,
    current_period_start DATETIME NOT NULL,
    current_period_end DATETIME NOT NULL,
    canceled_at DATETIME NULL,
    cancel_at_period_end DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_current_period_end (current_period_end),
    INDEX idx_stripe_subscription_id (stripe_subscription_id)
);
```

### processed_webhook_events Table (Optional)

```sql
CREATE TABLE processed_webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stripe_event_id VARCHAR(255) NOT NULL UNIQUE,
    event_type VARCHAR(100) NOT NULL,
    processed_at DATETIME NOT NULL,
    success BOOLEAN NOT NULL,
    error_message TEXT NULL,
    INDEX idx_stripe_event_id (stripe_event_id),
    INDEX idx_event_type (event_type),
    INDEX idx_processed_at (processed_at)
);
```

---

## Data Validation

### Entity-Level Validation

All entities use Symfony Validator constraints:

- **StripeCustomer**: `stripeCustomerId` must be unique and not blank
- **Payment**: `stripePaymentIntentId` must be unique, `amount` must be positive, `currency` must be 3 characters
- **Subscription**: `stripeSubscriptionId` must be unique, `planType` must be valid enum, `status` must be valid

### Business Logic Validation

**Subscription Synchronization**:
- Subscription.planType must match Account.planType (enforced by service layer)
- When webhook updates Subscription, Account.planType is updated accordingly
- When Account.planType is manually changed, Subscription is updated via Stripe API

**Payment Status Updates**:
- Payment status can only be updated via webhook events
- Status transitions: pending → succeeded/failed, succeeded → refunded

---

## State Transitions

### Subscription Lifecycle

1. **Created**: Subscription created when user completes Checkout payment
   - Status: `active`
   - `currentPeriodStart` and `currentPeriodEnd` set from Stripe
   - `planType` matches Account.planType

2. **Active**: Subscription is active and billing monthly
   - Status: `active`
   - Renews automatically at `currentPeriodEnd`

3. **Cancelled**: User cancels subscription
   - Status: `cancelled` or `active` with `cancelAtPeriodEnd = true`
   - Remains active until `currentPeriodEnd`
   - Account downgraded to FREE at period end

4. **Past Due**: Payment fails
   - Status: `past_due`
   - Account remains unchanged (grace period)
   - Stripe retries payment

5. **Deleted**: Subscription deleted in Stripe
   - Status: `cancelled`
   - Account downgraded to FREE immediately

### Payment Status Transitions

- `pending` → `succeeded` (payment completed)
- `pending` → `failed` (payment failed)
- `succeeded` → `refunded` (refund processed)

---

## Synchronization Strategy

### Account ↔ Subscription Synchronization

**Webhook Events Update Account**:
- `customer.subscription.created` → Create Subscription, update Account.planType
- `customer.subscription.updated` → Update Subscription, sync Account.planType
- `customer.subscription.deleted` → Delete Subscription, downgrade Account to FREE

**Manual Account Changes Update Stripe**:
- Admin changes Account.planType → Update Stripe subscription via API
- User upgrades via Checkout → Stripe creates subscription → Webhook updates Account

**Source of Truth**:
- **Billing**: Stripe is source of truth (subscriptions, payments)
- **Display**: Account.planType is source of truth for UI (synced from Stripe)

---

## Query Patterns

### Common Queries

1. **Get user's active subscription**:
   ```php
   $subscription = $user->getSubscription();
   ```

2. **Get user's payment history**:
   ```php
   $payments = $paymentRepository->findBy(['user' => $user], ['paidAt' => 'DESC']);
   ```

3. **Check if webhook event already processed**:
   ```php
   $processed = $processedWebhookEventRepository->findOneBy(['stripeEventId' => $eventId]);
   ```

4. **Find all active subscriptions expiring soon**:
   ```php
   $expiringSoon = $subscriptionRepository->findByStatusAndPeriodEnd('active', $date);
   ```

5. **Get Stripe customer for user**:
   ```php
   $stripeCustomer = $user->getStripeCustomer();
   ```

---

## Notes

- StripeCustomer is created lazily when first payment is initiated
- Payment records persist even after subscription cancellation (audit trail)
- Subscription is deleted when cancelled (or marked as cancelled, depending on implementation)
- Account.planType is synchronized with Subscription.planType via webhooks
- All Stripe IDs are stored as strings (Stripe format: `cus_xxx`, `sub_xxx`, `pi_xxx`)

