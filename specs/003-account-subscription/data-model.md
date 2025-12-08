# Data Model: Account Management / Subscription Model

**Date**: December 8, 2025  
**Feature**: Account Management / Subscription Model  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The subscription system introduces one new entity (`Account`) that extends the existing `User` entity with subscription plan and quota management capabilities. The Account entity maintains a one-to-one relationship with User, ensuring each user has exactly one account record.

## Entities

### 1. Account Entity

**Purpose**: Represents a user's subscription plan and quota management

```php
namespace App\Entity;

use App\Enum\PlanType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'user_account_unique', columns: ['user_id'])]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'account')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PlanType::class)]
    #[Assert\NotBlank]
    private PlanType $planType = PlanType::FREE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $updatedBy = null; // Email of admin who last modified

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
- `planType`: Enum value (FREE, PRO, ENTERPRISE) - defaults to FREE
- `createdAt`: Timestamp when account was created
- `updatedAt`: Timestamp when plan was last modified (nullable)
- `updatedBy`: Email of administrator who last modified the plan (nullable, for audit trail)

**Validation Rules**:
- `planType`: Must be valid PlanType enum value, cannot be null
- `user`: Required, must be unique (one account per user)
- `createdAt`: Automatically set on creation
- `updatedAt`: Automatically set on update

**Business Rules**:
- Every user must have exactly one account
- Default plan type is FREE for new users
- Plan changes are tracked with timestamp and admin email
- Account is deleted when user is deleted (CASCADE)

**Quota Logic**:
- Quota limits are determined by PlanType enum methods, not stored in database
- FREE plan: 1 card limit
- PRO plan: 10 card limit  
- ENTERPRISE plan: unlimited (null limit)
- Current usage is calculated dynamically by counting Card entities (not stored)

---

### 2. PlanType Enum

**Purpose**: Type-safe representation of subscription plan types with quota logic

```php
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
```

**Enum Values**:
- `FREE`: Free tier plan (1 card limit)
- `PRO`: Pro tier plan (10 card limit)
- `ENTERPRISE`: Enterprise tier plan (unlimited cards)

**Methods**:
- `getQuotaLimit()`: Returns the card limit for the plan (null for unlimited)
- `isUnlimited()`: Checks if plan has unlimited quota
- `getDisplayName()`: Returns human-readable plan name

---

### 3. User Entity (Modified)

**Purpose**: Existing User entity extended with Account relationship

**New Relationship**:
```php
#[ORM\OneToOne(targetEntity: Account::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
private ?Account $account = null;

public function getAccount(): ?Account
{
    return $this->account;
}

public function setAccount(Account $account): static
{
    $this->account = $account;
    $account->setUser($this);
    return $this;
}
```

**Changes**:
- Add `OneToOne` relationship to Account entity
- Add getter/setter methods for account
- Relationship is bidirectional (Account owns the foreign key)

---

## Entity Relationships

### Relationship Map

```
User (1) ←→ (1) Account
```

**Relationship Details**:
- **Type**: OneToOne bidirectional
- **Owning Side**: Account (contains `user_id` foreign key)
- **Inverse Side**: User (has `account` property)
- **Cascade**: Account is persisted/removed with User
- **On Delete**: CASCADE (account deleted when user deleted)
- **Unique**: Yes (one account per user)

### Foreign Key Constraints

- `accounts.user_id` → `users.id`
- Foreign key is NOT NULL and UNIQUE
- ON DELETE CASCADE (account deleted when user deleted)

---

## Database Schema

### accounts Table

```sql
CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    plan_type VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    updated_by VARCHAR(180) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_plan_type (plan_type)
);
```

**Columns**:
- `id`: Primary key
- `user_id`: Foreign key to users table (unique, not null)
- `plan_type`: Enum value ('free', 'pro', 'enterprise')
- `created_at`: Account creation timestamp
- `updated_at`: Last plan modification timestamp (nullable)
- `updated_by`: Email of admin who made last change (nullable)

**Indexes**:
- Primary key on `id`
- Unique index on `user_id`
- Index on `plan_type` for filtering queries

---

## Database Indexes

### Primary Indexes

- `accounts.id`: Primary key
- `accounts.user_id`: Unique foreign key index

### Secondary Indexes

- `accounts.plan_type`: For filtering users by plan type (admin queries)
- `accounts.created_at`: For analytics and reporting
- `accounts.updated_at`: For tracking recent plan changes

### Composite Indexes

- None required for MVP (can be added later if query patterns emerge)

---

## Data Validation

### Entity-Level Validation

Account entity uses Symfony Validator constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
private PlanType $planType = PlanType::FREE;
```

**Validation Rules**:
- `planType`: Must be valid PlanType enum value, cannot be null
- `user`: Required (enforced by database NOT NULL constraint)
- `createdAt`: Automatically set, always valid
- `updatedAt`: Optional, validated as DateTime if present
- `updatedBy`: Optional, max 180 characters (matches email max length)

