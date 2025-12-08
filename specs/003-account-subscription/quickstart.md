# Quickstart Guide: Account Management / Subscription Model

**Feature**: 003-account-subscription  
**Date**: 2025-12-08  
**Target Audience**: Developers implementing this feature

## Overview

This quickstart guide provides step-by-step instructions for implementing the account management and subscription system. Follow these steps in order to build the feature incrementally.

## Prerequisites

- Symfony 8.0+ installed and configured
- Feature 002 (User Account & Authentication) completed
- Database connection configured (PostgreSQL or MySQL)
- Doctrine ORM 3.x installed
- User entity exists with authentication working

## Implementation Steps

### Step 1: Create PlanType Enum

**File**: `app/src/Enum/PlanType.php`

Create the enum that represents subscription plan types:

```php
namespace App\Enum;

enum PlanType: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';

    public function getQuotaLimit(): ?int
    {
        return match($this) {
            self::FREE => 1,
            self::PRO => 10,
            self::ENTERPRISE => null,
        };
    }

    public function isUnlimited(): bool
    {
        return $this->getQuotaLimit() === null;
    }

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

**Verification**: Enum compiles without errors, can be used in type hints.

---

### Step 2: Create Account Entity

**File**: `app/src/Entity/Account.php`

Create the Account entity with OneToOne relationship to User:

```php
namespace App\Entity;

use App\Enum\PlanType;
use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'user_account_unique', columns: ['user_id'])]
#[ORM\HasLifecycleCallbacks]
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
    private ?string $updatedBy = null;

    // Getters, setters, and lifecycle callbacks...
}
```

**Verification**: Entity validates, can be persisted to database.

---

### Step 3: Update User Entity

**File**: `app/src/Entity/User.php`

Add OneToOne relationship to Account:

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

**Verification**: User entity compiles, relationship works bidirectionally.

---

### Step 4: Create Account Repository

**File**: `app/src/Repository/AccountRepository.php`

Create custom repository for Account queries:

```php
namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    // Add custom query methods as needed
}
```

**Verification**: Repository can find accounts, query by plan type.

---

### Step 5: Create Database Migration

**Command**: 
```bash
php bin/console make:migration
```

**File**: `app/migrations/Version[timestamp].php`

Migration should create `accounts` table with proper foreign key to `users` table.

**Apply Migration**:
```bash
php bin/console doctrine:migrations:migrate
```

**Data Migration**: Create Account records for existing users:
```php
// In migration or separate data migration script
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

**Verification**: Migration runs successfully, accounts table exists, existing users have accounts.

---

### Step 6: Create QuotaService

**File**: `app/src/Service/QuotaService.php`

Create service for quota validation:

```php
namespace App\Service;

use App\Entity\User;
use App\Exception\QuotaExceededException;

class QuotaService
{
    public function __construct(
        private CardRepository $cardRepository // Assumes Card entity exists
    ) {
    }

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

        $currentUsage = $this->cardRepository->count(['user' => $user]);
        return ($currentUsage + $quantity) <= $quotaLimit;
    }

    public function validateQuota(User $user, int $quantity = 1): void
    {
        if (!$this->canCreateContent($user, $quantity)) {
            $account = $user->getAccount();
            $limit = $account->getPlanType()->getQuotaLimit();
            $currentUsage = $this->cardRepository->count(['user' => $user]);
            
            throw new QuotaExceededException(
                "Quota limit exceeded. Current usage: {$currentUsage}, Limit: {$limit}"
            );
        }
    }

    public function getCurrentUsage(User $user): int
    {
        return $this->cardRepository->count(['user' => $user]);
    }
}
```

**Verification**: Service validates quota correctly, throws exception when exceeded.

---

### Step 7: Create AccountService

**File**: `app/src/Service/AccountService.php`

Create service for account management:

