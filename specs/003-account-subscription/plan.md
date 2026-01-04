# Implementation Plan: Account Management / Subscription Model

**Branch**: `003-account-subscription` | **Date**: 2025-12-08 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-account-subscription/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature implements a subscription-based account management system that associates each user with a subscription plan (Free, Pro, or Enterprise) and enforces quota limits based on plan type. The system provides user-facing interfaces to view plan details and quota usage, while administrators can manage user accounts and modify subscription plans. Quota enforcement prevents users from exceeding their plan limits when creating content (cards).

**Technical Approach**: 
- Create `Account` entity with one-to-one relationship to `User` entity
- Implement plan types as enum (Free, Pro, Enterprise) with associated quota limits
- Build quota validation service that checks limits before content creation
- Create Twig templates for "My Plan" page and account management interface
- Implement administrative interface for plan management with ROLE_ADMIN authorization

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.0  
**Primary Dependencies**: 
- Doctrine ORM 3.x (entity relationships, migrations)
- Symfony Security Bundle (ROLE_ADMIN authorization)
- Symfony Form Bundle (plan management forms)
- Symfony Validator (entity validation)
- Twig 3.x (templates for plan display and management)

**Storage**: Doctrine ORM with PostgreSQL/MySQL  
**Testing**: PHPUnit 10+ with Symfony Test framework  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend)  
**Performance Goals**: 
- Plan details page loads in < 2 seconds (SC-001)
- Plan changes take effect within 1 second (SC-004)
- Administrator plan modifications complete within 5 seconds (SC-003)
- Quota validation completes in < 100ms per check

**Constraints**: 
- MUST follow Symfony architecture: Controllers → Services → Repositories
- MUST use Doctrine ORM for all database operations
- MUST enforce quota limits at service layer before content creation
- MUST use Twig templates exclusively (no React/Vue/Svelte)
- MUST follow PSR-12 coding standards
- MUST use Symfony Security for ROLE_ADMIN authorization
- MUST support internationalization (EN/FR) for all user-facing messages

**Scale/Scope**: 
- Support for all registered users (assumes Feature 002 is complete)
- Three plan types: Free (1 card), Pro (10 cards), Enterprise (unlimited)
- Administrative interface for managing user accounts
- Real-time quota enforcement during content creation operations

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gate ✅

- **Controller → Service → Repository**: Plan management logic will be in `AccountService`, quota validation in `QuotaService`, database access via `AccountRepository` and `UserRepository`
- **No business logic in controllers**: Controllers will only handle HTTP requests/responses and delegate to services
- **Dependency injection**: All services will be injected via constructor

### Frontend Gate ✅

- **Twig-only rendering**: All plan display and management interfaces will use Twig templates
- **No React/Vue/Svelte**: Confirmed - using Twig exclusively
- **Webpack Encore**: Existing asset pipeline will be used for any additional CSS/JS needed

### ORM Gate ✅

- **Doctrine ORM**: Account entity will use Doctrine ORM with proper relationships
- **Migrations**: Schema changes will be managed via Doctrine migrations
- **Repository pattern**: Custom repositories for Account and User queries

### Security Gate ✅

- **Symfony Security**: ROLE_ADMIN authorization using `#[IsGranted('ROLE_ADMIN')]` attribute
- **Role hierarchy**: Using existing ROLE_USER and ROLE_ADMIN roles
- **No custom auth**: Using existing Symfony Security system

### i18n Gate ✅

- **Symfony Translation**: All user-facing messages will use translation keys
- **EN/FR support**: Translation files will be created for plan-related messages

### Coding Standards Gate ✅

- **PSR-12**: All code will follow PSR-12 standards
- **Strong typing**: All methods will have proper type hints
- **Symfony conventions**: Following Symfony directory structure and naming conventions

**Status**: ✅ All gates passed - No violations detected

## Project Structure

### Documentation (this feature)

```text
specs/003-account-subscription/
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
│   │   ├── Account.php              # New: Account entity with plan type and quota
│   │   └── User.php                 # Modified: Add OneToOne relationship to Account
│   ├── Repository/
│   │   ├── AccountRepository.php    # New: Custom repository for Account queries
│   │   └── UserRepository.php       # Modified: Add methods for admin user management
│   ├── Service/
│   │   ├── AccountService.php       # New: Plan management and account operations
│   │   ├── QuotaService.php         # New: Quota validation and enforcement
│   │   └── UserRegistrationService.php  # Modified: Auto-create Account on registration
│   ├── Controller/
│   │   ├── AccountController.php    # New: User-facing account management
│   │   └── AdminAccountController.php  # New: Admin plan management interface
│   └── Form/
│       └── PlanChangeFormType.php    # New: Form for admin plan changes
├── templates/
│   ├── account/
│   │   ├── index.html.twig          # New: Account management page
│   │   └── my_plan.html.twig        # New: "My Plan" page with quota display
│   └── admin/
│       └── account/
│           └── manage.html.twig     # New: Admin interface for plan management
└── migrations/
    └── Version[timestamp].php        # New: Migration for accounts table and User relationship
```

**Structure Decision**: Single Symfony web application following existing project structure. New entities, services, controllers, and templates will be added to existing directories following Symfony conventions. No new projects or separate applications needed.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations detected - all gates passed.