### Business Logic Validation

**Quota Validation** (handled by QuotaService, not entity):
- Quota limits are enforced at service layer before content creation
- Validation checks current card count against plan's quota limit
- Throws `QuotaExceededException` if limit would be exceeded

**Plan Change Validation** (handled by AccountService):
- Prevents downgrades if user has more content than new plan allows
- Admin can override with confirmation
- Validates plan type is valid enum value

---

## State Transitions

### Account Lifecycle

1. **Creation**: Account created automatically when User registers
   - Default plan: FREE
   - `createdAt` set to current timestamp
   - `updatedAt` and `updatedBy` are null

2. **Plan Upgrade**: Admin changes plan from lower tier to higher tier
   - `planType` updated to new plan
   - `updatedAt` set to current timestamp
   - `updatedBy` set to admin's email
   - Quota limit increases immediately

3. **Plan Downgrade**: Admin changes plan from higher tier to lower tier
   - Validation checks if user has more content than new plan allows
   - If validation passes or admin overrides: `planType` updated
   - `updatedAt` and `updatedBy` updated
   - Quota limit decreases immediately

4. **Deletion**: Account deleted when User is deleted (CASCADE)

### Plan Type Transitions

**Allowed Transitions**:
- FREE → PRO (upgrade)
- FREE → ENTERPRISE (upgrade)
- PRO → ENTERPRISE (upgrade)
- PRO → FREE (downgrade, requires validation)
- ENTERPRISE → PRO (downgrade, requires validation)
- ENTERPRISE → FREE (downgrade, requires validation)

**Validation Rules**:
- Upgrades: Always allowed
- Downgrades: Only allowed if user's current card count ≤ new plan's quota limit, or admin confirms override

---

## Quota Usage Calculation

### Current Implementation

Quota usage is calculated dynamically by counting Card entities:

```php
// Pseudo-code (assumes Card entity exists)
public function getCurrentUsage(User $user): int
{
    return $this->cardRepository->count(['user' => $user]);
}
```

**Assumptions**:
- Card entity exists with `user` relationship
- Count query is optimized by database
- Usage is calculated on-demand (not cached)

### Future Optimizations

If performance becomes an issue:
- Add `card_count` field to Account entity (denormalized)
- Update count via event listeners when cards are created/deleted
- Use database triggers for consistency
- Cache count in Redis for high-traffic scenarios

**For MVP**: Dynamic calculation is sufficient and ensures accuracy.

---

## Migration Strategy

### Initial Migration

```php
// Migration: Create accounts table and add relationship
public function up(Schema $schema): void
{
    $table = $schema->createTable('accounts');
    $table->addColumn('id', 'integer', ['autoincrement' => true]);
    $table->addColumn('user_id', 'integer', ['notnull' => true]);
    $table->addColumn('plan_type', 'string', ['length' => 20, 'notnull' => true]);
    $table->addColumn('created_at', 'datetime', ['notnull' => true]);
    $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
    $table->addColumn('updated_by', 'string', ['length' => 180, 'notnull' => false]);
    $table->setPrimaryKey(['id']);
    $table->addUniqueIndex(['user_id']);
    $table->addIndex(['plan_type']);
    $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
}
```

### Data Migration

After creating accounts table:
1. Create Account record for all existing users with FREE plan
2. Set `created_at` to user's `created_at` timestamp
3. Set `updated_at` and `updated_by` to null

```php
// Data migration script
$users = $userRepository->findAll();
foreach ($users as $user) {
    if ($user->getAccount() === null) {
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType(PlanType::FREE);
        $account->setCreatedAt($user->getCreatedAt());
        $entityManager->persist($account);
    }
}
$entityManager->flush();
```

---

## Query Patterns

### Common Queries

1. **Get user's account and plan**:
   ```php
   $account = $user->getAccount();
   $planType = $account->getPlanType();
   $quotaLimit = $planType->getQuotaLimit();
   ```

2. **Find all users with specific plan**:
   ```php
   $accounts = $accountRepository->findBy(['planType' => PlanType::PRO]);
   ```

3. **Get account with user (eager loading)**:
   ```php
   $account = $accountRepository->findOneBy(['user' => $user]);
   ```

4. **Admin: List all accounts with plan types**:
   ```php
   $accounts = $accountRepository->findAll();
   // Or with pagination for large datasets
   ```

---

## Notes

- Account entity is lightweight and focused on plan management
- Quota limits are not stored in database (calculated from PlanType enum)
- Current usage is calculated dynamically (not stored)
- Plan changes are audited with timestamps and admin email
- Relationship with User is strict one-to-one (enforced by unique constraint)
- Account is automatically created for new users during registration

