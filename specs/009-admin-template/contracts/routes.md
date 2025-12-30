# Route Contracts: Modern Admin Template

**Feature**: 009-admin-template  
**Date**: 2025-12-11  
**Type**: Symfony Routes (Twig-based web application)

## Overview

This document defines the route contracts for the admin template feature. Since this is a Twig-based web application (not a REST API), routes return HTML responses rendered via Twig templates.

## New Routes

### 1. Dashboard Page

**Route**: `GET /dashboard`  
**Route Name**: `app_dashboard`  
**Controller**: `App\Controller\DashboardController::index()`  
**Template**: `admin/dashboard.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Display account overview dashboard as default landing page for authenticated users.

**Request**:
- Method: GET
- Authentication: Required (session-based, ROLE_USER)
- Parameters: None
- Query Parameters: None

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page using `base_admin.html.twig` layout

**Response Data** (available in template):
```php
[
    'account' => Account,           // User's account entity
    'planType' => PlanType,        // Current plan (FREE/PRO/ENTERPRISE)
    'quotaLimit' => int|null,      // Card limit (null for unlimited)
    'currentUsage' => int,          // Current number of cards created
    'usagePercentage' => float|null, // Usage percentage (0-100, null if unlimited)
    'isUnlimited' => bool,          // True if Enterprise plan
]
```

**Success Scenarios**:
- User has Free plan: Dashboard displays "Free" plan, limit 1, current usage count
- User has Pro plan: Dashboard displays "Pro" plan, limit 10, current usage count and percentage
- User has Enterprise plan: Dashboard displays "Enterprise" plan, "Unlimited", current usage count

**Error Scenarios**:
- 401 Unauthorized: User not authenticated → redirect to login page
- 404 Not Found: User has no account → create default account and redirect (handled by controller)

**Controller Implementation**:
```php
#[Route('/dashboard', name: 'app_dashboard')]
#[IsGranted('ROLE_USER')]
public function index(AccountService $accountService, QuotaService $quotaService): Response
{
    $user = $this->getUser();
    $account = $user->getAccount() ?? $accountService->createDefaultAccount($user);
    
    $planType = $account->getPlanType();
    $quotaLimit = $planType->getQuotaLimit();
    $currentUsage = $quotaService->getCurrentUsage($user);
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
```

---

## Modified Routes

### Security Configuration Update

**File**: `config/packages/security.yaml`

**Change**: Update `default_target_path` in form_login configuration

**Before**:
```yaml
form_login:
    login_path: app_login
    check_path: app_login
    enable_csrf: true
    default_target_path: app_home
```

**After**:
```yaml
form_login:
    login_path: app_login
    check_path: app_login
    enable_csrf: true
    default_target_path: app_dashboard
```

**Purpose**: Redirect authenticated users to dashboard after successful login instead of home page.

**Impact**: 
- All successful logins redirect to `/dashboard`
- Existing authenticated users accessing `/` may be redirected to dashboard
- Public home page (`app_home`) remains unchanged and accessible to unauthenticated users

---

## Existing Routes (No Changes)

The following routes continue to work unchanged but will use the new `base_admin.html.twig` layout:

- `GET /card` → `app_card_index` (My Cards list)
- `GET /card/create` → `app_card_create` (Create card)
- `GET /card/{id}/edit` → `app_card_edit` (Edit card)
- `GET /account` → `app_account_index` (Account management)
- `GET /account/my-plan` → `app_account_my_plan` (My Plan)
- `GET /profile` → `app_profile` (User profile)
- `GET /subscription/manage` → `app_subscription_manage` (Subscription management)
- `GET /subscription/payments` → `app_subscription_payments` (Payment history)
- `GET /admin/account` → `app_admin_account_index` (Admin: Account list)
- `GET /admin/webhook` → `app_admin_webhook_index` (Admin: Webhook management)

**Note**: These routes only change their template inheritance (from `base.html.twig` to `base_admin.html.twig`). No controller or route definition changes are required.

---

## Public Routes (Unchanged)

The following routes remain unchanged and continue to use `base.html.twig`:

- `GET /` → `app_home` (Public home page)
- `GET /login` → `app_login` (Login page)
- `GET /register` → `app_register` (Registration page)
- `GET /public/card/{key}` → `app_public_card` (Public card view)

**Constraint**: These routes MUST NOT be modified as per feature requirements (C-001).

---

## Route Authorization

### Authentication Requirements

All authenticated routes require:
- Valid user session
- `ROLE_USER` role (enforced via `#[IsGranted('ROLE_USER')]` attribute)

### Authorization Flow

1. Unauthenticated user accesses authenticated route
2. Symfony Security redirects to `/login`
3. User logs in successfully
4. Symfony Security redirects to `default_target_path` (`app_dashboard`)
5. User sees dashboard with admin layout

---

## Route Testing

### Manual Testing Scenarios

1. **Login Redirect**:
   - Log out
   - Log in
   - Verify redirect to `/dashboard`

2. **Dashboard Access**:
   - Access `/dashboard` while authenticated
   - Verify dashboard displays with admin layout
   - Verify account information is correct

3. **Navigation**:
   - Click sidebar navigation items
   - Verify routes work correctly
   - Verify active item is highlighted

4. **Public Pages**:
   - Access `/` while logged out
   - Verify home page uses old layout (not admin layout)
   - Access `/login` and `/register`
   - Verify they use old layout

---

## Route Contracts Summary

| Route | Method | Auth | Layout | Status |
|-------|--------|------|--------|--------|
| `/dashboard` | GET | ROLE_USER | base_admin | New |
| `/card/*` | GET | ROLE_USER | base_admin | Modified (template only) |
| `/account/*` | GET | ROLE_USER | base_admin | Modified (template only) |
| `/profile` | GET | ROLE_USER | base_admin | Modified (template only) |
| `/subscription/*` | GET | ROLE_USER | base_admin | Modified (template only) |
| `/admin/*` | GET | ROLE_ADMIN | base_admin | Modified (template only) |
| `/` | GET | Public | base | Unchanged |
| `/login` | GET | Public | base | Unchanged |
| `/register` | GET | Public | base | Unchanged |
| `/public/*` | GET | Public | base_public | Unchanged |

