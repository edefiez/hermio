# Data Model: Digital Card Management

**Date**: December 8, 2025  
**Feature**: Digital Card Management  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The digital card management system introduces one new entity (`Card`) that represents digital cards created by users. Cards are publicly accessible via unique slugs and subject to quota limits based on user subscription plans. The Card entity maintains a ManyToOne relationship with User, allowing users to create multiple cards (subject to quota).

## Entities

### 1. Card Entity

**Purpose**: Represents a digital card created by a user, accessible via public URL

```php
namespace App\Entity;

use App\Repository\CardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\Table(name: 'cards')]
#[ORM\UniqueConstraint(name: 'card_slug_unique', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'card.slug.invalid_format')]
    private string $slug;

    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    private array $content = [];

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'active'; // 'active' or 'deleted'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function delete(): void
    {
        $this->status = 'deleted';
        $this->deletedAt = new \DateTime();
    }

    public function getPublicUrl(): string
    {
        return '/c/' . $this->slug;
    }

    // Getters and setters...
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `user`: ManyToOne relationship to User entity (card owner)
- `slug`: Unique URL-safe identifier (3-100 characters, alphanumeric + hyphens)
- `content`: JSON field containing card information (name, email, phone, etc.)
- `status`: Card status ('active' or 'deleted') - defaults to 'active'
- `createdAt`: Timestamp when card was created
- `updatedAt`: Timestamp when card was last modified
- `deletedAt`: Timestamp when card was deleted (nullable, soft delete)

**Content Structure** (JSON field):
```php
[
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    'company' => 'Acme Corp',
    'title' => 'Software Engineer',
    'bio' => 'Experienced developer...',
    'social' => [
        'linkedin' => 'https://linkedin.com/in/johndoe',
        'twitter' => 'https://twitter.com/johndoe',
    ],
    'website' => 'https://johndoe.com',
]
```

**Validation Rules**:
- `slug`: Required, unique, 3-100 characters, URL-safe format (a-z0-9-)
- `content`: Required, must be valid JSON array
- `user`: Required, cannot be null
- `status`: Must be 'active' or 'deleted', defaults to 'active'
- `createdAt`: Automatically set on creation
- `updatedAt`: Automatically set on creation and update

**Business Rules**:
- Each card belongs to exactly one user
- Slug must be unique across all cards (enforced by database unique constraint)
- Cards are soft-deleted (status = 'deleted') to preserve quota calculation
- Public URLs are generated from slug: `/c/<slug>`
- Quota validation happens before card creation (enforced by QuotaService)

---

### 2. User Entity (Modified)

**Purpose**: Existing User entity extended with Card relationship

**New Relationship**:
```php
#[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
private Collection $cards;

public function __construct()
{
    $this->cards = new ArrayCollection();
}

public function getCards(): Collection
{
    return $this->cards;
}

public function addCard(Card $card): static
{
    if (!$this->cards->contains($card)) {
        $this->cards->add($card);
        $card->setUser($this);
    }
    return $this;
}

public function removeCard(Card $card): static
{
    if ($this->cards->removeElement($card)) {
        if ($card->getUser() === $this) {
            $card->setUser(null);
        }
    }
    return $this;
}

