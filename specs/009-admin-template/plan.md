# Implementation Plan: Modern Admin Template for Authenticated Users

**Branch**: `009-admin-template` | **Date**: 2025-12-11 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/009-admin-template/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature implements a modern, responsive admin template layout for all authenticated user pages. The feature replaces the current navbar-based navigation with a left sidebar navigation and top header layout. A new dashboard page serves as the default landing page after login, displaying account overview information in a card-based layout. All existing authenticated pages (My Cards, Account, Settings, Profile, etc.) are migrated to use the new base layout template while maintaining their existing functionality. The layout is fully responsive, supports keyboard navigation, and meets basic accessibility requirements.

**Technical Approach**: 
- Create new base Twig layout template (`base_admin.html.twig`) with sidebar, header, and main content area structure
- Create dashboard controller and template to serve as default landing page for authenticated users
- Migrate all existing authenticated page templates to extend the new base layout
- Implement responsive sidebar with collapse/expand functionality using Bootstrap components
- Add JavaScript for sidebar state persistence (localStorage) and mobile toggle behavior
- Create SCSS stylesheet for admin layout components following existing design system
- Update security configuration to redirect authenticated users to dashboard after login
- Ensure public pages (home, login, registration) remain unchanged and use existing `base.html.twig`

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.0  
**Primary Dependencies**: 
- Twig 3.x (template inheritance, blocks, includes)
- Bootstrap 5.x (sidebar, navbar, responsive utilities, components)
- Webpack Encore 5.x (SCSS compilation, asset management)
- Font Awesome (icons for navigation and UI elements)
- Symfony Security Bundle (authentication, role checks)
- Symfony Translation (i18n for navigation labels)

**Storage**: No database changes required (presentation layer only)  
**Testing**: Manual testing and visual inspection (no unit tests required for templates)  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend + Bootstrap CSS)  
**Performance Goals**: 
- Layout loads and renders within 2 seconds on standard broadband (NFR-003)
- Navigation between pages completes in under 3 seconds (SC-005)
- Sidebar toggle animation completes within 300ms
- Mobile sidebar slide-in animation completes within 400ms

**Constraints**: 
- MUST use Bootstrap components and utilities exclusively (no React, Vue, or other JS frameworks)
- MUST use Twig templates exclusively for rendering
- MUST write styles in SCSS compiled via Webpack Encore
- MUST follow Symfony and Twig best practices
- MUST NOT modify public-facing pages (home, login, registration, public card pages)
- MUST NOT modify business logic, controllers, services, or entities
- MUST NOT change database schema
- MUST maintain visual consistency with existing design system
- MUST support internationalization (EN/FR) for all navigation labels
- MUST preserve sidebar collapse/expand state across page navigations (localStorage)
- MUST ensure keyboard navigation and ARIA labels for accessibility

**Scale/Scope**: 
- All authenticated pages (Dashboard, My Cards, Account, Settings, Profile, Subscription pages, Admin pages)
- Responsive support for desktop (≥1024px), tablet (768px-1023px), and mobile (<768px)
- Sidebar navigation with 4-6 main items (expandable for future features)
- Support for Free, Pro, and Enterprise plan indicators in dashboard
- Integration with existing flash message system
- Support for existing translation system

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gate ✅

- **Controller → Service → Repository**: No changes to controllers or services required. Controllers continue to delegate to services, only template rendering changes.
- **No business logic in controllers**: Controllers remain unchanged, only template selection changes.
- **Dependency injection**: No new services required, existing services continue to be injected.
- **Template inheritance**: New base layout uses Twig template inheritance following Symfony conventions.

### Frontend Gate ✅

- **Twig-only rendering**: All pages use Twig templates exclusively, new base layout is a Twig template.
- **No React/Vue/Svelte**: Confirmed - using Bootstrap and vanilla JavaScript only for sidebar interactions.
- **Webpack Encore**: SCSS styles compiled via Webpack Encore, following existing asset pipeline.
- **No business logic in templates**: Templates only display data passed from controllers, no logic changes.

### ORM Gate ✅

- **No database changes**: This feature is presentation-layer only, no entity or schema changes required.
- **No repository changes**: Existing repositories continue to work unchanged.

### Security Gate ✅

- **Symfony Security**: Authentication and authorization remain unchanged, only template rendering changes.
- **Route protection**: Existing route protection via `#[IsGranted('ROLE_USER')]` continues to work.
- **CSRF protection**: Existing CSRF protection for forms remains unchanged.

### i18n Gate ✅

- **Symfony Translation**: All navigation labels and UI text use translation keys with `|trans` filter.
- **EN/FR support**: Translation files will be updated with new navigation labels.

### Coding Standards Gate ✅

