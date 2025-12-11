# Implementation Plan: Back-Office Theme Redesign

**Branch**: `001-backoffice-theme-redesign` | **Date**: 2025-12-11 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-backoffice-theme-redesign/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature redesigns the Hermio back-office interface (authentication pages and dashboard) with a modern, professional theme using Bootstrap 5.3.8 and Twig templates. The redesign introduces a two-column layout for authentication pages featuring a business card + QR code illustration, eliminates sidebar navigation in favor of a clean card-based dashboard, and establishes a unified visual design system through centralized SCSS design tokens. The implementation leverages the existing Symfony 8 + Webpack Encore infrastructure while maintaining full accessibility compliance and i18n support.

## Technical Context

**Language/Version**: PHP 8.2+ with Symfony 8  
**Primary Dependencies**: 
- Symfony 8 (web framework)
- Twig 3.x (templating engine)
- Bootstrap 5.3.8 (CSS framework - already installed)
- FontAwesome 7.1.0 (icon library - already installed)
- Webpack Encore (asset compilation - already configured)
- Sass/SCSS (CSS preprocessing - already enabled)

**Storage**: N/A (purely presentational/UI feature, no new data storage)  
**Testing**: Symfony test framework (existing), browser-based accessibility testing  
**Target Platform**: Web browsers (desktop and mobile, responsive design from 320px to 2560px+ viewports)  
**Project Type**: Web application (Symfony backend + Twig frontend)  
**Performance Goals**: 
- Page load time <2 seconds on standard broadband
- CSS bundle size increase <50KB (gzipped)
- No impact on existing page performance

**Constraints**: 
- Must not modify public front-site styling
- Must maintain existing authentication functionality
- Must support French and English translations
- Must meet WCAG 2.1 Level AA accessibility standards (4.5:1 contrast for normal text, 3:1 for large text)
- Must work with JavaScript disabled (progressive enhancement)

**Scale/Scope**: 
- 3 primary templates to redesign (login, register, dashboard base)
- 5+ existing dashboard pages to update with new layout
- New SCSS architecture with design tokens
- New Webpack entry point for authentication styles
- SVG/image asset for business card illustration

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### ✅ Clean Symfony Architecture
- **Status**: COMPLIANT
- Controllers remain thin (no business logic changes required)
- No new services, repositories, or entities needed (UI-only feature)
- Twig templates follow Symfony conventions in `app/templates/`
- No backend logic modifications required

### ✅ Twig-Driven Frontend
- **Status**: COMPLIANT
- All rendering uses Twig templates (no React/Vue/Svelte)
- Templates contain no business logic
- All UI text uses Symfony Translator: `{{ 'key'|trans }}`
- Follows existing template structure extending `base.html.twig`

### ✅ Asset Pipeline Governance (Webpack Encore)
- **Status**: COMPLIANT
- Uses existing Webpack Encore setup in `app/webpack.config.js`
- New SCSS files in `app/assets/styles/` directory
- New entry point `auth` will be added to Encore config (addEntry)
- No alternative build tools introduced
- Templates use `encore_entry_link_tags()` and `encore_entry_script_tags()`

### ✅ Internationalization
- **Status**: COMPLIANT
- All new UI text added to `app/translations/messages.en.yaml`
- All new UI text added to `app/translations/messages.fr.yaml`
- No inline strings in templates (all use trans filter)

### ✅ Coding Standards & Conventions
- **Status**: COMPLIANT
- Follows Symfony directory structure
- SCSS follows BEM or component-based naming conventions
- Templates maintain existing naming patterns

### ✅ Security & Authentication
- **Status**: COMPLIANT
- No changes to security configuration
- CSRF tokens remain properly integrated
- Maintains existing authentication flow
- Templates preserve security-related form fields

**GATE RESULT**: ✅ **PASSED** - No violations. All requirements align with Hermio's Symfony 8 + Twig + Webpack Encore constitution.

## Project Structure

### Documentation (this feature)

