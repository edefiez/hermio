# Quickstart Guide: Modern Admin Template

**Feature**: 009-admin-template  
**Date**: 2025-12-11

## Overview

This guide provides step-by-step instructions for implementing the modern admin template layout for authenticated users. The implementation is divided into logical phases that can be completed incrementally.

## Prerequisites

- Symfony 8.0+ project with Twig configured
- Bootstrap 5.x installed and configured
- Webpack Encore configured for SCSS compilation
- Font Awesome icons available
- Existing authenticated pages (cards, account, profile, etc.)

## Implementation Phases

### Phase 1: Create Base Admin Layout

#### Step 1.1: Create Base Admin Template

Create `app/templates/base_admin.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="admin-layout">
    {% include 'admin/_sidebar.html.twig' %}
    <div class="admin-content">
        {% include 'admin/_header.html.twig' %}
        <main class="admin-main">
            {% for label, messages in app.flashes %}
                {% for message in messages %}
                    <div class="alert alert-{{ label == 'error' ? 'danger' : label }} alert-dismissible fade show" role="alert">
                        {{ message|trans }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                {% endfor %}
            {% endfor %}
            {% block admin_content %}{% endblock %}
        </main>
    </div>
</div>
{% endblock %}
```

#### Step 1.2: Create Sidebar Component

Create `app/templates/admin/_sidebar.html.twig`:

```twig
<nav class="admin-sidebar offcanvas offcanvas-start" id="adminSidebar" aria-label="Main navigation">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">{{ 'nav.main'|trans }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {% if app.request.attributes.get('_route') == 'app_dashboard' %}active{% endif %}" 
                   href="{{ path('app_dashboard') }}"
                   {% if app.request.attributes.get('_route') == 'app_dashboard' %}aria-current="page"{% endif %}>
                    <i class="fas fa-home me-2"></i>
                    <span>{{ 'nav.dashboard'|trans }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {% if app.request.attributes.get('_route') starts with 'app_card' %}active{% endif %}" 
                   href="{{ path('app_card_index') }}"
                   {% if app.request.attributes.get('_route') starts with 'app_card' %}aria-current="page"{% endif %}>
                    <i class="fas fa-id-card me-2"></i>
                    <span>{{ 'nav.my_cards'|trans }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {% if app.request.attributes.get('_route') starts with 'app_account' %}active{% endif %}" 
                   href="{{ path('app_account_index') }}"
                   {% if app.request.attributes.get('_route') starts with 'app_account' %}aria-current="page"{% endif %}>
                    <i class="fas fa-user-circle me-2"></i>
                    <span>{{ 'nav.account'|trans }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {% if app.request.attributes.get('_route') == 'app_profile' %}active{% endif %}" 
                   href="{{ path('app_profile') }}"
                   {% if app.request.attributes.get('_route') == 'app_profile' %}aria-current="page"{% endif %}>
                    <i class="fas fa-cog me-2"></i>
                    <span>{{ 'nav.settings'|trans }}</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
```

#### Step 1.3: Create Header Component

Create `app/templates/admin/_header.html.twig`:

```twig
<header class="admin-header">
    <div class="admin-header-content d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <button class="sidebar-toggle d-lg-none me-3 btn btn-link" 
                    type="button" 
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#adminSidebar"
                    aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="admin-page-title mb-0">{% block page_title %}{% endblock %}</h1>
        </div>
        <div class="admin-header-actions d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user me-2"></i>
                    {{ app.user.email }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                    <li>
                        <a class="dropdown-item" href="{{ path('app_profile') }}">
                            <i class="fas fa-user me-2"></i>{{ 'header.user_menu.profile'|trans }}
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ path('app_logout') }}">
                            <i class="fas fa-sign-out-alt me-2"></i>{{ 'header.user_menu.logout'|trans }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
```

### Phase 2: Create Dashboard

#### Step 2.1: Create Dashboard Controller

Create `app/src/Controller/DashboardController.php`:

