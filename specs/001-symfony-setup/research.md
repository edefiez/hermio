# Research: Initial Project Infrastructure Setup

**Feature**: 001-symfony-setup  
**Date**: 2025-12-07  
**Phase**: 0 - Research & Technology Selection

## Overview

This research document consolidates technology decisions for the Symfony 8 infrastructure setup. Since this is an infrastructure feature establishing the foundation mandated by the project constitution, most technology choices are pre-determined by constitutional requirements rather than being research outcomes.

## Technology Decisions

### Decision 1: PHP Version

**Decision**: PHP 8.4+

**Rationale**:
- Symfony 8 requires PHP 8.4 minimum
- PHP 8.4 provides improved performance and type system enhancements
- Long-term support until December 2025
- Better compatibility with modern Symfony features

**Alternatives Considered**:
- PHP 8.1: Not supported by Symfony 8
- PHP 8.4+: Future versions, but 8.2 is stable baseline

**Constitutional Reference**: Mandated by constitution Section 1 (Symfony 8 requires PHP 8.4+)

---

### Decision 2: Symfony Version

**Decision**: Symfony 8.0 (latest stable)

**Rationale**:
- Latest version with modern features
- Full compatibility with PHP 8.4+
- Best-in-class Doctrine ORM 3.x integration
- Comprehensive security components

**Alternatives Considered**:
- Symfony 7.x: Previous version, but constitution mandates Symfony 8
- Symfony 6.4 LTS: Older, would require migration later

**Constitutional Reference**: Explicitly mandated by constitution title and Section 1

---

### Decision 3: Templating Engine

**Decision**: Twig 3.x

**Rationale**:
- Constitutional mandate - exclusive rendering layer
- Native Symfony integration
- Secure by default (auto-escaping)
- Powerful template inheritance and component system
- Strong translation support

**Alternatives Considered**:
- React/Vue/Svelte: Explicitly forbidden by constitution Section 2
- Blade/Plates: Not Symfony-native

**Constitutional Reference**: Mandated by constitution Section 2

---

### Decision 4: Asset Build Tool

**Decision**: Webpack Encore 5.x

**Rationale**:
- Constitutional mandate - exclusive asset compilation tool
- Official Symfony integration
- Zero-config defaults for common scenarios
- Built-in Stimulus bridge support
- Excellent developer experience with hot-reload

**Alternatives Considered**:
- Vite: Modern and fast, but explicitly forbidden by constitution Section 2
- Parcel: Simpler config, but forbidden by constitution
- Laravel Mix: Not Symfony-native, forbidden by constitution

**Constitutional Reference**: Mandated by constitution Section 2 - "exclusive asset compilation tool"

---

### Decision 5: Frontend Progressive Enhancement

**Decision**: Stimulus (Hotwire)

**Rationale**:
- Symfony UX official solution
- Minimal JavaScript, HTML-centric approach
- Perfect for server-rendered Twig templates
- Auto-registration of controllers
- Aligns with "Twig-first" philosophy

**Alternatives Considered**:
- Alpine.js: Good lightweight option, but Stimulus is Symfony-native
- jQuery: Outdated approach
- Vanilla JS: More code to maintain

**Constitutional Reference**: Encouraged by constitution Section 2 (Symfony UX)

---

### Decision 6: ORM & Database Layer

**Decision**: Doctrine ORM 3.x with PostgreSQL

**Rationale**:
- Constitutional mandate - "single source of truth"
- Industry standard for Symfony applications
- Advanced mapping capabilities
- Migrations system for schema versioning
- Repository pattern built-in
- PostgreSQL chosen for robust features (JSON support, full-text search, etc.)

**Alternatives Considered**:
- Raw PDO/SQL: Explicitly discouraged by constitution Section 3
- MySQL: Valid alternative, but PostgreSQL more feature-rich
- MongoDB: Not relational, incompatible with Doctrine ORM mandate

**Constitutional Reference**: Mandated by constitution Section 3

---

### Decision 7: Security Infrastructure

**Decision**: Symfony Security Bundle with PasswordHasher

**Rationale**:
- Constitutional mandate
- Comprehensive authentication/authorization system
- Role-based access control (RBAC)
- Secure password hashing algorithms
- Voters for complex permissions

**Alternatives Considered**:
- Custom security: Reinventing wheel, violates constitution
- Third-party auth libraries: Unnecessary, Symfony Security sufficient

**Constitutional Reference**: Mandated by constitution Section 4

---

### Decision 8: Internationalization

**Decision**: Symfony Translation Component with YAML files

**Rationale**:
- Constitutional mandate for EN/FR support
- Native Symfony integration
- YAML format for readability
- ICU MessageFormat support
- Easy integration with Twig

**Alternatives Considered**:
- JSON translation files: Less readable for complex messages
- GetText: PHP-native but less Symfony-idiomatic
- Database translations: Over-engineering for static content

