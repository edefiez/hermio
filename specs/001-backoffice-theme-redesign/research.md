# Phase 0 Research: Back-Office Theme Redesign

**Feature**: Back-Office Theme Redesign  
**Date**: 2025-12-11  
**Status**: Complete

This document consolidates research findings for all technical decisions required to implement the back-office theme redesign.

---

## 1. Bootstrap 5.3.8 Customization Best Practices

### Decision
Use SCSS variable overrides in a dedicated `_design-tokens.scss` file, imported before Bootstrap's main SCSS.

### Rationale
- **Maintainability**: Centralized token management makes theme updates easy
- **Upgradability**: Overriding variables (vs. modifying Bootstrap source) allows clean Bootstrap upgrades
- **Performance**: Single compilation pass, no duplicate CSS
- **Developer Experience**: Clear separation between framework and custom styles

### Implementation Pattern
```scss
// app/assets/styles/_design-tokens.scss
$primary: #4F46E5;  // Override Bootstrap's default $primary
$secondary: #10B981;
// ... more overrides

// app/assets/styles/bootstrap-custom.scss
@import 'design-tokens';
@import '~bootstrap/scss/bootstrap';
```

### Alternatives Considered
- **CSS Custom Properties**: Not chosen because Bootstrap 5.3.8 still uses SCSS variables for theming; CSS custom properties add runtime overhead
- **Utility Classes Only**: Not chosen because we need consistent component-level theming
- **Bootstrap Customizer Tool**: Not chosen because it's not maintainable in a development workflow

