# Data Model: Branding & Theme (Pro / Enterprise)

**Date**: December 10, 2025  
**Feature**: Branding & Theme (Pro / Enterprise)  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The branding system introduces one new entity (`AccountBranding`) that represents branding configurations for Pro and Enterprise accounts. AccountBranding maintains a OneToOne relationship with Account, allowing each account to have optional branding configuration. The entity stores brand colors, logo file references, and custom template content (Enterprise only).

## Entities

### 1. AccountBranding Entity

**Purpose**: Represents branding configuration for an account (colors, logo, custom templates)

```php
namespace App\Entity;

use App\Repository\AccountBrandingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountBrandingRepository::class)]
#[ORM\Table(name: 'account_branding')]
#[ORM\UniqueConstraint(name: 'account_branding_unique', columns: ['account_id'])]
#[ORM\HasLifecycleCallbacks]
class AccountBranding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Account::class, inversedBy: 'branding')]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $primaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $secondaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right'],
        message: 'branding.logo.position.invalid'
    )]
    private ?string $logoPosition = 'top-left';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['small', 'medium', 'large'],
        message: 'branding.logo.size.invalid'
    )]
    private ?string $logoSize = 'medium';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customTemplate = null; // Enterprise only

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

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

    // Getters and setters...
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `account`: OneToOne relationship to Account entity (branding owner)
- `primaryColor`: Hex color code for primary brand color (nullable, format: #RRGGBB)
- `secondaryColor`: Hex color code for secondary brand color (nullable, format: #RRGGBB)
- `logoFilename`: Filename of uploaded logo file (nullable, stored in `public/uploads/branding/logos/`)
- `logoPosition`: Logo display position (nullable, enum: top-left, top-center, top-right, center, bottom-left, bottom-center, bottom-right)
- `logoSize`: Logo display size (nullable, enum: small, medium, large)
- `customTemplate`: Custom Twig template content for Enterprise accounts (nullable, TEXT field)
- `createdAt`: Timestamp when branding was created
- `updatedAt`: Timestamp when branding was last modified

**Validation Rules**:
- `primaryColor`: Optional, must match hex color format (#RRGGBB) if provided
- `secondaryColor`: Optional, must match hex color format (#RRGGBB) if provided
- `logoFilename`: Optional, validated at upload time (file type, size)
- `logoPosition`: Optional, must be one of the allowed position values
- `logoSize`: Optional, must be one of the allowed size values
- `customTemplate`: Optional, validated for Twig syntax before saving (Enterprise only)
- `account`: Required, cannot be null, unique (one branding per account)

**Business Rules**:
- Each account can have at most one branding configuration (OneToOne relationship)
- Branding configuration is optional (nullable relationship)
- Only Pro and Enterprise accounts can have branding (enforced at service layer)
- Custom templates are only available for Enterprise accounts (enforced at service layer)
- Logo files are stored in `public/uploads/branding/logos/` directory
- Branding is applied only to public card pages (`/c/<slug>`)
- Plan downgrades preserve branding data but disable features (graceful degradation)

---

### 2. Account Entity (Modified)

**Purpose**: Existing Account entity extended with AccountBranding relationship

**New Relationship**:
```php
#[ORM\OneToOne(targetEntity: AccountBranding::class, mappedBy: 'account', cascade: ['persist', 'remove'])]
private ?AccountBranding $branding = null;

public function getBranding(): ?AccountBranding
{
    return $this->branding;
}

public function setBranding(?AccountBranding $branding): static
{
    if ($branding === null) {
        if ($this->branding !== null) {
            $this->branding->setAccount(null);
        }
        $this->branding = null;
    } else {
        $branding->setAccount($this);
        $this->branding = $branding;
    }
    return $this;
}
```

**Changes**:
- Add `OneToOne` relationship to AccountBranding entity
- Add getter/setter methods for branding
- Relationship is bidirectional (AccountBranding owns the foreign key)
- Relationship is nullable (accounts may not have branding)

---

## Entity Relationships

### Relationship Map

```
Account (1) ←→ (1) AccountBranding
```

**Relationship Details**:
- **Type**: OneToOne bidirectional
- **Owning Side**: AccountBranding (contains `account_id` foreign key)
- **Inverse Side**: Account (has `branding` property)
- **Cascade**: AccountBranding is persisted/removed with Account (CASCADE)
- **On Delete**: CASCADE (branding deleted when account deleted)
- **Cardinality**: One account can have at most one branding configuration
- **Nullable**: Yes (accounts may not have branding)

### Foreign Key Constraints

- `account_branding.account_id` → `accounts.id`
- Foreign key is NOT NULL
- ON DELETE CASCADE (branding deleted when account deleted)
- UNIQUE constraint (one branding per account)

---

## Database Schema

### account_branding Table

```sql
CREATE TABLE account_branding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL UNIQUE,
    primary_color VARCHAR(7) NULL,
    secondary_color VARCHAR(7) NULL,
    logo_filename VARCHAR(255) NULL,
    logo_position VARCHAR(20) NULL,
    logo_size VARCHAR(20) NULL,
    custom_template TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_primary_color (primary_color),
    INDEX idx_secondary_color (secondary_color)
);
```

**Columns**:
- `id`: Primary key
- `account_id`: Foreign key to accounts table (not null, unique)
- `primary_color`: Hex color code (nullable, 7 characters: #RRGGBB)
- `secondary_color`: Hex color code (nullable, 7 characters: #RRGGBB)
- `logo_filename`: Logo filename (nullable, max 255 characters)
- `logo_position`: Logo position enum (nullable, 20 characters)
- `logo_size`: Logo size enum (nullable, 20 characters)
- `custom_template`: Custom Twig template content (nullable, TEXT)
- `created_at`: Branding creation timestamp
- `updated_at`: Last modification timestamp

**Indexes**:
- Primary key on `id`
- Unique index on `account_id` (enforces one branding per account)
- Index on `account_id` (for account lookup)
- Index on `primary_color` (for queries filtering by color, if needed)
- Index on `secondary_color` (for queries filtering by color, if needed)

---

## Database Indexes

### Primary Indexes

- `account_branding.id`: Primary key
- `account_branding.account_id`: Unique index (enforces one branding per account)

### Secondary Indexes

- `account_branding.account_id`: For finding branding by account
- `account_branding.primary_color`: For potential color-based queries
- `account_branding.secondary_color`: For potential color-based queries

**Query Optimization**:
```sql
-- Find branding by account (uses index on account_id)
SELECT * FROM account_branding WHERE account_id = ?;

