# Implementation Plan: Initial Project Infrastructure Setup

**Branch**: `001-symfony-setup` | **Date**: 2025-12-07 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-symfony-setup/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This infrastructure feature establishes the foundational technical stack for Hermio project as mandated by the constitution:
- **Symfony 8** framework with PHP 8.4+ for backend architecture
- **Twig** templating engine for server-side rendering
- **Webpack Encore** as exclusive asset compilation tool
- **Stimulus** (Symfony UX) for progressive enhancement JavaScript
- **Docker** development environment for consistency
- Full internationalization (EN/FR) and security foundation

This setup enables all future business features to be built on a solid, standards-compliant foundation following Controllers → Services → Repositories architecture pattern.

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.x (latest stable)  
**Primary Dependencies**: 
- Symfony core bundles (Framework, Twig, Security, Maker, Validator, Form)
- Doctrine ORM 3.x with PostgreSQL/MySQL support
- Symfony UX (Stimulus Bridge, Turbo)
- Webpack Encore 5.x
- Monolog for logging
- PHPUnit for testing

**Storage**: Doctrine ORM with PostgreSQL (primary) or MySQL (alternative)  
**Testing**: PHPUnit 10+ with Symfony Test framework  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend + Webpack assets)  
**Performance Goals**: 
- Development server start < 3 seconds
- Asset compilation (dev mode) < 5 seconds
- Hot-reload asset changes < 3 seconds
- Page load with debug toolbar < 500ms

**Constraints**: 
- MUST follow PSR-12 coding standards
- MUST use Symfony conventions (no custom frameworks)
- MUST separate concerns: Controllers → Services → Repositories
- MUST use dependency injection (no static services)
- Asset pipeline MUST use Webpack Encore exclusively

**Scale/Scope**: 
- Foundation for multi-module application
- Support for 100+ concurrent developers
- ~20 core Symfony bundles configured
- Base structure for 1000+ files long-term

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gates (from constitution Section 1)

