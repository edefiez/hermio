# Implementation Plan: Digital Card Management

**Branch**: `005-digital-card` | **Date**: December 8, 2025 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-digital-card/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature implements a digital card management system that allows users to create, manage, and share digital cards via unique URLs and QR codes. Cards are publicly accessible at `/c/<slug>` and subject to quota limits based on subscription plans (Free: 1 card, Pro: 10 cards, Enterprise: unlimited). The system includes card creation, public viewing, QR code generation, and card management (list, edit, delete) functionality.

**Technical Approach**: 
- Create `Card` entity with unique slug generation and ManyToOne relationship to `User`
- Implement `QrCodeService` for generating QR code images using endroid/qr-code library
- Build public card controller with dynamic route `/c/<slug>` for public access
- Create card management interface for logged-in users (create, list, edit, delete)
- Integrate with existing `QuotaService` for quota validation before card creation
- Use Twig templates for styled public card pages and management interfaces

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.0  
**Primary Dependencies**: 
- Doctrine ORM 3.x (Card entity, relationships, migrations)
- Symfony Security Bundle (authentication for card management)
- Symfony Form Bundle (card creation and editing forms)
- Symfony Validator (entity and form validation)
- Twig 3.x (templates for public cards and management)
- endroid/qr-code 5.x (QR code generation library)

**Storage**: Doctrine ORM with PostgreSQL/MySQL  
**Testing**: PHPUnit 10+ with Symfony Test framework  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend)  
**Performance Goals**: 
- Public card pages load in < 2 seconds for 95% of requests (SC-002)
- Card creation completes in < 2 minutes end-to-end (SC-001)
- Card list page loads in < 1 second (SC-007)
- QR code generation completes in < 500ms
- Card edit updates reflect on public page within 5 seconds (SC-006)

**Constraints**: 
- MUST follow Symfony architecture: Controllers → Services → Repositories
- MUST use Doctrine ORM for all database operations
- MUST enforce quota limits at service layer before card creation
- MUST use Twig templates exclusively (no React/Vue/Svelte)
- MUST follow PSR-12 coding standards
- MUST use Symfony Security for authentication (ROLE_USER required for management)
- MUST support internationalization (EN/FR) for all user-facing messages
- MUST generate URL-safe slugs (no special characters, spaces, or conflicts)
- MUST ensure slug uniqueness across all cards
- Public routes `/c/<slug>` MUST be accessible without authentication

**Scale/Scope**: 
- Support for all registered users (assumes Features 002, 003 are complete)
- Cards subject to quota limits: Free (1), Pro (10), Enterprise (unlimited)
- Public card pages accessible to unlimited viewers
- QR codes generated on-demand (not stored)
- Card management limited to card owners

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gate ✅

- **Controller → Service → Repository**: Card management logic will be in `CardService`, quota validation in `QuotaService`, database access via `CardRepository`
- **No business logic in controllers**: Controllers will only handle HTTP requests/responses and delegate to services
- **Dependency injection**: All services will be injected via constructor
- **Public route handling**: Public card controller will use thin controller pattern, delegating to service for card retrieval

### Frontend Gate ✅

- **Twig-only rendering**: All card pages (public and management) will use Twig templates
- **No React/Vue/Svelte**: Confirmed - using Twig exclusively
- **Webpack Encore**: Existing asset pipeline will be used for card page styling
- **No business logic in templates**: Templates will only display data passed from controllers

### ORM Gate ✅

- **Doctrine ORM**: Card entity will use Doctrine ORM with proper relationships to User
- **Migrations**: Schema changes will be managed via Doctrine migrations
- **Repository pattern**: Custom repository for Card queries (findBySlug, findByUser, etc.)
- **Slug uniqueness**: Enforced via database unique constraint and application-level validation

### Security Gate ✅

- **Symfony Security**: Card management routes require ROLE_USER authentication
- **Public access**: Public card routes `/c/<slug>` accessible without authentication (as per spec FR-005)
- **Ownership validation**: Card edit/delete operations validate user ownership in service layer
- **CSRF protection**: All form submissions protected with CSRF tokens

### i18n Gate ✅

- **Symfony Translation**: All user-facing messages will use translation keys
- **EN/FR support**: Translation files will be created for card-related messages
- **Public pages**: Public card pages support internationalization based on locale

### Coding Standards Gate ✅

- **PSR-12**: All code will follow PSR-12 standards
- **Strong typing**: All methods will have proper type hints
- **Symfony conventions**: Following Symfony directory structure and naming conventions

**Status**: ✅ All gates passed - No violations detected

## Project Structure

### Documentation (this feature)

```text
specs/005-digital-card/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── src/
│   ├── Entity/
│   │   ├── Card.php                    # New: Card entity with slug and user relationship
│   │   └── User.php                    # Modified: Add OneToMany relationship to Card
│   ├── Repository/
│   │   └── CardRepository.php          # New: Custom repository for Card queries
│   ├── Service/
│   │   ├── CardService.php             # New: Card CRUD operations and business logic
│   │   ├── QrCodeService.php           # New: QR code generation service
│   │   └── QuotaService.php            # Modified: Update to use CardRepository
│   ├── Controller/
│   │   ├── CardController.php          # New: User-facing card management (create, list, edit, delete)
│   │   └── PublicCardController.php    # New: Public card viewing at /c/<slug>
│   ├── Form/
│   │   └── CardFormType.php            # New: Form for card creation and editing
│   └── EventSubscriber/
│       └── QuotaExceptionSubscriber.php # Modified: Handle quota errors for card creation
│   ├── templates/
│   │   ├── card/
│   │   │   ├── index.html.twig         # New: Card list page
│   │   │   ├── create.html.twig        # New: Card creation form
│   │   │   ├── edit.html.twig          # New: Card editing form
│   │   │   └── show.html.twig          # New: Card details page
│   │   └── public/
│   │       └── card.html.twig          # New: Public card page at /c/<slug>
│   └── migrations/
│       └── Version[timestamp].php      # New: Migration for cards table
└── translations/
    ├── messages.en.yaml                # Modified: Add card-related translations
    └── messages.fr.yaml                # Modified: Add card-related translations
```

**Structure Decision**: Single Symfony web application following existing project structure. New entities, services, controllers, and templates will be added to existing directories following Symfony conventions. Public card routes will be handled by a separate controller to maintain clear separation between authenticated and public routes.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations detected - all gates passed.