```text
specs/001-backoffice-theme-redesign/
├── plan.md              # This file (/speckit.plan command output)
├── spec.md              # Feature specification (already created)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   └── design-tokens.scss  # Contract defining color palette, spacing, typography
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (Symfony 8 Web Application)

```text
app/
├── assets/
│   ├── app.js                           # Existing main entry point
│   ├── home.js                          # Existing home entry point
│   ├── auth.js                          # NEW: Authentication pages entry point
│   ├── styles/
│   │   ├── bootstrap-custom.scss        # Existing Bootstrap customization
│   │   ├── app.scss                     # NEW: Main app styles (rename from inline in app.js)
│   │   ├── auth.scss                    # NEW: Authentication layout styles
│   │   ├── _design-tokens.scss          # NEW: Design system tokens (colors, spacing, etc.)
│   │   ├── components/
│   │   │   ├── _auth-layout.scss        # NEW: Two-column auth layout component
│   │   │   ├── _dashboard-card.scss     # NEW: Dashboard card component
│   │   │   └── _illustration.scss       # NEW: Business card illustration styles
│   │   └── dashboard.scss               # NEW: Dashboard-specific styles
│   └── images/
│       └── auth-illustration.svg         # NEW: Business card + QR code illustration
│
├── templates/
│   ├── base.html.twig                    # Existing base template (may need minor updates)
│   ├── auth/
│   │   └── _base.html.twig              # NEW: Base template for authentication pages
│   ├── security/
│   │   ├── login.html.twig              # MODIFY: Update with new two-column layout
│   │   └── register.html.twig           # MODIFY: Update with new two-column layout
│   ├── account/
│   │   ├── index.html.twig              # MODIFY: Update with card-based layout
│   │   └── my_plan.html.twig            # MODIFY: Update with card-based layout
│   ├── profile/
│   │   └── index.html.twig              # MODIFY: Update with card-based layout
│   └── components/
│       ├── _dashboard_card.html.twig     # NEW: Reusable card component
│       └── _page_header.html.twig        # NEW: Reusable page header component
│
├── translations/
│   ├── messages.en.yaml                  # MODIFY: Add new translation keys
│   └── messages.fr.yaml                  # MODIFY: Add new translation keys
│
└── webpack.config.js                     # MODIFY: Add 'auth' entry point

public/
└── build/                                # Generated by Webpack Encore (gitignored)
    ├── app.css
    ├── app.js
    ├── auth.css                          # NEW: Generated from auth entry
    └── auth.js                           # NEW: Generated from auth entry