**Constitutional Reference**: Mandated by constitution Section 3

---

### Decision 9: Development Environment

**Decision**: Docker Compose with Symfony CLI

**Rationale**:
- Consistent environment across team
- Isolates dependencies
- Easy onboarding for new developers
- Symfony CLI for local dev server with TLS
- Supports multiple services (PHP, PostgreSQL, Redis, etc.)

**Alternatives Considered**:
- XAMPP/MAMP: Platform-specific, not reproducible
- Vagrant: Heavier than Docker
- Bare metal: Dependency conflicts

**Constitutional Reference**: Implicit requirement from constitution focus on team standards

---

### Decision 10: Code Quality Tools

**Decision**: PHP CS Fixer + PHPStan

**Rationale**:
- Enforce PSR-12 coding standards (constitutional requirement)
- Static analysis catches bugs before runtime
- IDE integration for real-time feedback
- Industry standard tools

**Alternatives Considered**:
- PHP_CodeSniffer: Valid but PHP CS Fixer more popular in Symfony ecosystem
- Psalm: Alternative to PHPStan, but PHPStan has better Symfony support

**Constitutional Reference**: PSR-12 mandated by constitution Section 4

---

## Best Practices Summary

### Symfony 8 Best Practices Applied

1. **Dependency Injection**:
   - Use constructor injection for all services
   - Leverage autowiring for automatic DI
   - Type-hint interfaces, not implementations

2. **Controller Design**:
   - Keep controllers thin (< 50 lines per action)
   - Use route attributes for configuration
   - Return Response objects

3. **Service Layer**:
   - Business logic lives in services
   - Services are reusable and testable
   - Use interfaces for flexibility

4. **Repository Pattern**:
   - All database queries through repositories
   - Custom repositories for complex queries
   - Avoid query logic in controllers

5. **Twig Templates**:
   - Use template inheritance (extends)
   - Extract reusable blocks into components
   - No business logic in templates

6. **Asset Management**:
   - Single entrypoint per logical bundle
   - Use Stimulus for JavaScript organization
   - Leverage Encore for optimization

### Webpack Encore Best Practices

1. **Entry Points**:
   - Global: `assets/app.js` and `assets/styles/app.css`
   - Page-specific: `assets/js/pages/[page].js`

2. **Stimulus Controllers**:
   - One controller per file in `assets/controllers/`
   - Use data attributes for configuration
   - Keep controllers focused (single responsibility)

3. **CSS Organization**:
   - SCSS with modular structure
   - Component-scoped styles
   - Use CSS custom properties for theming

### Doctrine Best Practices

1. **Entity Design**:
   - Rich domain models with behavior
   - Use Doctrine annotations/attributes
   - Implement `__toString()` for debugging

2. **Relationships**:
   - Define cascade operations explicitly
   - Use lazy loading by default
   - Eager load when needed (JOIN)

3. **Migrations**:
   - Never edit existing migrations
   - Test migrations up and down
   - Keep migrations atomic

### Security Best Practices

1. **Authentication**:
   - Use LoginFormAuthenticator (out of scope for this feature)
   - Hash passwords with Symfony PasswordHasher
   - Implement CSRF protection

2. **Authorization**:
   - Use `#[IsGranted()]` attribute on controllers
   - Implement Voters for complex rules
   - Define role hierarchy in security.yaml

3. **Input Validation**:
   - Use Symfony Validator
   - Validate in forms and API endpoints
   - Sanitize user input

## Integration Patterns

### Pattern 1: Controller -> Service -> Repository Flow

**Implementation**:
- Controllers call service methods
- Services orchestrate business logic
- Repositories encapsulate queries
- Never skip layers

---

### Pattern 2: Twig Component Architecture

**Implementation**:
- Base layout defines common structure
- Pages extend and fill blocks
- Components are included where needed
- Use Twig namespaces for organization

---

### Pattern 3: Asset Compilation Pipeline

**Implementation**:
- Modify source files in `assets/`
- Run `npm run watch` during development
- Encore generates manifest with hashes
- Twig helpers read manifest for URLs

---

### Pattern 4: Translation Workflow

**Implementation**:
- Extract translatable strings to YAML
- Use ICU format for pluralization
- Fall back to key if translation missing
- Log missing translations in dev

---

## Unresolved Questions

**None** - All technology choices are mandated by constitution or determined by Symfony conventions. This infrastructure setup has no ambiguities requiring further research.

---

## Next Steps: Phase 1 Design

1. Create `data-model.md`: N/A for infrastructure (no domain entities)
2. Generate API contracts: N/A for infrastructure (no API endpoints)
3. Create `quickstart.md`: Developer onboarding guide
4. Update agent context with new technologies

---

**Research Complete**: All decisions documented and justified against constitution requirements.

