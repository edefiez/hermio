# Research: Digital Card Management

**Feature**: 005-digital-card  
**Date**: December 8, 2025  
**Phase**: 0 - Research & Technology Selection

## Overview

This research document consolidates technology decisions for implementing the digital card management system. Since this feature builds on the existing Symfony 8 infrastructure and follows constitutional requirements, most technology choices are pre-determined by the project's architecture standards. Key research areas include QR code generation library selection and slug generation strategies.

## Technology Decisions

### Decision 1: QR Code Generation Library

**Decision**: Use `endroid/qr-code` version 5.x for QR code generation

**Rationale**:
- Most popular PHP QR code library (2M+ downloads/month)
- Well-maintained and actively developed
- Symfony-friendly (works seamlessly with Symfony services)
- Supports multiple output formats (PNG, SVG, EPS)
- Good performance and error correction levels
- Simple API: `QrCode::create('data')->writeString()`
- No external dependencies beyond GD or Imagick
- Compatible with PHP 8.4+

**Alternatives Considered**:
- `simplesoftwareio/simple-qrcode`: Rejected because it's a wrapper around BaconQrCode and adds unnecessary abstraction layer
- `chillerlan/php-qrcode`: Rejected because less popular and fewer Symfony integrations
- Custom implementation: Rejected because reinventing the wheel, QR code generation is complex

**Implementation Pattern**:
```php
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

$result = Builder::create()
    ->writer(new PngWriter())
    ->data($url)
    ->encoding(new Encoding('UTF-8'))
    ->size(300)
    ->build();
    
$qrCodeData = $result->getString();
```

**Constitutional Reference**: Follows Symfony service pattern (Constitution Section I)

---

### Decision 2: Slug Generation Strategy

**Decision**: Generate human-readable slugs from card title/name with uniqueness validation and fallback to random string

**Rationale**:
- Human-readable slugs improve SEO and user experience
- Users can remember and share slugs more easily
- Matches assumption: "Card slugs should be human-readable when possible"
- Fallback to random string ensures uniqueness even with conflicts
- URL-safe characters only (alphanumeric + hyphens)

**Alternatives Considered**:
- UUID-based slugs: Rejected because not human-readable and violates assumption
- Random string only: Rejected because violates human-readable assumption
- User-provided slugs: Considered but rejected for MVP - can be added later as enhancement

**Implementation Pattern**:
```php
// Primary: Generate from card name
$slug = $this->slugify($cardName); // "John Doe" -> "john-doe"

// Check uniqueness
if ($this->cardRepository->slugExists($slug)) {
    // Append random suffix
    $slug = $slug . '-' . bin2hex(random_bytes(4)); // "john-doe-a1b2c3d4"
}

// Ensure uniqueness in database (unique constraint)
```

**Slug Generation Rules**:
- Convert to lowercase
- Replace spaces with hyphens
- Remove special characters (keep only alphanumeric and hyphens)
- Trim hyphens from start/end
- Maximum length: 100 characters (database constraint)
- Minimum length: 3 characters

**Constitutional Reference**: Follows Doctrine ORM best practices (Constitution Section III)

---

### Decision 3: Card Entity Design Pattern

**Decision**: Create `Card` entity with ManyToOne relationship to `User` entity