```php
namespace App\Service;

use App\Entity\Account;
use App\Enum\PlanType;
use App\Repository\AccountRepository;

class AccountService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private QuotaService $quotaService
    ) {
    }

    public function createDefaultAccount(User $user): Account
    {
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType(PlanType::FREE);
        $account->setCreatedAt(new \DateTime());
        
        $this->accountRepository->save($account, true);
        return $account;
    }

    public function changePlan(Account $account, PlanType $newPlan, bool $confirmDowngrade = false): void
    {
        $currentPlan = $account->getPlanType();
        
        // Check if downgrading
        if ($this->isDowngrade($currentPlan, $newPlan)) {
            $user = $account->getUser();
            $currentUsage = $this->quotaService->getCurrentUsage($user);
            $newLimit = $newPlan->getQuotaLimit();
            
            if ($newLimit !== null && $currentUsage > $newLimit && !$confirmDowngrade) {
                throw new \InvalidArgumentException('Cannot downgrade: user exceeds new plan limit');
            }
        }
        
        $account->setPlanType($newPlan);
        $account->setUpdatedAt(new \DateTime());
        // Set updatedBy from security context
        
        $this->accountRepository->save($account, true);
    }

    private function isDowngrade(PlanType $current, PlanType $new): bool
    {
        $order = [PlanType::FREE, PlanType::PRO, PlanType::ENTERPRISE];
        $currentIndex = array_search($current, $order);
        $newIndex = array_search($new, $order);
        
        return $newIndex < $currentIndex;
    }
}
```

**Verification**: Service creates accounts, changes plans, validates downgrades.

---

### Step 8: Update UserRegistrationService

**File**: `app/src/Service/UserRegistrationService.php`

Modify registration to create Account:

```php
public function registerUser(string $email, string $password): User
{
    // ... existing user creation code ...
    
    // Create default account
    $account = $this->accountService->createDefaultAccount($user);
    $user->setAccount($account);
    
    // ... rest of registration ...
}
```

**Verification**: New users automatically get Free plan account.

---

### Step 9: Create Controllers

**Files**: 
- `app/src/Controller/AccountController.php` (user-facing)
- `app/src/Controller/AdminAccountController.php` (admin)

Implement routes as defined in [contracts/routes.md](./contracts/routes.md).

**Verification**: Routes accessible, authorization works, templates render.

---

### Step 10: Create Twig Templates

**Files**:
- `app/templates/account/my_plan.html.twig`
- `app/templates/account/index.html.twig`
- `app/templates/admin/account/index.html.twig`
- `app/templates/admin/account/show.html.twig`

Implement templates with plan display, quota usage, and admin forms.

**Verification**: Templates render correctly, display plan information, forms work.

---

### Step 11: Create Forms

**File**: `app/src/Form/PlanChangeFormType.php`

Create form for admin plan changes as defined in [contracts/forms.md](./contracts/forms.md).

**Verification**: Form validates, submits correctly, shows errors.

---

### Step 12: Add Translations

**Files**:
- `app/translations/messages.en.yaml`
- `app/translations/messages.fr.yaml`

Add translation keys for:
- Plan names
- Quota messages
- Form labels and errors
- Flash messages

**Verification**: Translations work in both EN and FR.

---

### Step 13: Integration Testing

Test the complete flow:
1. User registers → Account created with Free plan
2. User views "My Plan" page → Sees Free plan, quota limit 1
3. User creates card → Quota validated
4. User exceeds quota → Error message displayed
5. Admin changes user plan → Plan updated, quota limit changes
6. Admin downgrades plan → Validation prevents if exceeds quota

**Verification**: All scenarios work as expected.

---

## Common Issues & Solutions

### Issue: Account not created on registration

**Solution**: Ensure `UserRegistrationService` calls `AccountService::createDefaultAccount()` after user creation.

### Issue: Quota validation not working

**Solution**: Ensure `QuotaService` is injected into content creation services and called before creation.

### Issue: Plan change not taking effect immediately

**Solution**: Ensure quota checks query Account entity directly, not cached values.

### Issue: Admin form validation failing

**Solution**: Check that downgrade confirmation logic is implemented correctly in form validation.

---

## Next Steps

After completing implementation:
1. Run tests to verify functionality
2. Review code against constitution requirements
3. Check translations are complete
4. Verify security (authorization, CSRF protection)
5. Test edge cases (downgrades, quota limits)

## References

- [Data Model](./data-model.md) - Entity structure and relationships
- [Routes Contract](./contracts/routes.md) - Route definitions
- [Forms Contract](./contracts/forms.md) - Form specifications
- [Research](./research.md) - Technology decisions