-- Find accounts with specific color (uses index on primary_color)
SELECT * FROM account_branding WHERE primary_color = ?;
```

---

## Data Validation

### Entity-Level Validation

AccountBranding entity uses Symfony Validator constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Regex(
    pattern: '/^#[0-9A-Fa-f]{6}$/',
    message: 'branding.color.invalid_format'
)]
private ?string $primaryColor = null;

#[Assert\Choice(
    choices: ['top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right'],
    message: 'branding.logo.position.invalid'
)]
private ?string $logoPosition = 'top-left';
```

**Validation Rules**:
- `primaryColor`: Optional, must match hex format (#RRGGBB) if provided
- `secondaryColor`: Optional, must match hex format (#RRGGBB) if provided
- `logoFilename`: Optional, validated at upload time (not entity-level)
- `logoPosition`: Optional, must be one of allowed values if provided
- `logoSize`: Optional, must be one of allowed values if provided
- `customTemplate`: Optional, validated for Twig syntax before saving (service layer)
- `account`: Required (enforced by database NOT NULL constraint)

### Business Logic Validation

**Plan-Based Access Validation** (handled by BrandingService, not entity):
- Only Pro and Enterprise accounts can configure branding
- Template customization restricted to Enterprise accounts only
- Validation throws `AccessDeniedException` if plan doesn't allow feature

**File Upload Validation** (handled by BrandingService):
- Logo file type: PNG, JPG, JPEG, SVG only
- Logo file size: Maximum 5MB
- Filename sanitization: Secure filename generation (random hex + extension)
- File storage: Stored in `public/uploads/branding/logos/` directory

**Template Syntax Validation** (handled by TemplateResolverService):
- Twig syntax validation before saving custom templates
- Check for dangerous functions (exec, system, etc.)
- Ensure template extends base template (for security)

---

## State Transitions

### Branding Lifecycle

1. **Creation**: Branding created by Pro/Enterprise account owner
   - AccountBranding entity created with OneToOne relationship to Account
   - Colors, logo, and template configured
   - `createdAt` and `updatedAt` set to current timestamp
   - Logo file uploaded and stored

2. **Update**: Branding configuration modified by account owner
   - Colors, logo, or template updated
   - `updatedAt` set to current timestamp
   - Old logo file deleted if logo replaced
   - Changes reflected immediately on public card pages

3. **Plan Downgrade**: Account plan changed to lower tier
   - Branding data preserved in database
   - Features disabled based on new plan:
     - Pro → Free: All branding disabled (colors, logo, templates)
     - Enterprise → Pro: Template customization disabled, colors/logo remain
   - Public card pages revert to default styling for disabled features

4. **Plan Upgrade**: Account plan changed to higher tier
   - Existing branding data immediately enabled
   - New features become available (e.g., template customization for Pro → Enterprise)
   - Public card pages reflect all configured branding

5. **Deletion**: Account deleted or branding reset
   - AccountBranding entity deleted (CASCADE)
   - Logo file deleted from filesystem
   - Public card pages revert to default styling

### Feature Availability by Plan

**Free Plan**:
- No branding features available
- Branding configuration page shows upgrade prompt

**Pro Plan**:
- ✅ Brand colors (primary, secondary)
- ✅ Logo upload and configuration
- ❌ Custom template customization

**Enterprise Plan**:
- ✅ Brand colors (primary, secondary)
- ✅ Logo upload and configuration
- ✅ Custom template customization

---

## Logo File Storage

### Storage Structure

```
public/uploads/branding/logos/
├── a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6.png
├── b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7.jpg
└── ...
```

**File Naming Convention**:
- Random hex string (32 characters) + file extension
- Example: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6.png`
- Prevents filename conflicts and directory traversal attacks
- Extension preserved from original file

**File Validation**:
- Allowed formats: PNG, JPG, JPEG, SVG
- Maximum size: 5MB
- MIME type validation
- Filename sanitization

**File Cleanup**:
- Logo files deleted when branding is deleted
- Logo files deleted when logo is replaced
- Logo files deleted when account is deleted (CASCADE)

---

## Color Storage Format

### Hex Color Format

Colors stored as 7-character strings: `#RRGGBB`

**Examples**:
- `#FF5733` (red)
- `#33FF57` (green)
- `#3357FF` (blue)
- `#000000` (black)
- `#FFFFFF` (white)

**Validation**:
- Must start with `#`
- Must be followed by exactly 6 hexadecimal digits (0-9, A-F, a-f)
- Case-insensitive (accepts both uppercase and lowercase)

**Usage in Templates**:
```twig
<style>
    :root {
        --primary-color: {{ branding.primaryColor|default('#007bff') }};
        --secondary-color: {{ branding.secondaryColor|default('#6c757d') }};
    }
</style>
```

---

## Template Storage Format

### Custom Template Content

Custom templates stored as TEXT field in database, containing Twig template code:

```twig
{% extends 'public/card.html.twig' %}

{% block stylesheets %}
    {{ parent() }}
    <style>
        .custom-card {
            background: {{ branding.primaryColor }};
            color: {{ branding.secondaryColor }};
        }
    </style>
{% endblock %}

{% block body %}
    <div class="custom-card">
        {# Custom card content #}
    </div>
{% endblock %}
```

**Template Requirements**:
- Must extend base template (`public/card.html.twig`)
- Can override Twig blocks (stylesheets, body, etc.)
- Can use branding variables (`branding.primaryColor`, `branding.logoFilename`, etc.)
- Must pass Twig syntax validation before saving

**Security Considerations**:
- Validate template syntax before saving
- Check for dangerous functions (exec, system, file operations)
- Ensure template extends base template (prevents arbitrary code execution)
- Sanitize user input in templates

---

## Migration Strategy

### Initial Migration

```php
// Migration: Create account_branding table
public function up(Schema $schema): void
{
    $table = $schema->createTable('account_branding');
    $table->addColumn('id', 'integer', ['autoincrement' => true]);
    $table->addColumn('account_id', 'integer', ['notnull' => true]);
    $table->addColumn('primary_color', 'string', ['length' => 7, 'notnull' => false]);
    $table->addColumn('secondary_color', 'string', ['length' => 7, 'notnull' => false]);
    $table->addColumn('logo_filename', 'string', ['length' => 255, 'notnull' => false]);
    $table->addColumn('logo_position', 'string', ['length' => 20, 'notnull' => false]);
    $table->addColumn('logo_size', 'string', ['length' => 20, 'notnull' => false]);
    $table->addColumn('custom_template', 'text', ['notnull' => false]);
    $table->addColumn('created_at', 'datetime', ['notnull' => true]);
    $table->addColumn('updated_at', 'datetime', ['notnull' => true]);
    $table->setPrimaryKey(['id']);
    $table->addUniqueIndex(['account_id']);
    $table->addIndex(['account_id']);
    $table->addIndex(['primary_color']);
    $table->addIndex(['secondary_color']);
    $table->addForeignKeyConstraint('accounts', ['account_id'], ['id'], ['onDelete' => 'CASCADE']);
}
```

### Data Migration

No data migration needed - this is a new feature with no existing data.

### Directory Creation

Must create logo storage directory:
```bash
mkdir -p public/uploads/branding/logos
chmod 755 public/uploads/branding/logos
```

---

## Query Patterns

### Common Queries

1. **Find branding by account**:
   ```php
   $branding = $accountBrandingRepository->findOneBy(['account' => $account]);
   ```

2. **Find accounts with branding**:
   ```php
   $accounts = $accountRepository->createQueryBuilder('a')
       ->innerJoin('a.branding', 'b')
       ->where('b.primaryColor IS NOT NULL')
       ->getQuery()
       ->getResult();
   ```

3. **Find Enterprise accounts with custom templates**:
   ```php
   $accounts = $accountRepository->createQueryBuilder('a')
       ->innerJoin('a.branding', 'b')
       ->where('a.planType = :enterprise')
       ->andWhere('b.customTemplate IS NOT NULL')
       ->setParameter('enterprise', PlanType::ENTERPRISE)
       ->getQuery()
       ->getResult();
   ```

4. **Count accounts with branding**:
   ```php
   $count = $accountBrandingRepository->count([]);
   ```

---

## Notes

- AccountBranding entity uses OneToOne relationship with Account (one branding per account)
- Branding configuration is optional (nullable relationship)
- Plan-based access enforced at service layer, not database level
- Logo files stored in filesystem, filename reference stored in database
- Custom templates stored as TEXT in database (Enterprise only)
- Plan downgrades preserve data but disable features (graceful degradation)
- Branding applied only to public card pages, not authenticated dashboard pages

