# Route Contracts: Digital Card Management

**Feature**: 005-digital-card  
**Date**: December 8, 2025  
**Type**: Symfony Routes (Twig-based web application)

## Overview

This document defines the route contracts for the digital card management system. Routes include both authenticated user-facing routes (card management) and public routes (card viewing). Since this is a Twig-based web application, routes return HTML responses rendered via Twig templates.

## Public Routes

### 1. View Public Card

**Route**: `GET /c/{slug}`  
**Controller**: `PublicCardController::show(string $slug)`  
**Template**: `public/card.html.twig`  
**Authorization**: None (public access)

**Purpose**: Display a digital card publicly via its unique slug URL

**Request**:
- Method: GET
- Authentication: Not required (public access)
- Parameters:
  - `slug` (path): Card slug (3-100 characters, a-z0-9-)

**Response**:
- Success: 200 OK
- Not Found: 404 Not Found
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'card' => Card,              // Card entity (if found)
    'publicUrl' => string,        // Full public URL (/c/<slug>)
    'qrCodeUrl' => string,        // URL to QR code image (if requested)
]
```

**Success Scenarios**:
- Valid slug with active card: Shows card information in styled template
- Valid slug with deleted card: Returns 404 (card not found)

**Error Scenarios**:
- 404 Not Found: Card with slug doesn't exist or is deleted
- 404 Not Found: Invalid slug format (handled by route requirements)

**Route Requirements**:
```php
#[Route('/c/{slug}', name: 'app_public_card', requirements: ['slug' => '[a-z0-9-]+'])]
```

---

## User-Facing Routes (Authenticated)

### 2. Card List Page

**Route**: `GET /cards`  
**Controller**: `CardController::index()`  
**Template**: `card/index.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Display list of user's own cards with management options

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
    'cards' => Card[],           // Array of user's cards
    'quotaLimit' => int|null,   // User's quota limit (null if unlimited)
    'currentUsage' => int,       // Current number of active cards
    'canCreateMore' => bool,     // True if user can create more cards
]
```

**Success Scenarios**:
- User has cards: Shows list with edit/delete options and public URLs
- User has no cards: Shows empty state with "Create Card" button
- User at quota limit: Shows quota limit message

**Error Scenarios**:
- 401 Unauthorized: User not authenticated → redirect to login

---

### 3. Create Card Page

**Route**: `GET /cards/create`  
**Controller**: `CardController::create(Request $request)`  
**Template**: `card/create.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Display form for creating a new digital card

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Parameters: None

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page with card creation form

**Response Data** (available in template):
```php
[
    'form' => FormInterface,    // Card creation form
    'quotaLimit' => int|null,    // User's quota limit
    'currentUsage' => int,       // Current number of active cards
]
```

**Success Scenarios**:
- User has available quota: Shows card creation form
- User at quota limit: Shows quota exceeded message with upgrade options

**Error Scenarios**:
- 401 Unauthorized: User not authenticated → redirect to login
- 403 Forbidden: User at quota limit → show error message

---

### 4. Create Card (Submit)

**Route**: `POST /cards/create`  
**Controller**: `CardController::create(Request $request)`  
**Template**: Redirects to card list or shows form with errors  
**Authorization**: `ROLE_USER` (authenticated users only)

**Purpose**: Process card creation form submission

**Request**:
- Method: POST
- Authentication: Required (session-based)
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - Form fields from `CardFormType` (name, email, phone, etc.)
  - CSRF token

**Response**:
- Success: 302 Redirect to `/cards` with success flash message
- Error: 200 OK with form errors displayed

**Success Scenarios**:
- Valid form data: Card created, redirect to card list with success message
- Quota validation passes: Card created successfully

**Error Scenarios**:
- 400 Bad Request: Invalid form data → form errors displayed
- 400 Bad Request: Quota exceeded → error message with upgrade options
- 401 Unauthorized: User not authenticated
- 500 Internal Server Error: Slug conflict (should not happen, handled by service)

**Flash Messages**:
- Success: "Card created successfully. Public URL: /c/{slug}"
- Error: "Cannot create card: {quota error message}"

---

### 5. Edit Card Page