- **PSR-12**: All code follows PSR-12 coding standards.
- **Strong typing**: PHP code uses type hints where applicable.
- **Descriptive names**: Templates, styles, and JavaScript use clear, descriptive names.
- **Symfony conventions**: Directory structure follows Symfony 8 conventions.

## Project Structure

### Documentation (this feature)

```text
specs/009-admin-template/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── templates.md     # Template structure and blocks
│   └── routes.md        # Route definitions for dashboard
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── templates/
│   ├── base.html.twig              # Existing: Unchanged (public pages)
│   ├── base_admin.html.twig        # New: Base layout for authenticated pages
│   ├── admin/
│   │   ├── _sidebar.html.twig      # New: Sidebar navigation component
│   │   ├── _header.html.twig       # New: Top header component
│   │   └── dashboard.html.twig     # New: Dashboard page
│   ├── card/
│   │   ├── index.html.twig         # Modified: Extend base_admin instead of base
│   │   ├── create.html.twig        # Modified: Extend base_admin instead of base
│   │   └── edit.html.twig          # Modified: Extend base_admin instead of base
│   ├── account/
│   │   ├── index.html.twig          # Modified: Extend base_admin instead of base
│   │   └── my_plan.html.twig       # Modified: Extend base_admin instead of base
│   ├── profile/
│   │   └── index.html.twig         # Modified: Extend base_admin instead of base
│   ├── subscription/
│   │   ├── manage.html.twig         # Modified: Extend base_admin instead of base
│   │   └── payments.html.twig      # Modified: Extend base_admin instead of base
│   └── admin/
│       ├── account/
│       │   └── index.html.twig     # Modified: Extend base_admin instead of base
│       └── webhook/
│           └── index.html.twig     # Modified: Extend base_admin instead of base
├── assets/
│   ├── styles/
│   │   ├── admin-layout.scss       # New: Admin layout styles
│   │   └── bootstrap-custom.scss   # Modified: Add admin layout utilities
│   └── app.js                       # Modified: Add sidebar toggle JavaScript
├── src/
│   └── Controller/
│       └── DashboardController.php # New: Dashboard controller
└── config/
    └── packages/
        └── security.yaml            # Modified: Update default_target_path to dashboard
```

## Phase 0: Outline & Research

### Research Tasks

1. **Bootstrap Sidebar Patterns**: Research Bootstrap 5 patterns for creating responsive sidebars with collapse/expand functionality
2. **Admin Layout Best Practices**: Research best practices for admin dashboard layouts with sidebar navigation
3. **Mobile Sidebar Patterns**: Research mobile-first sidebar patterns with hamburger menu and overlay behavior
4. **Accessibility Patterns**: Research ARIA patterns for sidebar navigation and keyboard navigation support
5. **State Persistence**: Research localStorage patterns for preserving UI state across page navigations

### Research Output

See [research.md](./research.md) for consolidated findings.

## Phase 1: Design & Contracts

### Template Structure

**Base Admin Layout** (`base_admin.html.twig`):
- Extends HTML structure with sidebar, header, and main content blocks
- Includes sidebar component (`_sidebar.html.twig`)
- Includes header component (`_header.html.twig`)
- Defines main content block for page-specific content
- Handles flash messages display
- Includes JavaScript for sidebar interactions

**Sidebar Component** (`_sidebar.html.twig`):
- Navigation items: Dashboard, My Cards, Account, Settings
- Active item highlighting based on current route
- Collapse/expand button for desktop
- Responsive behavior (hidden on mobile, toggleable)
- Icon support (Font Awesome)
- Translation support for labels

**Header Component** (`_header.html.twig`):
- Page title display
- User information (name or email)
- User menu dropdown (Profile, Logout)
- Language selector (preserved from existing navbar)
- Mobile hamburger menu button

**Dashboard Template** (`dashboard.html.twig`):
- Card-based layout for account overview
- Plan type display (Free, Pro, Enterprise)
- Card usage statistics
- Recent activity section
- Quick access links to key features

### Route Contracts

**Dashboard Route**:
- Route: `GET /dashboard`
- Controller: `DashboardController::index()`
- Template: `admin/dashboard.html.twig`
- Authorization: `ROLE_USER`
- Purpose: Display account overview and serve as default landing page

**Security Configuration Update**:
- Update `default_target_path` in `security.yaml` from `app_home` to `app_dashboard`
- Ensure authenticated users are redirected to dashboard after login

### Data Model

**No Data Model Changes**: This feature is presentation-layer only. No new entities, repositories, or database schema changes are required. The feature uses existing User and Account entities for display purposes only.

See [data-model.md](./data-model.md) for details.

### Contracts

See [contracts/](./contracts/) directory for:
- Template structure and block definitions
- Route contracts for dashboard
- Component API specifications

### Quickstart Guide

See [quickstart.md](./quickstart.md) for implementation guide.

