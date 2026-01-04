# Data Model: Back-Office Theme Redesign

**Feature**: Back-Office Theme Redesign  
**Date**: 2025-12-11  
**Type**: UI/Presentation Layer Only

---

## Overview

This feature is **purely presentational** and does not introduce new database entities, API endpoints, or backend logic. It redesigns the visual presentation layer using existing Twig templates and SCSS styles.

However, it **interacts with** and **displays** existing data from the Hermio application.

---

## Existing Data Entities (Read-Only)

### 1. User Entity
**Location**: `app/src/Entity/User.php`

**Fields Displayed in Dashboard**:
- `email` (string): User's email address
- Plan-related data (via relationship)
- Account creation date
- Account status

**Context**: User information is displayed in dashboard cards (account overview, profile page).

**No Modifications**: This feature does not modify the User entity.

---

### 2. Plan/Subscription Data
**Location**: Service layer or entity (existing)

**Fields Displayed**:
- `planType` (enum): FREE, PRO, ENTERPRISE
- `quotaLimit` (integer|null): Maximum cards allowed (null = unlimited)
- `quotaUsage` (integer): Current number of cards created
- `activatedAt` (datetime): When plan was activated
- `lastModified` (datetime): Last plan change

**Context**: Plan information is displayed in dashboard cards ("My Plan" page, account overview).

**No Modifications**: This feature does not modify plan/subscription logic.

---

### 3. Business Card Data
**Location**: Card entity (existing)

**Fields Displayed**:
- Card count (aggregate): Total number of cards created by user
- Card statistics (aggregate): Active cards, QR code scans, etc.
- Recent activity: Last card created/updated

**Context**: Card statistics are displayed in dashboard summary cards.

**No Modifications**: This feature does not modify card entities.

---

## Visual Design Entities (Non-Database)

These are **design-time structures** defined in SCSS and Twig, not stored in a database.

### 1. Design Tokens
**Location**: `app/assets/styles/_design-tokens.scss`

**Structure**:
```scss
// Color Palette
$hermio-primary: #4F46E5;
$hermio-secondary: #10B981;
$hermio-success: #10B981;
$hermio-warning: #F59E0B;
$hermio-danger: #EF4444;
$hermio-info: #3B82F6;

// Gray Scale
$hermio-gray-50: #F9FAFB;
$hermio-gray-900: #111827;

// Spacing Scale
$hermio-space-xs: 0.5rem;
$hermio-space-sm: 1rem;
$hermio-space-md: 1.5rem;
$hermio-space-lg: 2rem;
$hermio-space-xl: 3rem;

// Typography
$hermio-text-base: 1rem;
$hermio-text-lg: 1.125rem;
$hermio-text-2xl: 1.5rem;

// Border Radius
$hermio-radius-md: 0.5rem;
$hermio-radius-lg: 0.75rem;

// Shadows
$hermio-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
$hermio-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
```

**Purpose**: Define visual consistency across all back-office pages.

**Validation Rules**: 
- Colors must meet WCAG 2.1 AA contrast ratios
- Spacing follows 0.25rem (4px) increments
- Typography scale follows 1.125x ratio (major second)

---

### 2. Component State Variants
**Location**: Component SCSS files

**Card Component States**:
```scss
.card {
  // Default state
  background: white;
  border-radius: $hermio-radius-lg;
  box-shadow: $hermio-shadow-sm;
  
  // Hover state
  &:hover {
    box-shadow: $hermio-shadow-md;
    transform: translateY(-2px);
  }
  
  // Focus state
  &:focus-within {
    outline: 2px solid $hermio-primary;
    outline-offset: 2px;
  }
}
```

**Form Component States**:
```scss
.form-control {
  // Default
  border-color: $hermio-gray-300;
  
  // Focus
  &:focus {
    border-color: $hermio-primary;
    box-shadow: 0 0 0 0.25rem rgba($hermio-primary, 0.25);
  }
  
  // Error
  &.is-invalid {
    border-color: $hermio-danger;
  }
  
  // Disabled
  &:disabled {
    background: $hermio-gray-100;
    color: $hermio-gray-500;
  }
}
```

---

### 3. Layout Structures
**Location**: Twig templates

**Auth Layout Structure**:
```twig
{# templates/auth/_base.html.twig #}
<div class="auth-container">
  <div class="auth-row">
    <div class="auth-form-column">
      {% block form_content %}{% endblock %}
    </div>
    <div class="auth-illustration-column">
      {% block illustration %}{% endblock %}
    </div>
  </div>
</div>
```

**Dashboard Layout Structure**:
```twig
{# templates/base.html.twig (extended by dashboard pages) #}
<body>
  <nav><!-- Top navbar --></nav>
  <main class="dashboard-container">
    {% block body %}
      <div class="container-lg">
        <div class="row g-4">
          {% block dashboard_cards %}{% endblock %}
        </div>
      </div>
    {% endblock %}
  </main>
</body>
```

---

### 4. Responsive Breakpoints
**Location**: Bootstrap 5 defaults + custom media queries

**Breakpoint Structure**:
```scss
// Mobile First
.auth-container {
  // Mobile (<768px): Default stacked layout
  display: flex;
  flex-direction: column;
  
  @media (min-width: 768px) {
    // Tablet/Desktop: Two-column layout
    flex-direction: row;
  }
}

.dashboard-grid {
  // Mobile: 1 column
  grid-template-columns: 1fr;
  
  @media (min-width: 768px) {
    // Tablet: 2 columns
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 1200px) {
    // Desktop: 3 columns
    grid-template-columns: repeat(3, 1fr);
  }
}
```

**Validation Rules**:
- All layouts must be tested from 320px to 2560px
- No horizontal scrolling at any breakpoint
- Touch targets ≥44px on mobile (WCAG guideline)

