# Data Model: Modern Admin Template for Authenticated Users

**Feature**: 009-admin-template  
**Date**: 2025-12-11

## Overview

This feature is **presentation-layer only** and does not introduce any new data entities, modify existing entities, or require database schema changes. The feature focuses exclusively on templates, layouts, and styles.

## No Data Model Changes

### Existing Entities Used (Read-Only)

The feature uses existing entities for **display purposes only**:

1. **User Entity** (`App\Entity\User`)
   - Used to display user name/email in header
   - Used to check authentication status
   - **No modifications required**

2. **Account Entity** (`App\Entity\Account`)
   - Used to display plan type (Free, Pro, Enterprise) on dashboard
   - Used to check account status
   - **No modifications required**

3. **Card Entity** (`App\Entity\Card`)
   - Used to display card usage statistics on dashboard
   - Used to list user's cards
   - **No modifications required**

### No New Entities

- No new database tables
- No new entity classes
- No new repository classes
- No new migrations

### No Data Relationships

- No new relationships between entities
- No modifications to existing relationships

## Data Display Requirements

### Dashboard Data

The dashboard page will display:

1. **Account Information** (from `User->getAccount()`):
   - Plan type: `$account->getPlanType()` → `PlanType` enum (FREE, PRO, ENTERPRISE)
   - Account status: Active/Inactive

2. **Card Usage Statistics** (from `QuotaService`):
   - Current usage: `$quotaService->getCurrentUsage($user)` → `int`
   - Quota limit: `$planType->getQuotaLimit()` → `int|null`
   - Usage percentage: Calculated from usage and limit

3. **Recent Activity** (optional, from existing `AuthenticationLogService`):
   - Recent login history
   - Recent card creation/edits

### Header Data

The header component will display:

1. **User Information** (from `$app.user`):
   - User name: `$user->getName()` or `$user->getEmail()`
   - User avatar: Optional, not in initial scope

2. **Page Title**:
   - Passed from individual page templates via Twig block

### Sidebar Data

The sidebar component will display:

1. **Navigation Items**:
   - Static list defined in template
   - Active route determined from `app.request.attributes.get('_route')`
   - No database queries required

## Data Access Patterns

### Controller Responsibilities

Controllers continue to work as before:
- Fetch data from services
- Pass data to templates
- No changes to controller logic

### Template Responsibilities

Templates receive data from controllers:
- Display user information
- Display account information
- Display card statistics
- No data fetching in templates

### Service Layer

Existing services continue to work unchanged:
- `AccountService`: Provides account data
- `QuotaService`: Provides quota/usage data
- `AuthenticationLogService`: Provides activity logs
- No new services required

## Summary

**Key Points**:
- ✅ No database schema changes
- ✅ No entity modifications
- ✅ No repository changes
- ✅ No service layer changes
- ✅ Only template rendering changes
- ✅ Uses existing entities for display only

This feature is a pure presentation-layer refactoring that improves the user interface without affecting the underlying data model or business logic.

