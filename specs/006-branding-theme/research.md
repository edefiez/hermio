# Research: Branding & Theme (Pro / Enterprise)

**Feature**: 006-branding-theme  
**Date**: December 10, 2025  
**Phase**: 0 - Research & Technology Selection

## Overview

This research document consolidates technology decisions for implementing the account-level branding customization system. Since this feature builds on the existing Symfony 8 infrastructure and follows constitutional requirements, most technology choices are pre-determined by the project's architecture standards. Key research areas include file upload handling, template resolution strategies, and color validation approaches.

## Technology Decisions

### Decision 1: File Upload Handling for Logos

**Decision**: Use Symfony's built-in file upload component with Filesystem component for storage

**Rationale**:
- Symfony provides robust file upload handling via `FileType` form field
- Filesystem component handles secure file storage and path management
- No additional dependencies required (part of Symfony core)
- Supports validation (file type, size) via Symfony Validator
- Secure filename generation prevents directory traversal attacks
- Can be extended to cloud storage (S3, etc.) later if needed

**Alternatives Considered**:
- `VichUploaderBundle`: Considered but rejected for MVP - adds complexity, Symfony's built-in solution is sufficient
- Direct file handling: Rejected because less secure and more error-prone
- Cloud storage (S3) from start: Rejected for MVP - can be added later, local filesystem is simpler

**Implementation Pattern**:
```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

// In BrandingService
public function uploadLogo(Account $account, UploadedFile $file): string
{
    // Validate file
    $this->validateLogoFile($file);
    
    // Generate secure filename
    $filename = $this->generateSecureFilename($file);
    $path = $this->getLogoStoragePath() . '/' . $filename;
    
    // Move file
    $file->move($this->getLogoStoragePath(), $filename);
    
    return $filename;
}

private function generateSecureFilename(UploadedFile $file): string
{
    $extension = $file->guessExtension();
    $basename = bin2hex(random_bytes(16));
    return $basename . '.' . $extension;
}
```

**File Storage Structure**:
```
public/uploads/branding/logos/
├── a1b2c3d4e5f6g7h8.png
├── b2c3d4e5f6g7h8i9.jpg
└── ...
```

**Constitutional Reference**: Follows Symfony service pattern (Constitution Section I)

---

### Decision 2: Color Validation and Storage

**Decision**: Store colors as hex codes (strings) with validation via Symfony Validator