**Rationale**:
- One user can have multiple cards (subject to quota)
- Cards belong to exactly one user (owner)
- Follows standard Doctrine relationship patterns
- Enables efficient queries (find cards by user, count user's cards)
- Supports soft delete pattern (status field) for quota management

**Alternatives Considered**:
- OneToOne relationship: Rejected because users can have multiple cards
- Separate ownership table: Rejected because over-engineered for simple ownership

**Implementation Pattern**:
```php
#[ORM\Entity(repositoryClass: CardRepository::class)]
class Card
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;
    
    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $slug;
    
    // Card content fields...
}
```

**Constitutional Reference**: Follows Doctrine ORM conventions (Constitution Section III)

---

### Decision 4: QR Code Storage Strategy

**Decision**: Generate QR codes on-demand (not stored in database)

**Rationale**:
- QR codes are deterministic (same URL = same QR code)
- Reduces database storage requirements
- QR codes can be regenerated if card slug changes
- Matches spec: "QR codes are generated on-demand and can be regenerated if the card's slug changes"
- Performance acceptable: QR code generation is fast (< 500ms)

**Alternatives Considered**:
- Store QR code images in database: Rejected because unnecessary storage, QR codes are deterministic
- Store QR code images on filesystem: Considered but rejected for MVP - can be optimized later with caching
- Pre-generate and cache: Considered but rejected for MVP - on-demand generation is simpler

**Implementation Pattern**:
```php
// Generate QR code on-demand in controller
$qrCodeService->generateQrCode($card->getPublicUrl());

// Cache in memory for request duration if needed
// Future optimization: Cache to filesystem or Redis
```

**Constitutional Reference**: Follows service layer pattern (Constitution Section I)

---

### Decision 5: Public Route Pattern

**Decision**: Use Symfony dynamic route `/c/{slug}` with custom route parameter validation

**Rationale**:
- Short, memorable URL pattern (`/c/<slug>`)
- Follows Symfony routing conventions
- Easy to validate slug format in route requirements
- Supports SEO-friendly URLs
- Matches spec requirement: "Public page at `/c/<slug>`"

**Alternatives Considered**:
- `/card/{slug}`: Rejected because longer URL, spec specifies `/c/<slug>`
- `/public/card/{slug}`: Rejected because violates spec requirement
- Query parameter approach: Rejected because less SEO-friendly and violates spec

**Implementation Pattern**:
```php
#[Route('/c/{slug}', name: 'app_public_card', requirements: ['slug' => '[a-z0-9-]+'])]
public function show(string $slug): Response
{
    $card = $this->cardRepository->findOneBySlug($slug);
    
    if (!$card) {
        throw $this->createNotFoundException('Card not found');
    }
    
    return $this->render('public/card.html.twig', ['card' => $card]);
}
```

**Constitutional Reference**: Follows Symfony routing conventions (Constitution Section I)

---

### Decision 6: Card Content Structure

**Decision**: Use flexible JSON field for card content with predefined structure

**Rationale**:
- Allows customization of card fields without schema changes
- Supports future expansion (social media links, custom fields, etc.)
- JSON is well-supported in Doctrine and PostgreSQL/MySQL
- Can validate structure via Symfony Validator
- Matches spec: "Card information/content (name, contact details, or other customizable fields)"

**Alternatives Considered**:
- Separate fields for each card property: Rejected because too rigid, requires migrations for new fields
- Separate CardField entity: Rejected because over-engineered for MVP
- Text field with markdown: Considered but rejected because less structured

**Implementation Pattern**:
```php
#[ORM\Column(type: Types::JSON)]
private array $content = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'title' => '',
    'bio' => '',
    'social' => [
        'linkedin' => '',
        'twitter' => '',
    ],
];
```

**Validation**: Use Symfony Validator constraints on content array structure

**Constitutional Reference**: Follows Doctrine ORM best practices (Constitution Section III)

---

### Decision 7: Quota Integration Strategy

**Decision**: Integrate with existing `QuotaService` by updating it to use `CardRepository`

**Rationale**:
- Reuses existing quota validation logic
- Maintains consistency across features
- `QuotaService` already has placeholder for CardRepository
- Matches spec dependency: "Existing QuotaService for quota validation and enforcement"

**Alternatives Considered**:
- Create separate CardQuotaService: Rejected because duplicates logic, violates DRY principle
- Inline quota checks in CardService: Rejected because violates separation of concerns

**Implementation Pattern**:
```php
// Update QuotaService constructor
public function __construct(
    private CardRepository $cardRepository
) {
}

// Update getCurrentUsage method
public function getCurrentUsage(User $user): int
{
    return $this->cardRepository->count(['user' => $user, 'status' => 'active']);
}
```

**Constitutional Reference**: Follows service layer pattern and code reuse (Constitution Section I)

---

### Decision 8: Card Deletion Strategy

**Decision**: Soft delete pattern (status field) with hard delete option

**Rationale**:
- Preserves data integrity (quota calculation, audit trail)
- Allows "undelete" functionality if needed
- Public URLs return 404 immediately (status check)
- Quota decreases immediately (counts only active cards)
- Matches spec: "Deleted cards' public URLs return 404 errors immediately after deletion"

**Alternatives Considered**:
- Hard delete only: Rejected because loses audit trail and complicates quota management
- Archive table: Considered but rejected for MVP - soft delete is simpler

**Implementation Pattern**:
```php
#[ORM\Column(type: Types::STRING, length: 20)]
private string $status = 'active'; // 'active' or 'deleted'

public function delete(): void
{
    $this->status = 'deleted';
    $this->deletedAt = new \DateTime();
}

// Repository queries filter by status
public function findActiveByUser(User $user): array
{
    return $this->findBy(['user' => $user, 'status' => 'active']);
}
```

**Constitutional Reference**: Follows Doctrine ORM patterns (Constitution Section III)

---

## Integration Points

### With Existing User Entity

- Add `OneToMany` relationship from User to Card
- No changes to User authentication/authorization logic
- User deletion cascades to Card deletion (CASCADE)

### With Existing QuotaService

- Update `QuotaService` constructor to inject `CardRepository`
- Update `getCurrentUsage()` to count active cards
- No changes to quota validation logic (already implemented)

### With Existing Account/Plan System

- Quota limits enforced via existing `PlanType` enum
- Card creation validates quota using existing `QuotaService`
- Plan changes immediately affect card creation limits

### With Symfony Security

- Card management routes require ROLE_USER authentication
- Public card routes accessible without authentication
- Card ownership validated in service layer (users can only edit/delete own cards)

## Unresolved Dependencies

- **endroid/qr-code library**: Must be added to `composer.json` dependencies
- **Card content structure**: Initial structure defined, but can be extended based on user feedback

## Best Practices Applied

1. **Doctrine Relationships**: Using proper ManyToOne bidirectional relationship
2. **Service Layer**: All business logic in services, not controllers
3. **Slug Generation**: Human-readable with uniqueness validation
4. **Error Handling**: Custom exceptions for card not found, quota exceeded
5. **Internationalization**: All user-facing messages use translation keys
6. **Validation**: Symfony Validator constraints on Card entity and forms
7. **Migrations**: Schema changes managed via Doctrine migrations
8. **Public Routes**: Separate controller for public access maintains security boundaries

## References

- Symfony 8 Documentation: Dynamic Routes, Route Requirements
- Doctrine ORM 3.x: ManyToOne Relationships, JSON Columns
- endroid/qr-code: Documentation and Examples
- Project Constitution: Sections I, II, III, IV
- Feature 003 (Account/Subscription): QuotaService implementation pattern

