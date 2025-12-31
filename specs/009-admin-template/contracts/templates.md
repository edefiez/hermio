# Template Contracts: Modern Admin Template

**Feature**: 009-admin-template  
**Date**: 2025-12-11  
**Type**: Twig Template Structure

## Overview

This document defines the template structure and block contracts for the admin layout system. All authenticated pages will extend `base_admin.html.twig` and use defined blocks for customization.

## Base Admin Layout

### Template: `templates/base_admin.html.twig`

**Purpose**: Base layout for all authenticated pages with sidebar, header, and main content area.

**Structure**:
```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="admin-layout">
    {% include 'admin/_sidebar.html.twig' %}
    <div class="admin-content">
        {% include 'admin/_header.html.twig' %}
        <main class="admin-main">
            {% block admin_content %}{% endblock %}
        </main>
    </div>
</div>
{% endblock %}
```

**Blocks Defined**:
- `admin_content`: Main content area for page-specific content (required)

**Variables Available**:
- `app.user`: Current authenticated user
- `app.request`: Current request object
- `app.flashes`: Flash messages array

**Dependencies**:
- Extends `base.html.twig` for HTML structure and asset loading
- Includes `admin/_sidebar.html.twig` for sidebar navigation
- Includes `admin/_header.html.twig` for top header

---

## Sidebar Component

### Template: `templates/admin/_sidebar.html.twig`

**Purpose**: Reusable sidebar navigation component.

**Structure**:
```twig
<nav class="admin-sidebar" aria-label="Main navigation">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {% if app.request.attributes.get('_route') == 'app_dashboard' %}active{% endif %}" 
               href="{{ path('app_dashboard') }}">
                <i class="fas fa-home"></i>
                <span>{{ 'nav.dashboard'|trans }}</span>
            </a>
        </li>
        <!-- Additional nav items -->
    </ul>
</nav>
```

**Navigation Items**:
1. Dashboard → `app_dashboard`
2. My Cards → `app_card_index`
3. Account → `app_account_index`
4. Settings → `app_profile` (or dedicated settings route)

**Active State**:
- Uses `app.request.attributes.get('_route')` to determine current route
- Applies `active` class to matching navigation item
- Uses `aria-current="page"` for accessibility

**Responsive Behavior**:
- Desktop: Fixed sidebar, collapsible
- Mobile: Offcanvas component, toggleable

**Variables Required**:
- None (uses global `app.request`)

---

## Header Component

### Template: `templates/admin/_header.html.twig`

**Purpose**: Top header bar with page title, user info, and user menu.

**Structure**:
```twig
<header class="admin-header">
    <div class="admin-header-content">
        <button class="sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="admin-page-title">{% block page_title %}{% endblock %}</h1>
        <div class="admin-header-actions">
            <!-- User menu dropdown -->
        </div>
    </div>
</header>
```

**Blocks Defined**:
- `page_title`: Page title text (required in page templates)

**Components**:
1. **Hamburger Menu Button** (mobile only):
   - Toggles sidebar offcanvas
   - Visible only on mobile (`d-lg-none`)

2. **Page Title**:
   - Set via `page_title` block in page templates
   - Displays current page name

3. **User Menu Dropdown**:
   - User name/email display
   - Dropdown with Profile and Logout options
   - Language selector (preserved from existing navbar)

**Variables Required**:
- `app.user`: Current authenticated user

---

## Dashboard Template

### Template: `templates/admin/dashboard.html.twig`

**Purpose**: Dashboard page displaying account overview.

**Structure**:
```twig
{% extends 'base_admin.html.twig' %}

{% block page_title %}{{ 'dashboard.title'|trans }}{% endblock %}

{% block admin_content %}
<div class="dashboard-container">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <!-- Account plan card -->
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <!-- Card usage card -->
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <!-- Recent activity card -->
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

**Blocks Used**:
- `page_title`: Sets dashboard page title
- `admin_content`: Main dashboard content

**Variables Required** (from controller):
- `account`: Account entity
- `planType`: PlanType enum
- `quotaLimit`: int|null
- `currentUsage`: int
- `usagePercentage`: float|null
- `isUnlimited`: bool

**Card Layout**:
- Account Plan Card: Displays current plan type and status
- Card Usage Card: Displays usage statistics and progress bar
- Recent Activity Card: Displays recent login/card activity

---

## Page Template Pattern

### Standard Authenticated Page Template

**Pattern for migrating existing pages**:

```twig
{% extends 'base_admin.html.twig' %}

{% block page_title %}{{ 'page.title'|trans }}{% endblock %}

{% block admin_content %}
<!-- Page-specific content here -->
{% endblock %}
```

**Migration Steps**:
1. Change `{% extends 'base.html.twig' %}` to `{% extends 'base_admin.html.twig' %}`
2. Add `{% block page_title %}` with page title
3. Wrap existing content in `{% block admin_content %}`
4. Remove old navbar navigation (handled by sidebar)
5. Keep flash message handling (handled by base_admin)

---

## Template Block Reference

### Base Admin Layout Blocks

| Block Name | Required | Purpose | Default Content |
|------------|----------|---------|-----------------|
| `admin_content` | Yes | Main page content | None |
| `page_title` | Yes | Page title in header | None |
| `admin_styles` | No | Additional page-specific styles | None |
| `admin_scripts` | No | Additional page-specific scripts | None |

### Inherited Blocks (from base.html.twig)

| Block Name | Usage |
|------------|-------|
| `title` | Page title in `<title>` tag |
| `head` | Additional head content |
| `stylesheets` | Additional stylesheets (rarely needed) |
| `javascripts` | Additional JavaScript (rarely needed) |

---

## Component API

### Sidebar Component

**Include Syntax**:
```twig
{% include 'admin/_sidebar.html.twig' %}
```

**No parameters required** - uses global `app.request` for active state.

### Header Component

**Include Syntax**:
```twig
{% include 'admin/_header.html.twig' %}
```

**No parameters required** - uses global `app.user` and `page_title` block.

---

## Flash Messages

Flash messages are handled automatically by `base_admin.html.twig`:

```twig
{% for label, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ label }} alert-dismissible fade show">
            {{ message|trans }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    {% endfor %}
{% endfor %}
```

**Location**: Displayed at top of `admin_content` block.

---

## Translation Keys

All navigation labels and UI text use translation keys:

- `nav.dashboard`: Dashboard navigation label
- `nav.my_cards`: My Cards navigation label
- `nav.account`: Account navigation label
- `nav.settings`: Settings navigation label
- `dashboard.title`: Dashboard page title
- `header.user_menu.profile`: Profile menu item
- `header.user_menu.logout`: Logout menu item

**Translation Files**:
- `translations/messages.en.yaml`
- `translations/messages.fr.yaml`