public function getActiveCards(): Collection
{
    return $this->cards->filter(fn(Card $card) => $card->getStatus() === 'active');
}
```

**Changes**:
- Add `OneToMany` relationship to Card entity
- Add getter/setter methods for cards collection
- Add helper method to filter active cards
- Relationship is bidirectional (Card owns the foreign key)

---

## Entity Relationships

### Relationship Map

```
User (1) ←→ (N) Card
```

**Relationship Details**:
- **Type**: OneToMany bidirectional
- **Owning Side**: Card (contains `user_id` foreign key)
- **Inverse Side**: User (has `cards` collection)
- **Cascade**: Cards are persisted/removed with User (CASCADE)
- **On Delete**: CASCADE (cards deleted when user deleted)
- **Cardinality**: One user can have many cards (subject to quota)

### Foreign Key Constraints

- `cards.user_id` → `users.id`
- Foreign key is NOT NULL
- ON DELETE CASCADE (cards deleted when user deleted)

---

## Database Schema

### cards Table

```sql
CREATE TABLE cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    content JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_user_status (user_id, status)
);
```

**Columns**:
- `id`: Primary key
- `user_id`: Foreign key to users table (not null)
- `slug`: Unique URL-safe identifier (unique, not null, 3-100 characters)
- `content`: JSON field containing card information (not null)
- `status`: Card status ('active' or 'deleted'), defaults to 'active'
- `created_at`: Card creation timestamp
- `updated_at`: Last modification timestamp
- `deleted_at`: Deletion timestamp (nullable, for soft delete)

**Indexes**:
- Primary key on `id`
- Unique index on `slug` (enforces uniqueness)
- Index on `user_id` (for user's card queries)
- Index on `status` (for filtering active/deleted cards)
- Composite index on `(user_id, status)` (for quota calculation queries)

---

## Database Indexes

### Primary Indexes

- `cards.id`: Primary key
- `cards.slug`: Unique index (enforces slug uniqueness)

### Secondary Indexes

- `cards.user_id`: For finding all cards by user
- `cards.status`: For filtering active/deleted cards
- `cards.created_at`: For sorting cards by creation date
- `cards.updated_at`: For tracking recent modifications

### Composite Indexes

- `cards(user_id, status)`: Optimizes quota calculation queries (count active cards per user)

**Query Optimization**:
```sql
-- Quota calculation query uses composite index
SELECT COUNT(*) FROM cards WHERE user_id = ? AND status = 'active';
```

---

## Data Validation

### Entity-Level Validation

Card entity uses Symfony Validator constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Length(min: 3, max: 100)]
#[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'card.slug.invalid_format')]
private string $slug;

#[Assert\NotBlank]
private array $content = [];
```

**Validation Rules**:
- `slug`: Required, 3-100 characters, URL-safe format (a-z0-9-), unique
- `content`: Required, must be valid JSON array
- `user`: Required (enforced by database NOT NULL constraint)
- `status`: Must be 'active' or 'deleted', defaults to 'active'
- `createdAt`: Automatically set, always valid
- `updatedAt`: Automatically set, always valid
- `deletedAt`: Optional, validated as DateTime if present

### Business Logic Validation

