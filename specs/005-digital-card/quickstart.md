# Quickstart Guide: Digital Card Management

**Feature**: 005-digital-card  
**Date**: December 8, 2025  
**Target Audience**: Developers implementing this feature

## Overview

This quickstart guide provides step-by-step instructions for implementing the digital card management system. Follow these steps in order to build the feature incrementally.

## Prerequisites

- Symfony 8.0+ installed and configured
- Feature 002 (User Account & Authentication) completed
- Feature 003 (Account Subscription) completed
- Database connection configured (PostgreSQL or MySQL)
- Doctrine ORM 3.x installed
- User and Account entities exist

## Implementation Steps

### Step 1: Install QR Code Library

**Command**: 
```bash
cd app
composer require endroid/qr-code
```

**Verification**: Library installed, can be imported in PHP code.

---

### Step 2: Create Card Entity

**File**: `app/src/Entity/Card.php`

Create the Card entity with relationships and properties:

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
    private string $status = 'active';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    // Lifecycle callbacks, getters, setters...
}
```

**Verification**: Entity validates, can be persisted to database.

---

### Step 3: Update User Entity

**File**: `app/src/Entity/User.php`

Add OneToMany relationship to Card:

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
```

**Verification**: User entity compiles, relationship works bidirectionally.

---

### Step 4: Create Card Repository

**File**: `app/src/Repository/CardRepository.php`

Create custom repository for Card queries:

```php
namespace App\Repository;

use App\Entity\Card;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findOneBySlug(string $slug): ?Card
    {
        return $this->findOneBy(['slug' => $slug, 'status' => 'active']);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'status' => 'active'], ['createdAt' => 'DESC']);
    }

    public function slugExists(string $slug): bool
    {
        return $this->findOneBy(['slug' => $slug]) !== null;
    }
}
```

**Verification**: Repository can find cards, query by slug and user.

---

### Step 5: Create Database Migration

**Command**: 
```bash
php bin/console make:migration
```

**File**: `app/migrations/Version[timestamp].php`

Migration should create `cards` table with proper foreign key to `users` table.

**Apply Migration**:
```bash
php bin/console doctrine:migrations:migrate
```

**Verification**: Migration runs successfully, cards table exists.

---

### Step 6: Update QuotaService

**File**: `app/src/Service/QuotaService.php`

Update constructor and getCurrentUsage method:

```php
public function __construct(
    private CardRepository $cardRepository
) {
}

public function getCurrentUsage(User $user): int
{
    return $this->cardRepository->count([
        'user' => $user,
        'status' => 'active'
    ]);
}
```

**Verification**: QuotaService correctly counts user's active cards.

---

### Step 7: Create QrCodeService

**File**: `app/src/Service/QrCodeService.php`

Create service for QR code generation:

```php
namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public function generateQrCode(string $data, int $size = 300): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->size($size)
            ->build();
            
        return $result->getString();
    }

    public function generateQrCodeBase64(string $data, int $size = 300): string
    {
        $qrCodeData = $this->generateQrCode($data, $size);
        return 'data:image/png;base64,' . base64_encode($qrCodeData);
    }
}
```

**Verification**: Service generates QR codes correctly, can be tested with sample URL.

---

### Step 8: Create CardService

**File**: `app/src/Service/CardService.php`

Create service for card CRUD operations:

```php
namespace App\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Exception\QuotaExceededException;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;

class CardService
{
    public function __construct(
        private CardRepository $cardRepository,
        private QuotaService $quotaService,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createCard(Card $card, User $user): Card
    {
        // Validate quota
        $this->quotaService->validateQuota($user);
        
        // Generate slug
        $slug = $this->generateUniqueSlug($card->getContent()['name'] ?? 'card');
        $card->setSlug($slug);
        $card->setUser($user);
        $card->setStatus('active');
        
        $this->entityManager->persist($card);
        $this->entityManager->flush();
        
        return $card;
    }

    public function updateCard(Card $card): void
    {
        $this->entityManager->flush();
    }

    public function deleteCard(Card $card): void
    {
        $card->delete();
        $this->entityManager->flush();
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = $this->slugify($name);
        
        if ($this->cardRepository->slugExists($slug)) {
            $slug = $slug . '-' . bin2hex(random_bytes(4));
        }
        
        return $slug;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return substr($slug, 0, 100) ?: 'card-' . bin2hex(random_bytes(4));
    }
}
```

**Verification**: Service creates, updates, and deletes cards correctly.

---

### Step 9: Create CardFormType

**File**: `app/src/Form/CardFormType.php`

Create form for card creation and editing as defined in [contracts/forms.md](./contracts/forms.md).

**Verification**: Form validates, submits correctly, shows errors.

---

### Step 10: Create Controllers

**Files**: 
- `app/src/Controller/CardController.php` (user-facing)
- `app/src/Controller/PublicCardController.php` (public access)

Implement routes as defined in [contracts/routes.md](./contracts/routes.md).

**Verification**: Routes accessible, authorization works, templates render.

---

### Step 11: Create Twig Templates

**Files**:
- `app/templates/card/index.html.twig` (card list)
- `app/templates/card/create.html.twig` (card creation)
- `app/templates/card/edit.html.twig` (card editing)
- `app/templates/card/show.html.twig` (card details)
- `app/templates/card/qr_code.html.twig` (QR code display)
- `app/templates/public/card.html.twig` (public card page)

Implement templates with styled card display and management interfaces.

**Verification**: Templates render correctly, display card information, forms work.

---

### Step 12: Add Translations

**Files**:
- `app/translations/messages.en.yaml`
- `app/translations/messages.fr.yaml`

Add translation keys for:
- Card form labels and placeholders
- Error messages
- Flash messages
- Public card page text

**Verification**: Translations work in both EN and FR.

---

### Step 13: Integration Testing

Test the complete flow:
1. User creates card → Card created with unique slug
2. User views card list → Sees all their cards
3. User edits card → Updates reflected on public page
4. User deletes card → Card soft-deleted, quota decreases
5. Public access to `/c/<slug>` → Card displays correctly
6. QR code generation → QR code links to public URL
7. Quota validation → Prevents creation when limit reached

**Verification**: All scenarios work as expected.

---

## Common Issues & Solutions

### Issue: Slug conflicts during creation

**Solution**: Ensure `generateUniqueSlug()` method checks uniqueness and appends random suffix if needed. Database unique constraint provides final enforcement.

### Issue: Quota validation not working

**Solution**: Ensure `QuotaService` is injected into `CardService` and called before card creation. Verify `CardRepository` is injected into `QuotaService`.

### Issue: Public card page shows deleted cards

**Solution**: Ensure `PublicCardController` filters by `status = 'active'` when finding cards by slug.

### Issue: QR code not generating

**Solution**: Verify `endroid/qr-code` library is installed and `QrCodeService` is properly configured. Check PHP GD or Imagick extension is available.

### Issue: Card content not saving

**Solution**: Ensure form data is properly transformed to Card entity's `content` JSON field. Check form data mapping in `CardFormType`.

---

## Next Steps

After completing implementation:
1. Run tests to verify functionality
2. Review code against constitution requirements
3. Check translations are complete
4. Verify security (authorization, CSRF protection)
5. Test edge cases (quota limits, slug conflicts, deleted cards)
6. Optimize queries if needed (add indexes, eager loading)

## References

- [Data Model](./data-model.md) - Entity structure and relationships
- [Routes Contract](./contracts/routes.md) - Route definitions
- [Forms Contract](./contracts/forms.md) - Form specifications
- [Research](./research.md) - Technology decisions