---

## Component Data Contracts

### Dashboard Card Component
**Template**: `templates/components/_dashboard_card.html.twig`

**Input Parameters**:
```twig
{
  'title': string (required),
  'icon': string (optional, FontAwesome class),
  'content': string|raw (required),
  'actions': string|raw (optional),
  'variant': string (optional: 'primary', 'secondary', 'success')
}
```

**Example Usage**:
```twig
{% include 'components/_dashboard_card.html.twig' with {
  'title': 'Account Overview'|trans,
  'icon': 'fas fa-user',
  'content': '<dl>...</dl>',
  'variant': 'primary'
} %}
```

---

### Page Header Component
**Template**: `templates/components/_page_header.html.twig`

**Input Parameters**:
```twig
{
  'title': string (required),
  'subtitle': string (optional),
  'icon': string (optional),
  'actions': string|raw (optional, button/link markup)
}
```

**Example Usage**:
```twig
{% include 'components/_page_header.html.twig' with {
  'title': 'My Dashboard'|trans,
  'subtitle': 'Welcome back, ' ~ user.email,
  'actions': '<a href="..." class="btn btn-primary">Action</a>'
} %}
```

---

## Translation Data Structure

### New Translation Keys (EN/FR)

**Authentication Pages**:
```yaml
auth:
  login:
    title: "Login to Hermio"
    subtitle: "Access your digital business cards"
    email_label: "Email Address"
    password_label: "Password"
    remember_me: "Remember me"
    submit: "Sign In"
    forgot_password: "Forgot your password?"
    no_account: "Don't have an account?"
    register_link: "Create one now"
  
  register:
    title: "Create Your Account"
    subtitle: "Start creating digital business cards"
    email_label: "Email Address"
    password_label: "Password"
    confirm_password_label: "Confirm Password"
    submit: "Create Account"
    have_account: "Already have an account?"
    login_link: "Sign in here"
  
  illustration:
    alt_text: "Hermio business card with QR code"
    tagline: "Your professional identity, one scan away"
```

**Dashboard Components**:
```yaml
dashboard:
  cards:
    account_overview: "Account Overview"
    plan_summary: "My Plan"
    card_stats: "Card Statistics"
    recent_activity: "Recent Activity"
  
  account:
    email: "Email"
    plan: "Current Plan"
    quota: "Quota"
    joined: "Member Since"
  
  actions:
    view_details: "View Details"
    upgrade_plan: "Upgrade Plan"
    manage_cards: "Manage Cards"
```

**Validation**: Both `messages.en.yaml` and `messages.fr.yaml` must have identical key structures.

---

## Accessibility Data Model

### Focus Management Structure
```scss
// Focus indicators for interactive elements
:focus-visible {
  outline: 2px solid $hermio-primary;
  outline-offset: 2px;
  border-radius: $hermio-radius-sm;
}

// Skip link (keyboard navigation)
.skip-to-content {
  position: absolute;
  left: -9999px;
  
  &:focus {
    left: 0;
    top: 0;
    z-index: 9999;
  }
}
```

### ARIA Landmark Structure
```html
<!-- Authentication Pages -->
<body>
  <main role="main" aria-label="Authentication">
    <form role="form" aria-label="Login form">
      <!-- Form fields -->
    </form>
  </main>
  <aside role="complementary" aria-label="Branding">
    <img role="presentation" alt="">
  </aside>
</body>

<!-- Dashboard Pages -->
<body>
  <nav role="navigation" aria-label="Main navigation">
    <!-- Navbar -->
  </nav>
  <main role="main" aria-label="Dashboard">
    <section aria-label="Account overview">
      <!-- Cards -->
    </section>
  </main>
</body>
```

---

## Asset Data Model

### Illustration Asset
**File**: `app/assets/images/auth-illustration.svg`

**Specifications**:
- Format: SVG (vector)
- Dimensions: 600x800px artboard
- Colors: Use design token hex values
- File size: Target <20KB
- Elements:
  - Business card mockup
  - QR code (points to https://hermio.cards)
  - Optional: smartphone graphic
  - Optional: subtle background pattern

**Fallback**:
```scss
.auth-illustration-column {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  
  img {
    // SVG illustration overlays gradient
    mix-blend-mode: multiply;
  }
}
```

---

## Form Validation Data Model

### Client-Side Validation (HTML5)
```html
<input type="email" 
       id="email" 
       name="_username" 
       required 
       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
       aria-describedby="email-help">
```

### Server-Side Validation (Symfony)
- Existing Symfony validation constraints
- No changes to validation logic
- Error messages styled with Bootstrap feedback classes

---

## State Transitions

### Authentication Flow
```
Unauthenticated → Login Page → Form Submission → {
  Success → Redirect to Dashboard
  Error → Display error message (stay on login page)
}
```

### Dashboard Navigation
```
Dashboard → Click Card Action → {
  View Details → Navigate to detail page
  External Link → Open in new tab
}
```

**No complex state management**: All state handled by server-side Symfony routing and sessions.

---

## Summary

| Entity Type | Count | Storage | Mutability |
|-------------|-------|---------|------------|
| **Database Entities** | 0 new, 3 read | PostgreSQL | Read-only |
| **Design Tokens** | ~50 variables | SCSS file | Design-time |
| **Twig Components** | 4 components | Template files | Development-time |
| **Translation Keys** | ~30 new keys | YAML files | Development-time |
| **Assets** | 1 SVG illustration | Filesystem | Static |

**Key Insight**: This feature is a **presentation layer redesign** with no backend data changes. All complexity is in the visual design system (SCSS tokens, Twig components, responsive layouts) rather than data modeling.