- ✅ **Controllers remain thin**: No controllers in this infrastructure setup, guideline documented for future features
- ✅ **Business logic in Services**: Service layer structure prepared in `src/Service/`
- ✅ **Database access via Repositories**: Doctrine repositories structure in `src/Repository/`
- ✅ **Entities in src/Entity/**: Entity directory prepared with Doctrine configuration
- ✅ **Dependency injection everywhere**: Symfony DI container configured, no static services
- ✅ **Controllers → Services → Repositories flow**: Architecture documented and enforced by directory structure

### Frontend Gates (from constitution Section 2)

- ✅ **Twig-driven rendering**: Twig bundle configured as exclusive rendering engine
- ✅ **No React/Vue/Svelte**: Not applicable - infrastructure only, Twig is the mandated solution
- ✅ **No business logic in templates**: Base templates follow display-only pattern
- ✅ **Translations use Symfony Translator**: Translation system configured with `|trans` filter

### ORM Gates (from constitution Section 3)

- ✅ **Doctrine as single source**: Doctrine ORM configured, migrations system enabled
- ✅ **Entities define domain model**: Entity structure prepared with proper annotations
- ✅ **Repositories manage persistence**: Repository pattern established
- ✅ **Migrations for schema changes**: Doctrine Migrations bundle installed and configured

### Security Gates (from constitution Section 4)

- ✅ **security.yaml configuration**: Security bundle configured with role hierarchy
- ✅ **PasswordHasher for credentials**: Password hasher service configured in security.yaml
- ✅ **Role hierarchy defined**: ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN configured
- ℹ️ **Custom Authenticator**: Structure prepared, actual implementation out of scope (per spec)

### Asset Pipeline Gates (from constitution Section 2)

- ✅ **Webpack Encore exclusive**: Encore configured, no Vite/Parcel/Mix
- ✅ **Standard directory structure**: `assets/js/`, `assets/styles/`, `assets/controllers/` created
- ✅ **Stimulus integration**: Symfony UX Stimulus bridge configured
- ✅ **Entrypoint loading via Encore Twig functions**: Templates use `encore_entry_link_tags()` and `encore_entry_script_tags()`
- ✅ **public/build/ in .gitignore**: Build output excluded from version control

### i18n Gates (from constitution Section 3)

- ✅ **Symfony Translation system**: Translation component configured
- ✅ **messages.en.yaml and messages.fr.yaml**: Locale files created
- ✅ **All UI text translatable**: Base templates demonstrate translation pattern

### Coding Standards Gates (from constitution Section 4)

- ✅ **PSR-12 coding style**: PHP CS Fixer configured
- ✅ **Symfony directory structure**: Standard layout enforced
- ✅ **Strong typing**: PHP 8.4 strict types enabled
- ✅ **Proper namespaces**: PSR-4 autoloading configured

### Feature Workflow Gates (from constitution Section 5)

- ✅ **Speckit structure**: This feature follows `.specify/features/<id>-<slug>/` pattern
- ✅ **All required docs**: spec.md, plan.md present; research.md, data-model.md, contracts/, quickstart.md to be generated

**Constitution Compliance**: ✅ **PASS** - All gates satisfied. This infrastructure setup establishes the foundation mandated by constitution.

---

## Post-Phase 1 Constitution Re-Check

*Re-evaluation after research.md, data-model.md, quickstart.md, and contracts/ generation*

### Research Decisions (from research.md)

✅ **All technology choices align with constitution**:
- PHP 8.4+ (Section 1 requirement)
- Symfony 8.0 (Section 1 requirement)
- Twig 3.x (Section 2 requirement)
- Webpack Encore 5.x (Section 2 requirement)
- Stimulus/Hotwire (Section 2 encouragement)
- Doctrine ORM 3.x with PostgreSQL (Section 3 requirement)
- Symfony Security Bundle (Section 4 requirement)
- Translation Component EN/FR (Section 3 requirement)
- Docker Compose (Best practice)
- PHP CS Fixer + PHPStan (Section 4 requirement)

### Design Decisions

✅ **No domain entities introduced** - Appropriate for infrastructure feature
✅ **No API contracts defined** - Appropriate for infrastructure feature
✅ **Quickstart guide created** - Facilitates onboarding per constitution workflow
✅ **Architecture patterns documented** - Controllers → Services → Repositories flow established

### Final Compliance Status

**Status**: ✅ **PASS**

All design decisions remain fully compliant with constitution. No violations introduced during Phase 0 (Research) or Phase 1 (Design).

**Ready for Phase 2**: Task breakdown (via `/speckit.tasks` command)

## Project Structure

### Documentation (this feature)

```text
specs/001-symfony-setup/
├── spec.md                      # Feature specification (completed)
├── plan.md                      # This implementation plan (in progress)
├── checklists/
│   └── requirements.md          # Validation checklist (completed)
├── research.md                  # Phase 0: Technology research (to be generated)
├── data-model.md               # Phase 1: Domain model (N/A for infrastructure)
├── quickstart.md               # Phase 1: Developer onboarding guide (to be generated)
└── contracts/                  # Phase 1: API contracts (N/A for infrastructure)
```

### Source Code (Symfony application root: /app)

```text
app/                            # Symfony application root
├── assets/                     # Webpack Encore assets
│   ├── app.js                  # Main JavaScript entrypoint
│   ├── controllers.json        # Stimulus controllers manifest
│   ├── controllers/            # Stimulus controllers
│   │   └── hello_controller.js
│   ├── styles/
│   │   └── app.css             # Main stylesheet
│   └── bootstrap.js
│
├── bin/
│   ├── console                 # Symfony console
│   └── phpunit                 # PHPUnit test runner
│
├── config/                     # Configuration files
│   ├── bundles.php
│   ├── services.yaml           # DI container config
│   ├── routes.yaml
│   ├── packages/               # Bundle-specific config
│   │   ├── framework.yaml
│   │   ├── twig.yaml
│   │   ├── security.yaml       # Security & roles
│   │   ├── doctrine.yaml       # ORM config
│   │   ├── translation.yaml    # i18n config
│   │   └── webpack_encore.yaml
│   └── routes/
│
├── migrations/                 # Doctrine migrations
│
├── public/                     # Web root
│   ├── index.php              # Front controller
│   └── build/                 # Compiled assets (gitignored)
│
├── src/                        # Application code
│   ├── Kernel.php
│   ├── Controller/            # Controllers (thin)
│   ├── Entity/                # Doctrine entities
│   ├── Repository/            # Doctrine repositories
│   ├── Service/               # Business logic
│   ├── Form/                  # Form types
│   ├── Security/              # Authenticators, voters
│   └── EventSubscriber/       # Event listeners
│
├── templates/                  # Twig templates
│   ├── base.html.twig         # Base layout
│   ├── components/            # Reusable components
│   └── pages/                 # Page templates
│
├── tests/                      # PHPUnit tests
│   ├── bootstrap.php
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
│
├── translations/               # Translation files
│   ├── messages.en.yaml
│   └── messages.fr.yaml
│
├── var/                        # Cache, logs (gitignored)
│   ├── cache/
│   └── log/
│
├── vendor/                     # Composer dependencies (gitignored)
│
├── composer.json              # PHP dependencies
├── composer.lock
├── package.json               # Node dependencies
├── package-lock.json
├── webpack.config.js          # Webpack Encore config
├── symfony.lock               # Symfony Flex recipes
├── phpunit.xml.dist           # PHPUnit configuration
├── .env                       # Environment variables
└── .gitignore
```

**Structure Decision**: Web application structure (Symfony + Twig + Webpack Encore)

This is a **Web application** following Symfony conventions:
- Backend: Symfony 8 PHP application in `/app` directory
- Frontend: Server-side rendered Twig templates with Stimulus for progressive enhancement
- Assets: Webpack Encore compilation pipeline
- No separate frontend project (Twig-driven, not SPA)

The structure follows constitution Section 4 mandatory layout and matches the existing workspace structure observed in `/Users/edefiez/Projects/Hermio/app/`.

## Complexity Tracking

**No violations detected.** 

This infrastructure setup fully complies with all constitution requirements:
- Standard Symfony directory structure (mandated by constitution Section 4)
- Controllers → Services → Repositories pattern (mandated by constitution Section 1)
- Twig-only frontend (mandated by constitution Section 2)
- Webpack Encore exclusive (mandated by constitution Section 2)
- Doctrine ORM (mandated by constitution Section 3)

All architectural decisions are constitution-mandated requirements, not additions of complexity.