```php
<?php

namespace App\Controller;

use App\Service\AccountService;
use App\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private AccountService $accountService,
        private QuotaService $quotaService
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
            $user->setAccount($account);
        }

        $planType = $account->getPlanType();
        $quotaLimit = $planType->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $usagePercentage = $quotaLimit !== null ? ($currentUsage / $quotaLimit) * 100 : null;
        $isUnlimited = $planType->isUnlimited();

        return $this->render('admin/dashboard.html.twig', [
            'account' => $account,
            'planType' => $planType,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'usagePercentage' => $usagePercentage,
            'isUnlimited' => $isUnlimited,
        ]);
    }
}
```

#### Step 2.2: Create Dashboard Template

Create `app/templates/admin/dashboard.html.twig`:

```twig
{% extends 'base_admin.html.twig' %}

{% block page_title %}{{ 'dashboard.title'|trans }}{% endblock %}

{% block admin_content %}
<div class="dashboard-container">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header card-header-gradient">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-crown me-2"></i>{{ 'dashboard.plan.title'|trans }}
                    </h5>
                </div>
                <div class="card-body">
                    <h3 class="mb-0">{{ planType.getDisplayName()|trans }}</h3>
                    {% if isUnlimited %}
                        <p class="text-muted mb-0">{{ 'dashboard.plan.unlimited'|trans }}</p>
                    {% else %}
                        <p class="text-muted mb-0">{{ 'dashboard.plan.limited'|trans }}</p>
                    {% endif %}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-id-card me-2"></i>{{ 'dashboard.usage.title'|trans }}
                    </h5>
                </div>
                <div class="card-body">
                    <h3 class="mb-0">{{ currentUsage }} / {{ quotaLimit ?? '∞' }}</h3>
                    {% if usagePercentage is not null %}
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: {{ usagePercentage }}%"
                                 aria-valuenow="{{ usagePercentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ usagePercentage|round }}%
                            </div>
                        </div>
                    {% endif %}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>{{ 'dashboard.quick_actions.title'|trans }}
                    </h5>
                </div>
                <div class="card-body">
                    <a href="{{ path('app_card_create') }}" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-plus me-2"></i>{{ 'card.create.button'|trans }}
                    </a>
                    <a href="{{ path('app_account_my_plan') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-chart-line me-2"></i>{{ 'account.view_plan_details'|trans }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

### Phase 3: Create Styles

#### Step 3.1: Create Admin Layout SCSS

Create `app/assets/styles/admin-layout.scss`:

```scss
// Admin Layout Styles
.admin-layout {
    display: flex;
    min-height: 100vh;
}

.admin-sidebar {
    width: 250px;
    background: #fff;
    border-right: 1px solid #dee2e6;
    position: fixed;
    height: 100vh;
    top: 0;
    left: 0;
    z-index: 1000;
    overflow-y: auto;
    
    @media (max-width: 991.98px) {
        // Offcanvas handles mobile
    }
    
    .nav-link {
        padding: 0.75rem 1rem;
        color: #495057;
        border-radius: 0.375rem;
        margin: 0.25rem 0;
        
        &:hover {
            background-color: #f8f9fa;
        }
        
        &.active {
            background-color: #e7f3ff;
            color: #0d6efd;
            font-weight: 600;
        }
    }
}

.admin-content {
    flex: 1;
    margin-left: 250px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    
    @media (max-width: 991.98px) {
        margin-left: 0;
    }
}

.admin-header {
    height: 70px;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 0 1.5rem;
    position: sticky;
    top: 0;
    z-index: 100;
}

.admin-main {
    flex: 1;
    padding: 1.5rem;
    background: #f8f9fa;
}

