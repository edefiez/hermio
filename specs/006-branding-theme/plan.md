# Implementation Plan: Branding & Theme (Pro / Enterprise)

**Branch**: `006-branding-theme` | **Date**: December 10, 2025 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/006-branding-theme/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature implements account-level branding customization for Pro and Enterprise subscription plans, allowing users to configure brand colors, upload logos, and (for Enterprise) customize templates for their public-facing digital card pages. The system applies branding configurations to all public card pages (`/c/<slug>`) while maintaining plan-based access restrictions and graceful degradation when plans are downgraded.

**Technical Approach**: 
- Create `AccountBranding` entity with OneToOne relationship to `Account` entity
- Implement `BrandingService` for managing branding configurations and applying them to public card pages
- Build file upload handling for logo assets using Symfony's file upload component
- Create branding configuration interface accessible to Pro/Enterprise users only
- Implement template resolution system that applies custom templates for Enterprise accounts
- Integrate branding data into public card page rendering via Twig variables and dynamic styling
- Handle plan downgrades by disabling features while preserving configuration data

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.0  
**Primary Dependencies**: 
- Doctrine ORM 3.x (AccountBranding entity, relationships, migrations)
- Symfony Security Bundle (plan-based access control)
- Symfony Form Bundle (branding configuration forms)
- Symfony Validator (color format, file upload validation)
- Symfony Filesystem Component (logo file storage)
- Twig 3.x (template rendering with branding variables, template inheritance)
- Symfony Translation (i18n for branding interface)

**Storage**: 
- Doctrine ORM with PostgreSQL/MySQL (branding configuration data)
- Filesystem storage for logo assets (local filesystem, configurable path)

**Testing**: PHPUnit 10+ with Symfony Test framework  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend)  
**Performance Goals**: 
- Branding configuration page loads in < 1 second (SC-001)
- Branding changes reflect on public card pages within 5 seconds (SC-003)
- Public card pages load with branding applied in < 2 seconds (SC-006)
- Template customization applies within 10 seconds for Enterprise users (SC-005)

**Constraints**: 
- MUST follow Symfony architecture: Controllers → Services → Repositories
- MUST use Doctrine ORM for all database operations
- MUST enforce plan-based access control at service layer (Pro/Enterprise for colors/logo, Enterprise only for templates)
- MUST use Twig templates exclusively (no React/Vue/Svelte)
- MUST follow PSR-12 coding standards
- MUST use Symfony Security for authentication (ROLE_USER required for branding configuration)
- MUST support internationalization (EN/FR) for all user-facing messages
- MUST validate color formats (hex codes) and file uploads (format, size limits)
- MUST handle plan downgrades gracefully (preserve data, disable features)
- MUST apply branding only to public card pages (`/c/<slug>`), not authenticated dashboard pages
- Logo files MUST be stored securely with proper file validation
- Custom templates MUST maintain template inheritance structure

**Scale/Scope**: 
- Support for Pro and Enterprise account owners (assumes Feature 003 is complete)
- Branding configurations are account-scoped (one per account)
- Public card pages accessible to unlimited viewers with branding applied
- Logo file storage with size limits (max 5MB, formats: PNG, JPG, JPEG, SVG)
- Template customization limited to Enterprise accounts only

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gate ✅

- **Controller → Service → Repository**: Branding management logic will be in `BrandingService`, database access via `AccountBrandingRepository`, file handling in service layer
- **No business logic in controllers**: Controllers will only handle HTTP requests/responses and delegate to services
- **Dependency injection**: All services will be injected via constructor
- **Public route handling**: Public card controller will retrieve branding via service and pass to Twig template

### Frontend Gate ✅

- **Twig-only rendering**: All branding configuration pages and public card pages will use Twig templates
- **No React/Vue/Svelte**: Confirmed - using Twig exclusively
- **Webpack Encore**: Existing asset pipeline will be used for branding interface styling
- **No business logic in templates**: Templates will only display data passed from controllers, branding applied via CSS variables or inline styles

### ORM Gate ✅

- **Doctrine ORM**: AccountBranding entity will use Doctrine ORM with proper relationship to Account
- **Migrations**: Schema changes will be managed via Doctrine migrations
- **Repository pattern**: Custom repository for AccountBranding queries (findByAccount, etc.)
- **OneToOne relationship**: AccountBranding has OneToOne relationship with Account (nullable, only for Pro/Enterprise)

### Security Gate ✅

- **Symfony Security**: Branding configuration routes require ROLE_USER authentication
- **Plan-based access**: Service layer validates plan type (Pro/Enterprise) before allowing branding configuration
- **Enterprise-only features**: Template customization restricted to Enterprise plan in service layer
- **CSRF protection**: All form submissions protected with CSRF tokens
- **File upload security**: Logo uploads validated for type, size, and sanitized filenames

### i18n Gate ✅

- **Symfony Translation**: All user-facing messages will use translation keys
- **EN/FR support**: Translation files will be created for branding-related messages
- **Public pages**: Public card pages support internationalization based on locale

### Coding Standards Gate ✅

- **PSR-12**: All code will follow PSR-12 coding standards
- **Strong typing**: All methods will use type hints (parameters and return types)
- **Descriptive names**: Clear, descriptive method and variable names
- **Proper namespaces**: Code organized in appropriate namespaces

## Project Structure

### Documentation (this feature)

```text
specs/006-branding-theme/
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
│   │   └── AccountBranding.php          # New entity for branding configuration
│   ├── Repository/
│   │   └── AccountBrandingRepository.php # Repository for AccountBranding queries
│   ├── Service/
│   │   ├── BrandingService.php          # Core branding management service
│   │   └── TemplateResolverService.php   # Template resolution for Enterprise custom templates
│   ├── Form/
│   │   └── BrandingFormType.php         # Form for branding configuration
│   └── Controller/
│       └── BrandingController.php        # Controller for branding configuration interface
│
├── templates/
│   ├── branding/
│   │   └── configure.html.twig          # Branding configuration page
│   └── public/
│       └── card.html.twig                # Updated to apply branding (colors, logo, templates)
│
├── migrations/
│   └── Version[timestamp].php           # Migration for account_branding table
│
├── translations/
│   ├── messages.en.yaml                 # Updated with branding translations
│   └── messages.fr.yaml                 # Updated with branding translations
│
└── public/
    └── uploads/
        └── branding/
            └── logos/                    # Directory for stored logo files
```

**Structure Decision**: Single Symfony web application structure. Branding feature integrates into existing application structure following established patterns. Logo files stored in `public/uploads/branding/logos/` directory accessible via web server. Custom templates for Enterprise accounts stored in database or filesystem (to be determined in research phase).

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations - all architecture gates pass. Feature follows standard Symfony patterns.

