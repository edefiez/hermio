# Quickstart Guide: Back-Office Theme Redesign

**Feature**: Back-Office Theme Redesign  
**Date**: 2025-12-11  
**For**: Developers implementing or extending the theme

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Setup & Installation](#setup--installation)
3. [Project Structure Overview](#project-structure-overview)
4. [Using Design Tokens](#using-design-tokens)
5. [Creating New Dashboard Pages](#creating-new-dashboard-pages)
6. [Creating Reusable Components](#creating-reusable-components)
7. [Extending the Theme](#extending-the-theme)
8. [Accessibility Checklist](#accessibility-checklist)
9. [Build & Deployment](#build--deployment)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting, ensure you have:

- **Node.js**: v18+ (for Webpack Encore)
- **npm** or **yarn**: Package manager
- **PHP**: 8.2+ with Symfony 8
- **Symfony CLI**: Optional but recommended
- **Git**: For version control
- **Browser DevTools**: Chrome/Firefox with accessibility extensions

**Required Knowledge**:
- Symfony 8 basics (templates, routing, translations)
- Twig templating syntax
- SCSS/Sass fundamentals
- Bootstrap 5 CSS framework
- Basic accessibility principles (WCAG 2.1)

---

## Setup & Installation

### 1. Clone & Navigate

```bash
cd /home/runner/work/hermio/hermio
git checkout 001-backoffice-theme-redesign
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
# OR
yarn install
```

### 3. Build Assets

```bash
# Development build (with source maps)
npm run dev
# OR
yarn encore dev

# Watch mode (rebuilds on file changes)
npm run watch
# OR
yarn encore dev --watch

# Production build (optimized, minified)
npm run build
# OR
yarn encore production
```

### 4. Start Development Server

```bash
# Using Symfony CLI
symfony server:start

# OR using PHP built-in server
php -S localhost:8000 -t public/
```

### 5. Verify Installation

Navigate to:
- **Login**: http://localhost:8000/login
- **Register**: http://localhost:8000/register
- **Dashboard**: http://localhost:8000/account (requires login)

---

## Project Structure Overview

```
app/
├── assets/
│   ├── app.js                    # Main entry point
│   ├── auth.js                   # Auth pages entry point
│   ├── home.js                   # Home page entry
│   └── styles/
│       ├── _design-tokens.scss   # ⭐ Design system tokens
│       ├── bootstrap-custom.scss # Bootstrap overrides
│       ├── auth.scss             # Auth page styles
│       ├── dashboard.scss        # Dashboard styles
│       └── components/
│           ├── _auth-layout.scss
│           ├── _dashboard-card.scss
│           └── _illustration.scss
│
├── templates/
│   ├── base.html.twig            # Global base template
│   ├── auth/
│   │   └── _base.html.twig       # Auth-specific base
│   ├── security/
│   │   ├── login.html.twig       # Login page
│   │   └── register.html.twig    # Register page
│   ├── account/
│   │   ├── index.html.twig       # Dashboard
│   │   └── my_plan.html.twig     # Plan page
│   └── components/
│       ├── _dashboard_card.html.twig
│       └── _page_header.html.twig
│
├── translations/
│   ├── messages.en.yaml          # English translations
│   └── messages.fr.yaml          # French translations
│
└── webpack.config.js             # Webpack Encore config
```

**Key Files to Know**:
- `_design-tokens.scss`: All color, spacing, typography variables
- `auth.js`: Entry point for login/register pages
- `app.js`: Entry point for dashboard/authenticated pages
- `components/`: Reusable Twig partials

---

## Using Design Tokens

### Import Design Tokens

In any SCSS file:

```scss
// Import design tokens FIRST
@import '../design-tokens';

.my-custom-component {
  // Use tokens instead of hardcoded values
  background: $hermio-primary;
  padding: $hermio-space-md;
  border-radius: $hermio-radius-lg;
  box-shadow: $hermio-shadow-md;
}
```

### Available Token Categories

#### Colors
```scss
// Brand colors
$hermio-primary          // #4F46E5 (indigo)
$hermio-secondary        // #10B981 (green)
$hermio-accent           // #F59E0B (amber)

// Semantic colors
$hermio-success          // Green
$hermio-warning          // Amber
$hermio-danger           // Red
$hermio-info             // Blue

// Gray scale
$hermio-gray-50          // Lightest
$hermio-gray-500         // Medium
$hermio-gray-900         // Darkest
```

#### Spacing
```scss
$hermio-space-xs         // 0.5rem (8px)
$hermio-space-sm         // 1rem (16px)
$hermio-space-md         // 1.5rem (24px)
$hermio-space-lg         // 2rem (32px)
$hermio-space-xl         // 3rem (48px)
```

#### Typography
```scss
$hermio-text-xs          // 0.75rem (12px)
$hermio-text-base        // 1rem (16px)
$hermio-text-2xl         // 1.5rem (24px)
$hermio-text-5xl         // 3rem (48px)

$hermio-font-normal      // 400
$hermio-font-semibold    // 600
$hermio-font-bold        // 700
```

#### Border Radius
```scss
$hermio-radius-sm        // 0.25rem (4px)
$hermio-radius-md        // 0.5rem (8px)
$hermio-radius-lg        // 0.75rem (12px)
```

#### Shadows
```scss
$hermio-shadow-sm        // Subtle elevation
$hermio-shadow-md        // Default cards
$hermio-shadow-lg        // Elevated cards (hover)
$hermio-shadow-xl        // Modals, popovers
```

**Full reference**: See `app/assets/styles/_design-tokens.scss`

---

## Creating New Dashboard Pages

### Step 1: Create Twig Template

Create a new file in `app/templates/`:

```twig
{# app/templates/my_feature/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ 'my_feature.title'|trans }} - Hermio{% endblock %}

{% block stylesheets %}
  {{ parent() }}
  {# Use 'app' entry for dashboard pages #}
  {{ encore_entry_link_tags('app') }}
{% endblock %}

{% block body %}
<div class="container-lg py-4">
  {# Page Header #}
  {% include 'components/_page_header.html.twig' with {
    'title': 'my_feature.title'|trans,
    'subtitle': 'my_feature.subtitle'|trans
  } %}
  
  {# Dashboard Cards Grid #}
  <div class="row g-4 mt-4">
    <div class="col-12 col-md-6 col-xl-4">
      {% include 'components/_dashboard_card.html.twig' with {
        'title': 'my_feature.card1.title'|trans,
        'icon': 'fas fa-chart-line',
        'content': '<p>Your content here...</p>'
      } %}
    </div>
    
    <div class="col-12 col-md-6 col-xl-4">
      {% include 'components/_dashboard_card.html.twig' with {
        'title': 'my_feature.card2.title'|trans,
        'content': '<p>More content...</p>'
      } %}
    </div>
  </div>
</div>
{% endblock %}
```

### Step 2: Add Translations

In `app/translations/messages.en.yaml`:

```yaml
my_feature:
  title: "My Feature"
  subtitle: "Manage your feature settings"
  card1:
    title: "Statistics"
  card2:
    title: "Recent Activity"
```

In `app/translations/messages.fr.yaml`:

```yaml
my_feature:
  title: "Ma Fonctionnalité"
  subtitle: "Gérer vos paramètres de fonctionnalité"
  card1:
    title: "Statistiques"
  card2:
    title: "Activité Récente"
```

### Step 3: Create Controller Route

```php
// src/Controller/MyFeatureController.php
#[Route('/my-feature', name: 'app_my_feature')]
public function index(): Response
{
    return $this->render('my_feature/index.html.twig');
}
```

### Step 4: Build & Test

```bash
npm run watch
```

Navigate to: http://localhost:8000/my-feature

---

## Creating Reusable Components

### Twig Component Pattern

Create a new file in `app/templates/components/`:

```twig
{# app/templates/components/_my_component.html.twig #}
<div class="my-component {{ variant|default('default') }}">
  {% if title is defined %}
  <h3 class="component-title">{{ title }}</h3>
  {% endif %}
  
  <div class="component-body">
    {{ content|raw }}
  </div>
  
  {% if actions is defined %}
  <div class="component-actions">
    {{ actions|raw }}
  </div>
  {% endif %}
</div>
```

### Component SCSS

Create `app/assets/styles/components/_my-component.scss`:

```scss
@import '../design-tokens';

.my-component {
  background: $hermio-bg-card;
  border-radius: $hermio-radius-lg;
  padding: $hermio-space-lg;
  box-shadow: $hermio-shadow-sm;
  transition: box-shadow $hermio-transition-base;
  
  &:hover {
    box-shadow: $hermio-shadow-md;
  }
  
  .component-title {
    font-size: $hermio-text-xl;
    font-weight: $hermio-font-semibold;
    color: $hermio-gray-900;
    margin-bottom: $hermio-space-md;
  }
  
  .component-body {
    color: $hermio-gray-600;
    line-height: $hermio-leading-normal;
  }
  
  // Variants
  &.primary {
    border-left: 4px solid $hermio-primary;
  }
  
  &.success {
    border-left: 4px solid $hermio-success;
  }
}
```

### Import Component SCSS

In `app/assets/styles/dashboard.scss`:

```scss
@import 'components/my-component';
```

### Use the Component

```twig
{% include 'components/_my_component.html.twig' with {
  'title': 'Component Title',
  'content': '<p>Component content</p>',
  'variant': 'primary'
} %}
```

---

## Extending the Theme

### Adding New Colors

1. **Define in design tokens** (`_design-tokens.scss`):

```scss
$hermio-purple: #9333EA;
```

2. **Use in components**:

```scss
.my-element {
  background: $hermio-purple;
}
```

### Adding New Spacing Values

```scss
$hermio-space-4xl: 8rem; // 128px
```

### Custom Bootstrap Overrides

In `bootstrap-custom.scss`:

```scss
@import 'design-tokens';

// Override Bootstrap variables
$navbar-padding-y: $hermio-space-md;
$card-border-radius: $hermio-radius-xl;

@import '~bootstrap/scss/bootstrap';
```

### Adding Custom Utility Classes

```scss
// In app.scss or component file
.bg-gradient-primary {
  background: linear-gradient(135deg, $hermio-primary 0%, $hermio-primary-dark 100%);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
```

---

## Accessibility Checklist

### Before Deploying Any Page

- [ ] **Keyboard Navigation**: All interactive elements reachable via Tab key
- [ ] **Focus Indicators**: Visible focus outline on all focusable elements
- [ ] **Form Labels**: All inputs have associated `<label>` or `aria-label`
- [ ] **Error Messages**: Associated with inputs via `aria-describedby`
- [ ] **Color Contrast**: Text meets 4.5:1 ratio (normal), 3:1 (large text)
- [ ] **ARIA Landmarks**: Proper use of `<main>`, `<nav>`, `<aside>`, etc.
- [ ] **Alt Text**: Images have appropriate alt text (or empty if decorative)
- [ ] **Heading Hierarchy**: Logical h1 → h2 → h3 structure
- [ ] **Screen Reader Testing**: Test with NVDA (Windows) or VoiceOver (Mac)
- [ ] **Responsive**: Functional from 320px to 2560px+ viewports

### Testing Tools

**Browser Extensions**:
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/extension/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) (built into Chrome)

**Automated Testing**:
```bash
# Run Lighthouse CI in terminal
npx lighthouse http://localhost:8000/login --view

# Check specific page for accessibility issues
npx @axe-core/cli http://localhost:8000/account
```

**Manual Testing**:
1. **Keyboard only**: Navigate entire page using only Tab, Enter, Esc
2. **Screen reader**: Use NVDA (free, Windows) or VoiceOver (Mac)
3. **Zoom to 200%**: Ensure layout doesn't break
4. **High contrast mode**: Test in Windows high contrast mode

---

## Build & Deployment

### Development Build

```bash
# Build once
npm run dev

# Watch for changes (rebuilds automatically)
npm run watch
```

### Production Build

```bash
# Optimized, minified build
npm run build
```

**Output**: Files generated in `public/build/`

```
public/build/
├── app.css           # Main app styles
├── app.js            # Main app scripts
├── auth.css          # Auth page styles
├── auth.js           # Auth page scripts
├── manifest.json     # Asset manifest
└── runtime.js        # Webpack runtime
```

### Deploy to Production

1. **Build assets**:
   ```bash
   npm run build
   ```

2. **Commit built assets** (if not using CI/CD):
   ```bash
   git add public/build/
   git commit -m "Build production assets"
   ```

3. **Clear Symfony cache**:
   ```bash
   php bin/console cache:clear --env=prod
   ```

4. **Deploy** to your server (method varies)

### Verifying Deployment

1. Check CSS loads: View source, confirm `<link>` tags point to `/build/auth.css` and `/build/app.css`
2. Check translations: Switch language selector between EN/FR
3. Check responsive: Test on mobile, tablet, desktop viewports
4. Run Lighthouse: Ensure performance score >90

---

## Troubleshooting

### CSS Changes Not Appearing

**Problem**: Modified SCSS but changes don't show in browser.

**Solutions**:
1. **Rebuild assets**: `npm run watch` (auto-rebuilds on save)
2. **Hard refresh**: Ctrl+Shift+R (Chrome) or Cmd+Shift+R (Mac)
3. **Clear Symfony cache**: `php bin/console cache:clear`
4. **Check entry point**: Ensure template uses correct `encore_entry_link_tags()`

### Webpack Build Errors

**Problem**: `npm run dev` fails with errors.

**Solutions**:
1. **Check syntax**: Validate SCSS syntax (missing semicolon, bracket, etc.)
2. **Import path**: Ensure `@import` paths are correct (relative to current file)
3. **Missing dependency**: Run `npm install` to ensure all packages installed
4. **Node version**: Ensure Node.js v18+ (`node --version`)

### Translation Keys Not Found

**Problem**: `{{ 'my.key'|trans }}` displays `my.key` instead of translation.

**Solutions**:
1. **Check YAML syntax**: Ensure proper indentation, no tabs
2. **Key exists**: Verify key exists in both `messages.en.yaml` and `messages.fr.yaml`
3. **Clear cache**: `php bin/console cache:clear`
4. **Locale**: Check current locale (`{{ app.request.locale }}`)

### Layout Breaks on Mobile

**Problem**: Page looks broken on small screens.

**Solutions**:
1. **Use responsive classes**: `col-12 col-md-6` (mobile first)
2. **Test in DevTools**: Chrome DevTools device mode
3. **Check media queries**: Ensure `@media (min-width: 768px)` syntax correct
4. **Viewport meta tag**: Ensure `<meta name="viewport">` in `base.html.twig`

### Component Not Rendering

**Problem**: `{% include 'components/_my_component.html.twig' %}` doesn't show.

**Solutions**:
1. **File exists**: Check path is correct (case-sensitive on Linux)
2. **Parameters**: Ensure required parameters passed (`with { ... }`)
3. **Twig error**: Check Symfony profiler for Twig errors
4. **Check Twig syntax**: Validate template syntax

### Colors Don't Match Design

**Problem**: Colors look different than expected.

**Solutions**:
1. **Use tokens**: Replace hardcoded colors with `$hermio-primary`, etc.
2. **Check contrast**: Ensure text/background contrast meets WCAG 2.1
3. **Browser rendering**: Different monitors show colors differently
4. **Gradient**: Ensure CSS gradients use proper syntax

---

## Quick Reference

### Common Commands

| Task | Command |
|------|---------|
| Install dependencies | `npm install` |
| Build assets (dev) | `npm run dev` |
| Watch for changes | `npm run watch` |
| Build assets (prod) | `npm run build` |
| Start Symfony server | `symfony server:start` |
| Clear cache | `php bin/console cache:clear` |
| Run accessibility check | `npx @axe-core/cli URL` |

### File Locations

| Type | Path |
|------|------|
| Design tokens | `app/assets/styles/_design-tokens.scss` |
| Auth styles | `app/assets/styles/auth.scss` |
| Dashboard styles | `app/assets/styles/dashboard.scss` |
| Components | `app/templates/components/` |
| Translations (EN) | `app/translations/messages.en.yaml` |
| Translations (FR) | `app/translations/messages.fr.yaml` |

### Bootstrap Classes Quick Reference

| Purpose | Classes |
|---------|---------|
| Container | `container-lg` |
| Row with gap | `row g-4` |
| Responsive cols | `col-12 col-md-6 col-xl-4` |
| Card | `card shadow-sm` |
| Card sections | `card-header`, `card-body`, `card-footer` |
| Button | `btn btn-primary` |
| Form input | `form-control` |
| Form label | `form-label` |

---

## Additional Resources

- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Symfony Twig Documentation](https://twig.symfony.com/doc/3.x/)
- [Webpack Encore Documentation](https://symfony.com/doc/current/frontend.html)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN Web Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)

---

**Need Help?**

- Check the [Troubleshooting](#troubleshooting) section above
- Review existing component code in `app/templates/components/`
- Consult the design tokens contract in `specs/001-backoffice-theme-redesign/contracts/design-tokens.scss`

**Happy coding! 🚀**