```

**Structure Decision**: This is a standard Symfony 8 web application with Twig templating. The feature adds new SCSS modules following a component-based architecture, creates a dedicated authentication entry point in Webpack Encore, and introduces reusable Twig components for the dashboard. The structure separates authentication styles from dashboard styles to optimize asset loading. All files follow Symfony conventions with assets in `app/assets/`, templates in `app/templates/`, and translations in `app/translations/`.

## Complexity Tracking

**No violations to track.** This feature fully complies with the Hermio constitution and introduces no additional complexity beyond what is justified by the feature requirements. The implementation follows standard Symfony + Twig + Webpack Encore patterns.

---

## Phase 0: Research & Architecture Decisions

### Research Topics

#### 1. Bootstrap 5.3.8 Customization Best Practices
**Research Question**: What is the optimal approach for customizing Bootstrap 5.3.8 theming in a Symfony Webpack Encore setup while maintaining upgradability?

**Key Areas**:
- SCSS variable override patterns
- Custom component creation
- Bootstrap utilities extension
- Color system customization
- Responsive breakpoint configuration

#### 2. Two-Column Authentication Layout Patterns
**Research Question**: What are the industry-standard patterns for implementing split-view authentication pages with illustration/branding on one side and forms on the other?

**Key Areas**:
- Responsive breakpoint strategy (when to stack vs. side-by-side)
- Illustration sizing and positioning
- Form column width ratios (e.g., 40/60, 50/50)
- Mobile-first implementation approach
- Accessibility considerations for split layouts

#### 3. Business Card + QR Code Illustration Design
**Research Question**: How should the business card illustration be created and integrated to effectively communicate the Hermio value proposition?

**Key Areas**:
- SVG vs. PNG/JPG format decision
- Illustration style (flat design, isometric, realistic)
- QR code generation and embedding in illustration
- Color scheme for illustration matching brand
- Responsive scaling strategies
- Alternative text and accessibility

#### 4. SCSS Design Token Architecture
**Research Question**: What is the best practice for organizing design tokens (colors, spacing, typography) in a Sass-based design system for maintainability?

**Key Areas**:
- Token naming conventions (BEM, semantic, etc.)
- File organization structure
- CSS custom properties vs. SCSS variables
- Token categorization (primitive vs. semantic tokens)
- Bootstrap integration strategy

#### 5. Card-Based Dashboard Layout Best Practices
**Research Question**: What are the optimal Bootstrap 5 patterns for creating a responsive card-based dashboard without sidebar navigation?

**Key Areas**:
- Grid layout strategies for cards
- Card component composition
- Responsive stacking patterns
- Visual hierarchy and spacing
- Empty state design

#### 6. Webpack Encore Multiple Entry Points Strategy
**Research Question**: What is the best practice for managing multiple entry points in Webpack Encore to optimize asset loading?

**Key Areas**:
- When to create separate entry points
- Code splitting strategies
- Shared dependencies handling
- CSS extraction configuration
- Bundle size optimization

#### 7. Accessibility Implementation for Form Layouts
**Research Question**: What are the WCAG 2.1 Level AA requirements for accessible authentication forms and how to implement them with Bootstrap 5?

**Key Areas**:
- ARIA attributes for form validation
- Focus management and indicators
- Color contrast calculation and testing
- Keyboard navigation patterns
- Screen reader compatibility
- Error message association

#### 8. Twig Component Architecture
**Research Question**: What is the optimal pattern for creating reusable Twig components for a dashboard interface?

**Key Areas**:
- Block inheritance vs. includes vs. embeds
- Component parameter passing
- Component composition patterns
- Naming conventions
- Documentation approach

---

## Phase 1: Design & Contracts

### Data Model (data-model.md)

**Note**: This feature is purely presentational and does not introduce new data entities. However, it does interact with existing user and plan data.

#### Existing Entities Referenced

##### User (app/src/Entity/User.php)
- **Fields Used**: `email`, plan-related fields
- **Context**: Displayed in dashboard account information
- **No modifications required**

##### Plan/Subscription Data
- **Fields Used**: Plan type (Free/Pro/Enterprise), quota limits, usage counts
- **Context**: Displayed in dashboard cards
- **No modifications required**

##### Business Card Data
- **Fields Used**: Card count, card statistics
- **Context**: Displayed in dashboard summary
- **No modifications required**

#### Visual Design Entities (Non-Database)

##### Design Tokens
- **Primary Colors**: Brand primary, secondary, accent colors
- **Semantic Colors**: Success, warning, danger, info
- **Neutral Colors**: Gray scale (50-900)
- **Spacing Scale**: 0.25rem increments (xs, sm, md, lg, xl, 2xl, etc.)
- **Typography Scale**: Font sizes, weights, line heights
- **Border Radius**: Component-specific radius values
- **Shadows**: Elevation levels (sm, md, lg, xl)

##### Component State Variants
- **Cards**: Default, hover, focus states
- **Buttons**: Primary, secondary, outline variants with states
- **Forms**: Default, focus, error, disabled states
- **Alerts**: Info, success, warning, error variants

---

### API Contracts (contracts/)

**Note**: This is a frontend-only feature with no new API endpoints. However, we define design contracts for visual consistency.

#### Design Token Contract (contracts/design-tokens.scss)

This contract defines the design system variables that must be consistently used across all back-office pages.

```scss
// contracts/design-tokens.scss
// Design System Token Contract for Hermio Back-Office Theme
// Version: 1.0.0
// Last Updated: 2025-12-11

// ============================================================================
// COLOR PALETTE
// ============================================================================

// Primary Brand Colors
$hermio-primary: #4F46E5;        // Indigo - primary brand color
$hermio-primary-light: #6366F1;  // Lighter variant for hovers
$hermio-primary-dark: #4338CA;   // Darker variant for active states

// Secondary Colors
$hermio-secondary: #10B981;      // Emerald green - success/growth
$hermio-accent: #F59E0B;         // Amber - highlights/badges

// Semantic Colors
$hermio-success: #10B981;
$hermio-warning: #F59E0B;
$hermio-danger: #EF4444;
$hermio-info: #3B82F6;

// Neutral Palette
$hermio-gray-50: #F9FAFB;
$hermio-gray-100: #F3F4F6;
$hermio-gray-200: #E5E7EB;
$hermio-gray-300: #D1D5DB;
$hermio-gray-400: #9CA3AF;
$hermio-gray-500: #6B7280;
$hermio-gray-600: #4B5563;
$hermio-gray-700: #374151;
$hermio-gray-800: #1F2937;
$hermio-gray-900: #111827;