// Sidebar collapsed state
.admin-layout.sidebar-collapsed {
    .admin-sidebar {
        width: 80px;
        
        .nav-link span {
            display: none;
        }
    }
    
    .admin-content {
        margin-left: 80px;
    }
}
```

#### Step 3.2: Import Admin Layout Styles

Update `app/assets/styles/app.css` or main SCSS file to import:

```scss
@import 'admin-layout';
```

### Phase 4: Add JavaScript

#### Step 4.1: Add Sidebar Toggle JavaScript

Add to `app/assets/app.js`:

```javascript
// Admin sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    // Load sidebar state from localStorage
    const sidebarCollapsed = localStorage.getItem('hermio-admin-sidebar-collapsed') === 'true';
    if (sidebarCollapsed) {
        document.body.classList.add('sidebar-collapsed');
    }
    
    // Toggle sidebar collapse
    const collapseButton = document.querySelector('.sidebar-collapse-btn');
    if (collapseButton) {
        collapseButton.addEventListener('click', function() {
            const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('hermio-admin-sidebar-collapsed', isCollapsed);
        });
    }
    
    // Close mobile sidebar on navigation item click
    const sidebarLinks = document.querySelectorAll('#adminSidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('adminSidebar'));
            if (offcanvas) {
                offcanvas.hide();
            }
        });
    });
});
```

### Phase 5: Update Security Configuration

#### Step 5.1: Update Login Redirect

Update `app/config/packages/security.yaml`:

```yaml
form_login:
    login_path: app_login
    check_path: app_login
    enable_csrf: true
    default_target_path: app_dashboard  # Changed from app_home
```

### Phase 6: Migrate Existing Pages

#### Step 6.1: Update Template Inheritance

For each authenticated page template, change:

**Before**:
```twig
{% extends 'base.html.twig' %}

{% block body %}
<!-- content -->
{% endblock %}
```

**After**:
```twig
{% extends 'base_admin.html.twig' %}

{% block page_title %}{{ 'page.title'|trans }}{% endblock %}

{% block admin_content %}
<!-- content -->
{% endblock %}
```

**Pages to migrate**:
- `card/index.html.twig`
- `card/create.html.twig`
- `card/edit.html.twig`
- `account/index.html.twig`
- `account/my_plan.html.twig`
- `profile/index.html.twig`
- `subscription/manage.html.twig`
- `subscription/payments.html.twig`
- `admin/account/index.html.twig`
- `admin/webhook/index.html.twig`

### Phase 7: Add Translations

#### Step 7.1: Add Translation Keys

Add to `app/translations/messages.en.yaml`:

```yaml
nav:
    main: Main Navigation
    dashboard: Dashboard
    my_cards: My Cards
    account: Account
    settings: Settings

dashboard:
    title: Dashboard
    plan:
        title: Plan
        unlimited: Unlimited
        limited: Limited
    usage:
        title: Card Usage
    quick_actions:
        title: Quick Actions

header:
    user_menu:
        profile: Profile
        logout: Logout
```

Add French translations to `app/translations/messages.fr.yaml`:

```yaml
nav:
    main: Navigation principale
    dashboard: Tableau de bord
    my_cards: Mes cartes
    account: Compte
    settings: Paramètres

dashboard:
    title: Tableau de bord
    plan:
        title: Plan
        unlimited: Illimité
        limited: Limité
    usage:
        title: Utilisation des cartes
    quick_actions:
        title: Actions rapides

header:
    user_menu:
        profile: Profil
        logout: Déconnexion
```

## Testing Checklist

- [ ] Dashboard loads correctly after login
- [ ] Sidebar navigation works on desktop
- [ ] Sidebar collapses/expands on desktop
- [ ] Sidebar state persists across page navigations
- [ ] Mobile sidebar toggles correctly
- [ ] Active navigation item is highlighted
- [ ] Header displays correct page title
- [ ] User menu dropdown works
- [ ] All authenticated pages use new layout
- [ ] Public pages (home, login, register) unchanged
- [ ] Flash messages display correctly
- [ ] Responsive design works on mobile/tablet/desktop
- [ ] Keyboard navigation works
- [ ] Translations work (EN/FR)

## Next Steps

After completing this quickstart:

1. Review and refine dashboard content
2. Add additional dashboard widgets if needed
3. Test accessibility with screen readers
4. Optimize performance if needed
5. Add any additional navigation items for future features