**Quota Validation** (handled by QuotaService, not entity):
- Quota limits are enforced at service layer before card creation
- Validation checks current active card count against plan's quota limit
- Throws `QuotaExceededException` if limit would be exceeded
- Only active cards count toward quota (deleted cards don't count)

**Slug Uniqueness Validation** (handled by CardService):
- Slug uniqueness checked before card creation
- If slug exists, append random suffix to ensure uniqueness
- Database unique constraint provides final enforcement

**Content Validation** (handled by CardFormType):
- Required fields: name (at minimum)
- Email format validation if email provided
- URL format validation for social links and website
- Maximum length constraints for text fields

---

## State Transitions

### Card Lifecycle

1. **Creation**: Card created by logged-in user
   - Slug generated from card name (or random if conflict)
   - Status set to 'active'
   - `createdAt` and `updatedAt` set to current timestamp
   - `deletedAt` is null
   - Quota validated before creation

2. **Update**: Card information modified by owner
   - Content updated
   - `updatedAt` set to current timestamp
   - Slug can be regenerated (if user requests)
   - Status remains 'active'

3. **Deletion**: Card deleted by owner
   - Status set to 'deleted'
   - `deletedAt` set to current timestamp
   - `updatedAt` set to current timestamp
   - Card remains in database (soft delete)
   - Public URL returns 404 immediately
   - Quota usage decreases (only active cards count)

4. **Hard Delete**: Card permanently removed (future feature)
   - Card record deleted from database
   - Only for administrative purposes or data retention policies

### Status Transitions

**Allowed Transitions**:
- `active` → `deleted` (user deletes card)
- `deleted` → `active` (future: restore functionality)

**Current Implementation**:
- Only `active` → `deleted` transition supported in MVP
- Restore functionality can be added later if needed

---

## Slug Generation Strategy

### Generation Rules

1. **Primary Method**: Generate from card name/title
   ```php
   $slug = strtolower($cardName);
   $slug = preg_replace('/[^a-z0-9]+/', '-', $slug); // Replace non-alphanumeric with hyphens
   $slug = trim($slug, '-'); // Remove leading/trailing hyphens
   $slug = substr($slug, 0, 100); // Limit length
   ```

2. **Uniqueness Check**: Verify slug doesn't exist
   ```php
   if ($cardRepository->slugExists($slug)) {
       // Append random suffix
       $slug = $slug . '-' . bin2hex(random_bytes(4));
   }
   ```

3. **Fallback**: If card name is empty or invalid, generate random slug
   ```php
   $slug = 'card-' . bin2hex(random_bytes(8));
   ```

### Slug Constraints

- Minimum length: 3 characters
- Maximum length: 100 characters
- Allowed characters: lowercase letters (a-z), numbers (0-9), hyphens (-)
- Must be unique across all cards
- Cannot start or end with hyphen
- Cannot contain consecutive hyphens

---

## Quota Integration

### Quota Calculation

Quota usage is calculated by counting active cards for a user:

```php
// In QuotaService
public function getCurrentUsage(User $user): int
{
    return $this->cardRepository->count([
        'user' => $user,
        'status' => 'active'
    ]);
}
```

**Key Points**:
- Only active cards count toward quota
- Deleted cards don't count (soft delete preserves quota calculation)
- Calculation is dynamic (not cached)
- Uses composite index `(user_id, status)` for performance

### Quota Validation Flow

1. User requests card creation
2. `CardService` calls `QuotaService::validateQuota($user)`
3. `QuotaService` counts user's active cards
4. Compares count + 1 against plan's quota limit
5. If exceeds limit: throws `QuotaExceededException`
6. If within limit: card creation proceeds

---

## Migration Strategy

### Initial Migration

```php
// Migration: Create cards table
public function up(Schema $schema): void
{
    $table = $schema->createTable('cards');
    $table->addColumn('id', 'integer', ['autoincrement' => true]);
    $table->addColumn('user_id', 'integer', ['notnull' => true]);
    $table->addColumn('slug', 'string', ['length' => 100, 'notnull' => true]);
    $table->addColumn('content', 'json', ['notnull' => true]);
    $table->addColumn('status', 'string', ['length' => 20, 'notnull' => true, 'default' => 'active']);
    $table->addColumn('created_at', 'datetime', ['notnull' => true]);
    $table->addColumn('updated_at', 'datetime', ['notnull' => true]);
    $table->addColumn('deleted_at', 'datetime', ['notnull' => false]);
    $table->setPrimaryKey(['id']);
    $table->addUniqueIndex(['slug']);
    $table->addIndex(['user_id']);
    $table->addIndex(['status']);
    $table->addIndex(['user_id', 'status']);
    $table->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
}
```

### Data Migration

No data migration needed - this is a new feature with no existing data.

---

## Query Patterns

### Common Queries

1. **Find card by slug** (public access):
   ```php
   $card = $cardRepository->findOneBy(['slug' => $slug, 'status' => 'active']);
   ```

2. **Find all user's cards**:
   ```php
   $cards = $cardRepository->findBy(['user' => $user, 'status' => 'active']);
   ```

3. **Count user's active cards** (quota calculation):
   ```php
   $count = $cardRepository->count(['user' => $user, 'status' => 'active']);
   ```

4. **Check slug uniqueness**:
   ```php
   $exists = $cardRepository->findOneBy(['slug' => $slug]) !== null;
   ```

5. **Find cards updated recently**:
   ```php
   $cards = $cardRepository->findBy(
       ['user' => $user],
       ['updatedAt' => 'DESC'],
       10 // limit
   );
   ```

---

## Notes

- Card entity uses soft delete pattern (status field) to preserve quota calculation
- Slug uniqueness is enforced at both application and database levels
- Content is stored as JSON for flexibility (can be extended without schema changes)
- Public URLs are generated from slug: `/c/<slug>`
- Quota validation happens before card creation (service layer)
- Only active cards count toward quota limits
- Cards are deleted (CASCADE) when user is deleted