// Background Colors
$hermio-bg-auth-form: #FFFFFF;
$hermio-bg-auth-illustration: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
$hermio-bg-dashboard: #F9FAFB;

// ============================================================================
// SPACING SCALE (based on 0.25rem = 4px)
// ============================================================================

$hermio-space-xs: 0.5rem;    // 8px
$hermio-space-sm: 1rem;      // 16px
$hermio-space-md: 1.5rem;    // 24px
$hermio-space-lg: 2rem;      // 32px
$hermio-space-xl: 3rem;      // 48px
$hermio-space-2xl: 4rem;     // 64px
$hermio-space-3xl: 6rem;     // 96px

// ============================================================================
// TYPOGRAPHY
// ============================================================================

// Font Families
$hermio-font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
                   "Helvetica Neue", Arial, sans-serif;
$hermio-font-mono: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;

// Font Sizes
$hermio-text-xs: 0.75rem;    // 12px
$hermio-text-sm: 0.875rem;   // 14px
$hermio-text-base: 1rem;     // 16px
$hermio-text-lg: 1.125rem;   // 18px
$hermio-text-xl: 1.25rem;    // 20px
$hermio-text-2xl: 1.5rem;    // 24px
$hermio-text-3xl: 1.875rem;  // 30px
$hermio-text-4xl: 2.25rem;   // 36px
$hermio-text-5xl: 3rem;      // 48px

// Font Weights
$hermio-font-normal: 400;
$hermio-font-medium: 500;
$hermio-font-semibold: 600;
$hermio-font-bold: 700;

// Line Heights
$hermio-leading-tight: 1.25;
$hermio-leading-normal: 1.5;
$hermio-leading-relaxed: 1.75;

// ============================================================================
// BORDER RADIUS
// ============================================================================

$hermio-radius-sm: 0.25rem;  // 4px - small elements
$hermio-radius-md: 0.5rem;   // 8px - buttons, inputs
$hermio-radius-lg: 0.75rem;  // 12px - cards
$hermio-radius-xl: 1rem;     // 16px - large containers
$hermio-radius-full: 9999px; // Full rounded (pills, avatars)

// ============================================================================
// SHADOWS
// ============================================================================

$hermio-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
$hermio-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                   0 2px 4px -1px rgba(0, 0, 0, 0.06);
$hermio-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                   0 4px 6px -2px rgba(0, 0, 0, 0.05);
$hermio-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
                   0 10px 10px -5px rgba(0, 0, 0, 0.04);

// ============================================================================
// LAYOUT
// ============================================================================

// Breakpoints (Bootstrap 5 defaults - for reference)
$hermio-breakpoint-sm: 576px;
$hermio-breakpoint-md: 768px;
$hermio-breakpoint-lg: 992px;
$hermio-breakpoint-xl: 1200px;
$hermio-breakpoint-xxl: 1400px;

// Auth Layout
$hermio-auth-form-width: 480px;
$hermio-auth-max-width: 1400px;

// Dashboard Layout
$hermio-dashboard-max-width: 1200px;
$hermio-card-gap: 1.5rem;  // 24px gap between cards

// ============================================================================
// TRANSITIONS
// ============================================================================

$hermio-transition-fast: 150ms ease-in-out;
$hermio-transition-base: 250ms ease-in-out;
$hermio-transition-slow: 350ms ease-in-out;

// ============================================================================
// USAGE GUIDELINES
// ============================================================================

