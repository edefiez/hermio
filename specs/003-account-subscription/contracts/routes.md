# Route Contracts: Account Management / Subscription Model

**Feature**: 003-account-subscription  
**Date**: 2025-12-08  
**Type**: Symfony Routes (Twig-based web application)

## Overview

This document defines the route contracts for the account management and subscription system. Since this is a Twig-based web application (not a REST API), routes return HTML responses rendered via Twig templates.

## User-Facing Routes

### 1. My Plan Page

**Route**: `GET /account/my-plan`  
**Controller**: `AccountController::myPlan()`  
**Template**: `account/my_plan.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Display user's current subscription plan, quota limits, and usage

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Parameters: None

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'account' => Account,           // User's account entity
    'planType' => PlanType,        // Current plan (FREE/PRO/ENTERPRISE)
    'quotaLimit' => int|null,      // Card limit (null for unlimited)
    'currentUsage' => int,          // Current number of cards created
    'usagePercentage' => float,     // Usage percentage (0-100, null if unlimited)
    'isUnlimited' => bool,          // True if Enterprise plan
]
```

**Success Scenarios**:
- User has Free plan: Shows "Free", limit 1, current usage count
- User has Pro plan: Shows "Pro", limit 10, current usage count and percentage
- User has Enterprise plan: Shows "Enterprise", "Unlimited", current usage count

**Error Scenarios**:
- 401 Unauthorized: User not authenticated → redirect to login
- 404 Not Found: User has no account → create default account and redirect

---

### 2. Account Management Page

**Route**: `GET /account`  
**Controller**: `AccountController::index()`  
**Template**: `account/index.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Central hub for account management with links to plan details and settings

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Parameters: None

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'account' => Account,           // User's account entity
    'user' => User,                 // Current user
    'planSummary' => [              // Summary for quick display
        'type' => PlanType,
        'limit' => int|null,
        'usage' => int,
    ],
]
```

**Success Scenarios**:
- User accesses account page: Shows account overview with links to plan details

**Error Scenarios**:
- 401 Unauthorized: User not authenticated → redirect to login

---

## Administrative Routes

### 3. Admin: List All Accounts

**Route**: `GET /admin/accounts`  
**Controller**: `AdminAccountController::index()`  
**Template**: `admin/account/index.html.twig`  
**Authorization**: `ROLE_ADMIN` (administrators only)

**Purpose**: Display list of all user accounts with their plan types and usage

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Authorization: ROLE_ADMIN required
- Parameters: 
  - `page` (optional): Page number for pagination (default: 1)
  - `plan` (optional): Filter by plan type (free/pro/enterprise)

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'accounts' => Paginator,        // Paginated list of Account entities
    'totalUsers' => int,            // Total number of users
    'planCounts' => [               // Counts by plan type
        'free' => int,
        'pro' => int,
        'enterprise' => int,
    ],
    'currentFilter' => string|null, // Current plan filter (if any)
]
```

**Success Scenarios**:
- Admin views all accounts: Shows paginated list with plan types
- Admin filters by plan: Shows only accounts with selected plan type

**Error Scenarios**:
- 403 Forbidden: User lacks ROLE_ADMIN → show access denied page
- 401 Unauthorized: User not authenticated → redirect to login

---

### 4. Admin: View User Account Details

**Route**: `GET /admin/accounts/{id}`  
**Controller**: `AdminAccountController::show(int $id)`  
**Template**: `admin/account/show.html.twig`  
**Authorization**: `ROLE_ADMIN` (administrators only)

**Purpose**: View detailed information about a specific user's account and plan

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Authorization: ROLE_ADMIN required
- Parameters:
  - `id` (path): User ID

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'account' => Account,           // User's account entity
    'user' => User,                 // User entity
    'planType' => PlanType,         // Current plan
    'quotaLimit' => int|null,       // Card limit
    'currentUsage' => int,          // Current card count
    'canDowngrade' => bool,         // True if downgrade is allowed
    'downgradeWarning' => string|null, // Warning message if downgrade would exceed quota
]
```

**Success Scenarios**:
- Admin views account: Shows full account details with plan management form

**Error Scenarios**:
- 404 Not Found: Account with given ID doesn't exist
- 403 Forbidden: User lacks ROLE_ADMIN
- 401 Unauthorized: User not authenticated

---

### 5. Admin: Change User Plan

**Route**: `POST /admin/accounts/{id}/change-plan`  
**Controller**: `AdminAccountController::changePlan(int $id, Request $request)`  
**Template**: Redirects to account details page  
**Authorization**: `ROLE_ADMIN` (administrators only)

**Purpose**: Update a user's subscription plan

**Request**:
- Method: POST
- Authentication: Required (session-based)
- Authorization: ROLE_ADMIN required
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): User ID
  - `plan_type` (form): New plan type (free/pro/enterprise)
  - `confirm_downgrade` (form, optional): Confirmation flag if downgrading with excess content

**Response**:
- Success: 302 Redirect to `/admin/accounts/{id}` with success flash message
- Error: 200 OK with form errors displayed

**Success Scenarios**:
- Plan upgraded: Success message, quota limit updated immediately
- Plan downgraded (within quota): Success message, quota limit updated
- Plan downgraded (with confirmation): Success message, quota limit updated

**Error Scenarios**:
- 400 Bad Request: Invalid plan type → form error
- 400 Bad Request: Downgrade would exceed quota without confirmation → form error
- 404 Not Found: Account doesn't exist
- 403 Forbidden: User lacks ROLE_ADMIN
- 401 Unauthorized: User not authenticated

**Flash Messages**:
- Success: "Plan successfully changed to {planType}"
- Error: "Cannot downgrade: user has {count} cards, new plan allows {limit}"

---

## Route Naming Conventions

All routes follow Symfony naming conventions:
- User-facing: `/account/*`
- Admin-facing: `/admin/accounts/*`
- Route names: `app_account_*` and `app_admin_account_*`

## Security Considerations

- All routes require authentication (except public pages)
- Admin routes require `ROLE_ADMIN` authorization
- CSRF protection on all POST routes
- Input validation on all form submissions
- User can only access their own account (except admins)

## Internationalization

All route responses support internationalization:
- Routes detect locale from session/request
- Templates use `{{ 'key'|trans }}` for all text
- Flash messages are translated
- Form labels and errors are translated