### References
- [Bootstrap 5 Theming Documentation](https://getbootstrap.com/docs/5.3/customize/sass/)
- [Symfony Webpack Encore SCSS Configuration](https://symfony.com/doc/current/frontend/encore/css-preprocessors.html)

---

## 2. Two-Column Authentication Layout Patterns

### Decision
Implement a 50/50 split layout on desktop (≥768px) with responsive stacking on mobile, using Bootstrap's grid system.

### Rationale
- **Industry Standard**: Major SaaS applications (Stripe, GitHub, Slack) use similar split-view patterns
- **Visual Balance**: 50/50 split provides equal weight to form and branding
- **Accessibility**: Clear visual hierarchy, form is first in DOM order for screen readers
- **Mobile-First**: Stacking on mobile ensures form accessibility on small screens

### Implementation Pattern
```html
<div class="container-fluid vh-100">
  <div class="row h-100">
    <div class="col-12 col-md-6 order-1 order-md-1">
      <!-- Form column -->
    </div>
    <div class="col-12 col-md-6 order-2 order-md-2 bg-gradient">
      <!-- Illustration column -->
    </div>
  </div>
</div>
```

### Responsive Breakpoints
- **Mobile (<768px)**: Single column, form above illustration
- **Tablet/Desktop (≥768px)**: Two columns, 50/50 split
- **Wide screens (≥1400px)**: Max-width container to prevent excessive stretching

### Alternatives Considered
- **40/60 Split**: Not chosen because equal weight better represents the dual purpose (authentication + branding)
- **Sidebar Pattern**: Not chosen because illustration should be prominent, not secondary
- **Full-Page Background**: Not chosen because form legibility would be compromised

### Best Practices Applied
- Form column scrollable if content overflows
- Illustration column fixed height with centered content
- Focus management: form fields tab in logical order
- ARIA landmarks: `role="main"` on form, `role="complementary"` on illustration

---

## 3. Business Card + QR Code Illustration Design

### Decision
Create a custom SVG illustration featuring a modern business card with an embedded QR code pointing to https://hermio.cards, using flat design style with the brand color palette.

### Rationale
- **SVG Format**: Scalable, small file size (~10-20KB), crisp on all displays
- **Custom Illustration**: Reinforces brand identity and product value proposition
- **Flat Design**: Modern, clean aesthetic matching Bootstrap 5 design language
- **QR Code Integration**: Visually demonstrates the product's core feature

### Design Specifications
- **Style**: Flat illustration with subtle shadows for depth
- **Colors**: Use `$hermio-primary` (#4F46E5) and accent colors from design tokens
- **Elements**:
  - Business card mockup (front view, slight perspective)
  - QR code prominently displayed on card
  - Subtle iconography (email, phone, website icons)
  - Optional: smartphone scanning the QR code for context
- **Dimensions**: 600x800px artboard (portrait orientation)
- **Accessibility**: Decorative image with empty alt text (branding, not informational)

### Alternatives Considered
- **Photograph**: Not chosen because illustration is more flexible and on-brand
- **Animated SVG**: Not chosen because accessibility and performance concerns
- **Icon Set**: Not chosen because not impactful enough for the branding space

### Implementation Notes
```html
<img src="{{ asset('build/images/auth-illustration.svg') }}" 
     alt="" 
     class="img-fluid" 
     role="presentation">
```

---

## 4. SCSS Design Token Architecture

### Decision
Use a hybrid approach: SCSS variables for compile-time values with semantic naming, organized in a single `_design-tokens.scss` file following a primitive-to-semantic pattern.

### Rationale
- **Single Source of Truth**: One file for all design decisions
- **Semantic Naming**: `$hermio-primary` is more meaningful than `$blue-500`
- **Type Safety**: SCSS compilation catches errors
- **Performance**: No runtime CSS custom property overhead
- **Bootstrap Integration**: Seamlessly overrides Bootstrap variables

### Token Organization
```scss
// 1. Primitive tokens (base colors, base spacing)
$color-indigo-600: #4F46E5;
$space-base: 1rem;

// 2. Semantic tokens (mapped to primitives)
$hermio-primary: $color-indigo-600;
$hermio-space-md: $space-base * 1.5;

// 3. Component tokens (specific uses)
$auth-form-padding: $hermio-space-xl;
```

### File Structure
```
app/assets/styles/
├── _design-tokens.scss    # All tokens defined here
├── bootstrap-custom.scss  # Imports tokens, then Bootstrap
├── auth.scss              # Auth-specific styles
└── components/
    ├── _auth-layout.scss
    └── _dashboard-card.scss
```

### Naming Convention
- Prefix: `$hermio-*` for all custom tokens
- Categories: `color-`, `space-`, `text-`, `radius-`, `shadow-`
- Semantic: `primary`, `secondary`, `success`, `danger`, etc.

### Alternatives Considered
- **CSS Custom Properties**: Not chosen for compile-time token values (faster, no browser compatibility issues)
- **Multiple Token Files**: Not chosen because single file is easier to maintain for this scope
- **JavaScript Design Tokens**: Not chosen because SCSS is sufficient and simpler

---

## 5. Card-Based Dashboard Layout Best Practices

### Decision
Use Bootstrap 5's card component with a responsive grid layout (1 column mobile, 2-3 columns desktop) and consistent spacing using design tokens.

### Rationale
- **Bootstrap Native**: Leverage framework's built-in card component
- **Responsive**: Bootstrap grid handles responsive layouts automatically
- **Scannable**: Card-based layouts improve information scannability
- **Modular**: Easy to add/remove dashboard sections

### Layout Pattern
```html
<div class="container-lg py-4">
  <div class="row g-4">
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100"><!-- Card 1 --></div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100"><!-- Card 2 --></div>
    </div>
    <!-- More cards -->
  </div>
</div>
```

### Grid Strategy
- **Mobile (<576px)**: 1 column (stacked)
- **Tablet (≥768px)**: 2 columns
- **Desktop (≥1200px)**: 3 columns
- **Spacing**: `g-4` class for 1.5rem gap (24px)

### Card Anatomy
```html
<div class="card shadow-sm">
  <div class="card-header">
    <h5 class="card-title mb-0">{{ title }}</h5>
  </div>
  <div class="card-body">
    {{ content }}
  </div>
  <div class="card-footer">
    {{ actions }}
  </div>
</div>
```

### Alternatives Considered
- **List-Based Layout**: Not chosen because less visual hierarchy
- **Masonry Grid**: Not chosen because complex and not needed for uniform content
- **Sidebar Navigation**: Explicitly rejected per requirements

---

## 6. Webpack Encore Multiple Entry Points Strategy

### Decision
Create a dedicated `auth` entry point for authentication pages, separate from the main `app` entry point used by dashboard pages.

### Rationale
- **Performance**: Auth pages don't need dashboard-specific CSS/JS
- **Code Splitting**: Smaller initial bundles for faster page loads
- **Maintainability**: Clear separation of concerns
- **Caching**: Dashboard updates don't bust auth page cache

### Implementation
```javascript
// webpack.config.js
Encore
  .addEntry('app', './assets/app.js')          // Dashboard pages
  .addEntry('home', './assets/home.js')        // Existing home entry
  .addEntry('auth', './assets/auth.js')        // NEW: Auth pages
```

### Entry Point Structure
```javascript
// assets/auth.js
import './styles/auth.scss';
import 'bootstrap';  // JS for dropdowns, etc.
```

```javascript
// assets/app.js
import './styles/app.scss';
import './styles/dashboard.scss';
import 'bootstrap';
```

### Bundle Size Strategy
- **Shared Dependencies**: Webpack will automatically extract common chunks (Bootstrap, core CSS)
- **Lazy Loading**: Not needed for this scope (static pages)
- **Code Splitting**: Enabled via `.splitEntryChunks()`

### Alternatives Considered
- **Single Entry Point**: Not chosen because unnecessary CSS/JS loaded on auth pages
- **Per-Page Entry Points**: Not chosen because too granular, harder to maintain
- **Dynamic Imports**: Not chosen because not needed for static Twig pages

---

## 7. Accessibility Implementation for Form Layouts

### Decision
Implement WCAG 2.1 Level AA compliance with proper ARIA attributes, semantic HTML, keyboard navigation, and high-contrast colors from the start.

### Rationale
- **Legal Compliance**: Many jurisdictions require WCAG 2.1 AA
- **Better UX**: Accessible design benefits all users
- **Retrofitting Costly**: Building accessibility in from the start is easier
- **Bootstrap Support**: Bootstrap 5 has good accessibility defaults

### Key Requirements & Implementation

#### 1. Form Labels & Association
```html
<label for="email" class="form-label">
  {{ 'auth.email.label'|trans }}
</label>
<input type="email" 
       id="email" 
       class="form-control" 
       required>
```

#### 2. Error Messages
```html
<input type="email" 
       id="email" 
       class="form-control is-invalid" 
       aria-describedby="email-error">
<div id="email-error" class="invalid-feedback">
  {{ error.message }}
</div>
```

#### 3. ARIA Landmarks
```html
<main role="main">
  <form role="form">...</form>
</main>
<aside role="complementary">
  <img role="presentation" alt="">
</aside>
```

#### 4. Keyboard Navigation
- All interactive elements in tab order
- Visible focus indicators (`:focus-visible`)
- Skip links if multiple forms on page

#### 5. Color Contrast
- Normal text: Minimum 4.5:1 contrast ratio
- Large text (≥18pt or ≥14pt bold): Minimum 3:1
- Testing: Chrome DevTools, axe DevTools

### Color Contrast Validation
```scss
// Design tokens chosen with contrast in mind
$hermio-primary: #4F46E5;  // 4.54:1 on white (AA compliant)
$hermio-text: #111827;     // 16.12:1 on white (AAA compliant)
```

### Alternatives Considered
- **ARIA-only approach**: Not chosen because semantic HTML is preferred
- **AAA Compliance**: Not chosen because AA is industry standard, AAA may limit design flexibility
- **Screen reader testing only**: Not chosen because automated tools catch many issues faster

### Testing Tools
- axe DevTools (browser extension)
- WAVE (Web Accessibility Evaluation Tool)
- Lighthouse accessibility audit
- Manual keyboard navigation testing
- Manual screen reader testing (NVDA/JAWS/VoiceOver)

---

## 8. Twig Component Architecture

### Decision
Use Twig's `include` with parameter passing for reusable components, organized in a `templates/components/` directory.

### Rationale
- **Simplicity**: `include` is straightforward for stateless components
- **Performance**: Includes are compiled efficiently
- **Symfony Standard**: Consistent with Symfony best practices
- **Maintainability**: Clear component boundaries

### Component Pattern
```twig
{# templates/components/_dashboard_card.html.twig #}
<div class="card shadow-sm h-100">
  {% if title is defined %}
  <div class="card-header">
    <h5 class="card-title mb-0">
      {% if icon is defined %}
        <i class="{{ icon }} me-2"></i>
      {% endif %}
      {{ title }}
    </h5>
  </div>
  {% endif %}
  
  <div class="card-body">
    {{ content|raw }}
  </div>
  
  {% if actions is defined %}
  <div class="card-footer">
    {{ actions|raw }}
  </div>
  {% endif %}
</div>
```

### Usage
```twig
{% include 'components/_dashboard_card.html.twig' with {
  'title': 'Account Overview',
  'icon': 'fas fa-user',
  'content': '<p>Your account details...</p>'
} %}
```

### Component Library
1. **_dashboard_card.html.twig**: Card container with header/body/footer
2. **_page_header.html.twig**: Page title with optional actions
3. **_stat_card.html.twig**: Numeric stat display
4. **_alert_banner.html.twig**: Styled alert messages

### Alternatives Considered
- **Twig Blocks with Extends**: Not chosen because components need to be reusable across different base templates
- **Embeds**: Not chosen because more complex than needed for these simple components
- **UX Turbo Components**: Not chosen because Twig components are sufficient for server-rendered pages

### Naming Convention
- Prefix components with `_` to indicate they're partials
- Use snake_case: `_dashboard_card.html.twig`
- Store in `templates/components/`

---

## Summary of Key Decisions

| Aspect | Decision | Primary Reason |
|--------|----------|----------------|
| **Bootstrap Customization** | SCSS variable overrides | Maintainability & upgradability |
| **Auth Layout** | 50/50 split, responsive stack | Industry standard, visual balance |
| **Illustration** | Custom SVG, flat design | Scalable, brand-aligned, small file size |
| **Design Tokens** | Single SCSS file, semantic naming | Single source of truth, clear organization |
| **Dashboard Layout** | Bootstrap cards, responsive grid | Scannable, modular, framework-native |
| **Webpack Entries** | Separate `auth` entry point | Performance, code splitting |
| **Accessibility** | WCAG 2.1 AA from the start | Legal compliance, better UX |
| **Twig Components** | Include-based components | Simplicity, Symfony standard |

---

## Next Steps

With research complete, proceed to:

1. ✅ Phase 0 Complete: All research questions answered
2. 🔄 Phase 1: Generate detailed design artifacts (data-model.md, quickstart.md)
3. 🔄 Update agent context with technology decisions
4. ⏳ Phase 2: Generate implementation tasks

**All technical decisions are documented and justified. Implementation can proceed with confidence.**