// All back-office SCSS files MUST import this contract:
//   @import '../contracts/design-tokens';
//
// Use tokens instead of hardcoded values:
//   ✅ background: $hermio-primary;
//   ❌ background: #4F46E5;
//
// Follow semantic naming:
//   ✅ Use $hermio-success for positive actions
//   ✅ Use $hermio-danger for destructive actions
//   ✅ Use $hermio-gray-* for neutral UI elements
//
// Maintain consistency:
//   - Use spacing scale for all margins and padding
//   - Use typography scale for all font sizes
//   - Use shadow scale for elevation
```

#### Twig Component Contract

**Reusable Components** (templates/components/):

1. **_dashboard_card.html.twig**
   - Props: `title`, `icon` (optional), `content` block
   - Usage: Consistent card layout across dashboard pages

2. **_page_header.html.twig**
   - Props: `title`, `subtitle` (optional), `actions` block (optional)
   - Usage: Standardized page titles and action buttons

---

### Quickstart Guide (quickstart.md)

See separate `quickstart.md` file for:
- Developer setup instructions
- How to use design tokens
- How to create new dashboard pages
- How to extend the theme
- Accessibility testing checklist
- Build and deployment commands

---

## Phase 2: Task Breakdown

**Note**: Detailed task breakdown will be created by the `/speckit.tasks` command (not generated by this plan command).

### High-Level Task Categories

1. **Setup & Configuration**
   - Create directory structure
   - Add Webpack entry point
   - Configure asset pipeline

2. **Design Token System**
   - Create `_design-tokens.scss`
   - Define color palette
   - Define spacing and typography
   - Document usage guidelines

3. **Authentication Page Redesign**
   - Create business card illustration
   - Build two-column layout component
   - Update login template
   - Update register template
   - Add responsive styles
   - Implement accessibility features

4. **Dashboard Redesign**
   - Create reusable card component
   - Create page header component
   - Update account/index.html.twig
   - Update account/my_plan.html.twig
   - Update profile/index.html.twig
   - Remove sidebar navigation
   - Add responsive grid layouts

5. **Internationalization**
   - Add English translations
   - Add French translations
   - Test both locales

6. **Testing & Validation**
   - Browser compatibility testing
   - Responsive design testing (320px - 2560px+)
   - Accessibility audit (WCAG 2.1 AA)
   - Performance testing
   - Translation completeness check
   - Cross-browser testing

7. **Documentation**
   - Component usage examples
   - Design token reference
   - Migration guide for existing pages
   - Accessibility compliance report

---

## Implementation Notes

### Key Decisions

1. **Separate Entry Point for Auth Pages**: Create a dedicated `auth` entry point in Webpack Encore to minimize CSS loaded on authentication pages. Dashboard pages will continue using the main `app` entry point.

2. **Component-Based SCSS Architecture**: Organize styles using a component-based approach with clear separation:
   - `_design-tokens.scss` - Global tokens
   - `components/_auth-layout.scss` - Auth page specific
   - `components/_dashboard-card.scss` - Dashboard specific
   - `components/_illustration.scss` - Shared illustration styles

3. **Mobile-First Responsive Design**: Implement responsive layouts using mobile-first approach with Bootstrap 5 breakpoints:
   - Mobile (<768px): Stacked vertical layout
   - Tablet/Desktop (≥768px): Two-column layout for auth, grid for dashboard

4. **Progressive Enhancement**: Ensure all functionality works without JavaScript, then enhance with interactive features (e.g., focus indicators, animations).

5. **Accessibility First**: Build accessibility into the initial implementation rather than retrofitting:
   - Proper ARIA landmarks from the start
   - High contrast colors chosen upfront
   - Keyboard navigation tested during development

### Risks & Mitigations

| Risk | Mitigation Strategy |
|------|---------------------|
| Breaking existing pages during base template changes | Create new `auth/_base.html.twig` instead of modifying `base.html.twig` for auth pages |
| CSS bundle size increase | Use separate entry points and code splitting; monitor with webpack-bundle-analyzer |
| Translation key conflicts | Namespace new keys under `auth.*` and `dashboard.*` prefixes |
| Accessibility regressions | Run automated accessibility checks in CI pipeline |
| Browser compatibility issues | Test in major browsers (Chrome, Firefox, Safari, Edge) early and often |
| Illustration not loading | Provide fallback background color; ensure SVG optimization |

### Success Metrics

- [ ] All 3 authentication pages render correctly in 5+ browsers
- [ ] Dashboard pages load in <2 seconds
- [ ] WCAG 2.1 AA compliance verified with automated tools
- [ ] CSS bundle increase <50KB gzipped
- [ ] 100% translation coverage in EN/FR
- [ ] Zero regression in authentication functionality
- [ ] Responsive layout works from 320px to 2560px+ viewports

---

## Next Steps

After this planning phase:

1. ✅ Constitution check passed
2. 🔄 Run Phase 0: Generate `research.md` (resolve all research questions)
3. 🔄 Run Phase 1: Generate detailed `data-model.md` and `quickstart.md`
4. 🔄 Update agent context with technology decisions
5. ⏳ Run Phase 2: Use `/speckit.tasks` command to generate `tasks.md` with detailed implementation tasks

**Command to proceed**: `/speckit.tasks` (after research and design phases complete)