**Rationale**:
- Hex codes are standard web format (#RRGGBB)
- Simple string storage in database (VARCHAR)
- Easy to validate with regex pattern
- Directly usable in CSS/HTML
- Supports color picker integration in frontend
- No additional dependencies required

**Alternatives Considered**:
- RGB/RGBA tuples: Rejected because less standard, harder to use in CSS
- Color library (league/color): Considered but rejected for MVP - overkill, regex validation sufficient
- HSL values: Rejected because hex is more standard for web

**Implementation Pattern**:
```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Regex(
    pattern: '/^#[0-9A-Fa-f]{6}$/',
    message: 'branding.color.invalid_format'
)]
private ?string $primaryColor = null;

#[Assert\Regex(
    pattern: '/^#[0-9A-Fa-f]{6}$/',
    message: 'branding.color.invalid_format'
)]
private ?string $secondaryColor = null;
```

**Validation Rules**:
- Format: `#RRGGBB` (6 hex digits after #)
- Case-insensitive (accepts both uppercase and lowercase)
- Optional (nullable) - defaults to null if not configured

**Constitutional Reference**: Follows Symfony Validator patterns (Constitution Section I)

---

### Decision 3: Template Resolution Strategy for Enterprise

**Decision**: Store custom template content in database (TEXT field) and resolve via Twig's template resolver

**Rationale**:
- Database storage allows per-account customization
- Twig supports dynamic template loading via custom loader
- No filesystem management required (simpler deployment)
- Template content can be validated before saving
- Supports template inheritance via Twig's `extends` mechanism
- Can cache compiled templates for performance

**Alternatives Considered**:
- Filesystem storage: Considered but rejected - requires file management, permissions, deployment complexity
- Separate template files per account: Rejected because filesystem management is complex
- Template variables only (no custom templates): Rejected because doesn't meet Enterprise requirement for full customization

**Implementation Pattern**:
```php
// In AccountBranding entity
#[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $customTemplate = null;

// In TemplateResolverService
public function resolveTemplate(Account $account): string
{
    $branding = $this->accountBrandingRepository->findOneByAccount($account);
    
    if ($branding && $branding->getCustomTemplate() && $account->getPlanType() === PlanType::ENTERPRISE) {
        // Use custom template
        return $this->renderCustomTemplate($branding->getCustomTemplate(), $account);
    }
    
    // Use default template
    return 'public/card.html.twig';
}

private function renderCustomTemplate(string $templateContent, Account $account): string
{
    // Validate template syntax
    $this->validateTemplateSyntax($templateContent);
    
    // Create temporary template file or use Twig's string loader
    // Apply branding variables
    return $this->twig->createTemplate($templateContent)->render([
        'account' => $account,
        'branding' => $account->getBranding(),
    ]);
}
```

**Template Validation**:
- Validate Twig syntax before saving
- Check for dangerous functions (exec, system, etc.)
- Ensure template extends base template (for security)

**Constitutional Reference**: Follows Twig-driven frontend pattern (Constitution Section II)

---

### Decision 4: Branding Application Strategy

**Decision**: Apply branding via Twig variables and CSS custom properties (CSS variables)

**Rationale**:
- CSS custom properties allow dynamic color application
- No need to regenerate CSS files
- Logo URL passed as Twig variable
- Template selection handled in controller/service
- Maintains separation of concerns (data vs presentation)
- Performance-friendly (no CSS compilation needed)

**Alternatives Considered**:
- Inline styles: Considered but rejected - CSS variables are cleaner and more maintainable
- Pre-compiled CSS per account: Rejected because requires CSS generation, caching complexity
- Separate CSS files: Rejected because filesystem management complexity

**Implementation Pattern**:
```twig
{# In public/card.html.twig #}
{% set branding = account.branding %}
{% if branding %}
    <style>
        :root {
            --primary-color: {{ branding.primaryColor|default('#007bff') }};
            --secondary-color: {{ branding.secondaryColor|default('#6c757d') }};
        }
    </style>
    
    {% if branding.logoFilename %}
        <img src="{{ asset('uploads/branding/logos/' ~ branding.logoFilename) }}" alt="Logo" class="brand-logo">
    {% endif %}
{% endif %}
```

**CSS Variable Usage**:
```css
.card-header {
    background-color: var(--primary-color);
    color: var(--secondary-color);
}
```

**Constitutional Reference**: Follows Twig-driven frontend pattern (Constitution Section II)

---

### Decision 5: Plan-Based Access Control Strategy

**Decision**: Validate plan type in service layer before allowing branding operations

**Rationale**:
- Service layer is appropriate place for business logic
- Consistent with existing QuotaService pattern
- Can throw clear exceptions for plan restrictions
- Easy to test and maintain
- Follows Symfony architecture patterns

**Alternatives Considered**:
- Controller-level checks: Rejected because violates "thin controllers" principle
- Voter-based authorization: Considered but rejected for MVP - service layer validation is simpler
- Middleware/event listener: Rejected because over-engineered for simple plan checks

**Implementation Pattern**:
```php
// In BrandingService
public function configureBranding(Account $account, array $data): void
{
    // Validate plan access
    if (!$this->canConfigureBranding($account)) {
        throw new AccessDeniedException('Branding is only available for Pro and Enterprise plans');
    }
    
    // Configure branding...
}

private function canConfigureBranding(Account $account): bool
{
    $planType = $account->getPlanType();
    return $planType === PlanType::PRO || $planType === PlanType::ENTERPRISE;
}

public function configureTemplate(Account $account, string $templateContent): void
{
    // Validate Enterprise plan
    if ($account->getPlanType() !== PlanType::ENTERPRISE) {
        throw new AccessDeniedException('Template customization is only available for Enterprise plans');
    }
    
    // Configure template...
}
```

**Constitutional Reference**: Follows service layer pattern (Constitution Section I)

---

### Decision 6: Plan Downgrade Handling Strategy

**Decision**: Preserve branding data but disable features based on plan (graceful degradation)

**Rationale**:
- Users may upgrade again, preserving their configuration
- Better user experience than deleting data
- Matches spec requirement: "graceful degradation"
- Can show upgrade prompts when accessing disabled features
- Data cleanup can happen later if needed (data retention policy)

**Alternatives Considered**:
- Delete branding data on downgrade: Rejected because poor UX, users lose configuration
- Keep all features active: Rejected because violates plan restrictions
- Archive branding data: Considered but rejected for MVP - preserving is simpler

**Implementation Pattern**:
```php
// In BrandingService
public function getBrandingForAccount(Account $account): ?AccountBranding
{
    $branding = $this->accountBrandingRepository->findOneByAccount($account);
    
    if (!$branding) {
        return null;
    }
    
    $planType = $account->getPlanType();
    
    // Apply plan restrictions
    if ($planType === PlanType::FREE) {
        // Free plan: no branding
        return null;
    }
    
    if ($planType === PlanType::PRO) {
        // Pro plan: colors and logo only, no custom templates
        $branding->setCustomTemplate(null); // Disable template feature
    }
    
    // Enterprise: full access
    return $branding;
}
```

**Constitutional Reference**: Follows service layer pattern (Constitution Section I)

---

### Decision 7: Logo Display Configuration

**Decision**: Store logo position/size as simple string enum values in database

**Rationale**:
- Simple storage (VARCHAR)
- Easy to validate and apply in templates
- Can be extended later with more options
- No complex configuration needed for MVP
- Directly usable in CSS classes

**Alternatives Considered**:
- JSON configuration: Considered but rejected for MVP - overkill for simple position/size
- Separate LogoConfig entity: Rejected because over-engineered for MVP
- CSS-only positioning: Considered but rejected - users may want control

**Implementation Pattern**:
```php
#[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
#[Assert\Choice(choices: ['top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right'], message: 'branding.logo.position.invalid')]
private ?string $logoPosition = 'top-left';

#[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
#[Assert\Choice(choices: ['small', 'medium', 'large'], message: 'branding.logo.size.invalid')]
private ?string $logoSize = 'medium';
```

**Position Values**: `top-left`, `top-center`, `top-right`, `center`, `bottom-left`, `bottom-center`, `bottom-right`  
**Size Values**: `small`, `medium`, `large`

**Constitutional Reference**: Follows Doctrine ORM patterns (Constitution Section III)

---

## Integration Points

### With Existing Account Entity

- Add `OneToOne` relationship from Account to AccountBranding
- No changes to Account authentication/authorization logic
- Account deletion can cascade to AccountBranding deletion (optional)

### With Existing Plan System

- Plan type validation uses existing `PlanType` enum
- Plan downgrades handled gracefully (preserve data, disable features)
- Plan upgrades immediately enable branding features

### With Public Card Pages (Feature 005)

- Public card controller retrieves branding via BrandingService
- Branding applied to card template via Twig variables
- Logo and colors displayed on public card pages
- Custom templates (Enterprise) override default card template

### With Symfony Security

- Branding configuration routes require ROLE_USER authentication
- Plan-based access enforced at service layer
- File uploads validated for security (type, size, filename)

## Unresolved Dependencies

- **File upload configuration**: Must configure `php.ini` or Symfony config for max upload size (5MB for logos)
- **Logo storage directory**: Must create `public/uploads/branding/logos/` directory with proper permissions
- **Template validation**: Must implement Twig syntax validation (can use Twig's parser)

## Best Practices Applied

1. **Doctrine Relationships**: Using proper OneToOne bidirectional relationship
2. **Service Layer**: All business logic in services, not controllers
3. **File Upload Security**: Secure filename generation, validation, type checking
4. **Plan-Based Access**: Service layer validation with clear exceptions
5. **Internationalization**: All user-facing messages use translation keys
6. **Validation**: Symfony Validator constraints on entity and forms
7. **Migrations**: Schema changes managed via Doctrine migrations
8. **Graceful Degradation**: Preserve data on plan downgrade, disable features

## References

- Symfony 8 Documentation: File Uploads, Filesystem Component
- Doctrine ORM 3.x: OneToOne Relationships, Text Columns
- Twig 3.x: Template Inheritance, Custom Loaders, String Templates
- Project Constitution: Sections I, II, III, IV
- Feature 003 (Account/Subscription): PlanType enum and plan validation patterns
- Feature 005 (Digital Card): Public card page rendering pattern