**Route**: `GET /cards/{id}/edit`  
**Controller**: `CardController::edit(int $id, Request $request)`  
**Template**: `card/edit.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only, card owner)

**Purpose**: Display form for editing an existing card

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Parameters:
  - `id` (path): Card ID

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page with card editing form

**Response Data** (available in template):
```php
[
    'card' => Card,              // Card entity
    'form' => FormInterface,    // Card editing form
]
```

**Success Scenarios**:
- User owns card: Shows edit form with current card data
- Card is active: Form is editable

**Error Scenarios**:
- 404 Not Found: Card doesn't exist
- 403 Forbidden: User doesn't own card → access denied
- 401 Unauthorized: User not authenticated

---

### 6. Edit Card (Submit)

**Route**: `POST /cards/{id}/edit`  
**Controller**: `CardController::edit(int $id, Request $request)`  
**Template**: Redirects to card list or shows form with errors  
**Authorization**: `ROLE_USER` (authenticated users only, card owner)

**Purpose**: Process card editing form submission

**Request**:
- Method: POST
- Authentication: Required (session-based)
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Card ID
  - Form fields from `CardFormType`
  - CSRF token

**Response**:
- Success: 302 Redirect to `/cards` with success flash message
- Error: 200 OK with form errors displayed

**Success Scenarios**:
- Valid form data: Card updated, redirect to card list
- Slug changed: New slug generated, public URL updated

**Error Scenarios**:
- 400 Bad Request: Invalid form data → form errors displayed
- 404 Not Found: Card doesn't exist
- 403 Forbidden: User doesn't own card
- 401 Unauthorized: User not authenticated

**Flash Messages**:
- Success: "Card updated successfully"
- Error: "Cannot update card: {error message}"

---

### 7. Delete Card

**Route**: `POST /cards/{id}/delete`  
**Controller**: `CardController::delete(int $id, Request $request)`  
**Template**: Redirects to card list  
**Authorization**: `ROLE_USER` (authenticated users only, card owner)

**Purpose**: Delete (soft delete) a card

**Request**:
- Method: POST
- Authentication: Required (session-based)
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Card ID
  - CSRF token

**Response**:
- Success: 302 Redirect to `/cards` with success flash message
- Error: 302 Redirect to `/cards` with error flash message

**Success Scenarios**:
- User owns card: Card soft-deleted (status = 'deleted'), quota usage decreases

**Error Scenarios**:
- 404 Not Found: Card doesn't exist
- 403 Forbidden: User doesn't own card
- 401 Unauthorized: User not authenticated

**Flash Messages**:
- Success: "Card deleted successfully"
- Error: "Cannot delete card: {error message}"

---

### 8. View Card QR Code

**Route**: `GET /cards/{id}/qr-code`  
**Controller**: `CardController::qrCode(int $id)`  
**Template**: `card/qr_code.html.twig`  
**Authorization**: `ROLE_USER` (authenticated users only, card owner)

**Purpose**: Display QR code for a card

**Request**:
- Method: GET
- Authentication: Required (session-based)
- Parameters:
  - `id` (path): Card ID

**Response**:
- Status: 200 OK
- Content-Type: text/html
- Body: Twig-rendered HTML page with QR code image

**Response Data** (available in template):
```php
[
    'card' => Card,              // Card entity
    'qrCodeData' => string,      // Base64-encoded QR code image data
    'publicUrl' => string,       // Full public URL for QR code
]
```

**Success Scenarios**:
- User owns card: QR code displayed, can be downloaded/printed

**Error Scenarios**:
- 404 Not Found: Card doesn't exist
- 403 Forbidden: User doesn't own card
- 401 Unauthorized: User not authenticated

---

## Route Naming Conventions

All routes follow Symfony naming conventions:
- Public routes: `/c/{slug}` (short, memorable)
- User-facing: `/cards/*`
- Route names: `app_public_card`, `app_card_*`

## Security Considerations

- Public card routes accessible without authentication (as per spec FR-005)
- Card management routes require `ROLE_USER` authentication
- Card edit/delete operations validate user ownership in service layer
- CSRF protection on all POST routes
- Input validation on all form submissions
- Slug format validation via route requirements

## Internationalization

All route responses support internationalization:
- Routes detect locale from session/request
- Templates use `{{ 'key'|trans }}` for all text
- Flash messages are translated
- Form labels and errors are translated
- Public card pages support locale detection

## Performance Considerations

- Public card routes should be cacheable (future: HTTP cache headers)
- Card list queries use indexes for performance
- QR code generation is on-demand (can be cached if needed)
- Slug uniqueness check uses database index

