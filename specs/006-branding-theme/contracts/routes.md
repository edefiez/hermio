# Route Contracts: Branding & Theme (Pro / Enterprise)

**Feature**: 006-branding-theme  
**Date**: December 10, 2025  
**Type**: Symfony Routes (Twig-based web application)

## Overview

This document defines the route contracts for the branding configuration system. Routes include authenticated user-facing routes for configuring branding (colors, logo, templates) and integration with public card pages. Since this is a Twig-based web application, routes return HTML responses rendered via Twig templates.

## Authenticated Routes

### 1. Branding Configuration Page

**Route**: `GET /branding/configure`  
**Controller**: `BrandingController::configure()`  
**Template**: `branding/configure.html.twig`  
**Authorization**: ROLE_USER (Pro/Enterprise plans only)

**Purpose**: Display branding configuration interface for Pro/Enterprise account owners

**Request**:
- Method: GET
- Authentication: Required (ROLE_USER)
- Plan Requirement: Pro or Enterprise

**Response**:
- Success: 200 OK
- Access Denied: 403 Forbidden (Free plan users)
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'account' => Account,              // Current user's account
    'branding' => AccountBranding|null, // Existing branding configuration (if any)
    'form' => FormView,                // Branding configuration form
    'canConfigureTemplate' => bool,    // Whether user can configure templates (Enterprise only)
]
```

**Success Scenarios**:
- Pro/Enterprise user: Shows branding configuration form with current values (if configured)
- Pro/Enterprise user with existing branding: Pre-fills form with current branding values

**Error Scenarios**:
- 403 Forbidden: Free plan user attempts to access (redirect to upgrade page)
- 302 Redirect: User not authenticated (redirect to login)

---

### 2. Save Branding Configuration

**Route**: `POST /branding/configure`  
**Controller**: `BrandingController::save()`  
**Template**: `branding/configure.html.twig` (on validation errors) or redirect  
**Authorization**: ROLE_USER (Pro/Enterprise plans only)

**Purpose**: Save branding configuration (colors, logo) for Pro/Enterprise accounts

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Pro or Enterprise
- Content-Type: multipart/form-data (for logo upload)
- Parameters:
  - `branding[primaryColor]`: Hex color code (optional, format: #RRGGBB)
  - `branding[secondaryColor]`: Hex color code (optional, format: #RRGGBB)
  - `branding[logo]`: UploadedFile (optional, PNG/JPG/JPEG/SVG, max 5MB)
  - `branding[logoPosition]`: string (optional, enum: top-left, top-center, etc.)
  - `branding[logoSize]`: string (optional, enum: small, medium, large)
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to `/branding/configure` with success flash message
- Validation Error: 200 OK with form errors displayed
- Access Denied: 403 Forbidden (Free plan users)
- Content-Type: text/html (on error) or redirect (on success)

**Success Scenarios**:
- Valid configuration saved: Redirects with success message, branding applied to public card pages
- Logo uploaded: File stored, old logo deleted (if exists), filename saved to database

**Error Scenarios**:
- 403 Forbidden: Free plan user attempts to save
- Validation Error: Invalid color format, invalid file type/size, invalid position/size values
- 500 Internal Server Error: File upload failure, database error

---

### 3. Configure Custom Template (Enterprise Only)

**Route**: `POST /branding/template`  
**Controller**: `BrandingController::saveTemplate()`  
**Template**: `branding/configure.html.twig` (on validation errors) or redirect  
**Authorization**: ROLE_USER (Enterprise plan only)

**Purpose**: Save custom template configuration for Enterprise accounts

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `template[customTemplate]`: string (required, Twig template content)
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to `/branding/configure` with success flash message
- Validation Error: 200 OK with form errors displayed
- Access Denied: 403 Forbidden (Pro/Free plan users)
- Content-Type: text/html (on error) or redirect (on success)

**Success Scenarios**:
- Valid template saved: Redirects with success message, template applied to public card pages
- Template syntax validated: Twig syntax checked before saving

**Error Scenarios**:
- 403 Forbidden: Pro/Free plan user attempts to configure template
- Validation Error: Invalid Twig syntax, dangerous functions detected
- 500 Internal Server Error: Database error

---

### 4. Remove Logo

**Route**: `POST /branding/logo/remove`  
**Controller**: `BrandingController::removeLogo()`  
**Template**: Redirect  
**Authorization**: ROLE_USER (Pro/Enterprise plans only)

**Purpose**: Remove uploaded logo from branding configuration

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Pro or Enterprise
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to `/branding/configure` with success flash message
- Access Denied: 403 Forbidden (Free plan users)
- Content-Type: Redirect

**Success Scenarios**:
- Logo removed: Logo file deleted from filesystem, filename cleared in database

**Error Scenarios**:
- 403 Forbidden: Free plan user attempts to remove logo
- 404 Not Found: No logo exists to remove

---

### 5. Reset Branding

**Route**: `POST /branding/reset`  
**Controller**: `BrandingController::reset()`  
**Template**: Redirect  
**Authorization**: ROLE_USER (Pro/Enterprise plans only)

**Purpose**: Reset all branding configuration to defaults (remove colors, logo, templates)

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Pro or Enterprise
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `_token`: CSRF token
  - `confirm`: string (required, must be "yes" to confirm)

**Response**:
- Success: 302 Redirect to `/branding/configure` with success flash message
- Access Denied: 403 Forbidden (Free plan users)
- Content-Type: Redirect

**Success Scenarios**:
- Branding reset: All branding data cleared, logo file deleted, public card pages revert to defaults

**Error Scenarios**:
- 403 Forbidden: Free plan user attempts to reset
- Validation Error: Confirmation not provided

---

## Public Route Integration

### Public Card Page (Modified)

**Route**: `GET /c/{slug}` (existing route from Feature 005)  
**Controller**: `PublicCardController::show(string $slug)` (modified)  
**Template**: `public/card.html.twig` (modified) or custom template (Enterprise)  
**Authorization**: None (public access)

**Purpose**: Display public card page with account branding applied

**Request**:
- Method: GET
- Authentication: Not required (public access)
- Parameters:
  - `slug` (path): Card slug

**Response**:
- Success: 200 OK
- Not Found: 404 Not Found
- Content-Type: text/html
- Body: Twig-rendered HTML page with branding applied

**Response Data** (available in template):
```php
[
    'card' => Card,                    // Card entity
    'account' => Account,              // Card owner's account
    'branding' => AccountBranding|null, // Account branding (if configured and plan allows)
    'template' => string,              // Template name to use (default or custom)
]
```

**Branding Application**:
- Colors: Applied via CSS custom properties (CSS variables)
- Logo: Displayed if configured and plan allows
- Custom Template: Used for Enterprise accounts with custom templates configured

**Success Scenarios**:
- Card with Pro/Enterprise account branding: Page displays with configured colors, logo, and template
- Card with Free account: Page displays with default styling
- Enterprise account with custom template: Page uses custom template instead of default

---

## Route Requirements

### Authentication Requirements

- All branding configuration routes require `ROLE_USER` authentication
- Public card pages remain publicly accessible (no authentication required)

### Plan-Based Access Control

- `/branding/configure`: Pro and Enterprise plans only
- `/branding/template`: Enterprise plan only
- `/branding/logo/remove`: Pro and Enterprise plans only
- `/branding/reset`: Pro and Enterprise plans only

### CSRF Protection

- All POST routes require CSRF token validation
- Forms must include `{{ csrf_token('branding') }}` or equivalent

---

## Flash Messages

### Success Messages

- `branding.save.success`: "Branding configuration saved successfully"
- `branding.template.save.success`: "Custom template saved successfully"
- `branding.logo.remove.success`: "Logo removed successfully"
- `branding.reset.success`: "Branding configuration reset successfully"

### Error Messages

- `branding.access_denied`: "Branding customization is only available for Pro and Enterprise plans"
- `branding.template.access_denied`: "Template customization is only available for Enterprise plans"
- `branding.validation.error`: "Please correct the errors below"
- `branding.upload.error`: "Logo upload failed. Please try again."

---

## Notes

- Branding routes integrate with existing authentication system
- Plan-based access enforced at service layer (not just route level)
- Public card pages apply branding automatically (no separate route needed)
- Logo uploads use multipart/form-data encoding
- Template configuration uses textarea input for Twig code
- All routes follow Symfony conventions and return appropriate HTTP status codes

