# Route Contracts: Multi-user (Enterprise)

**Feature**: 007-multi-user  
**Date**: December 10, 2025  
**Type**: Symfony Routes (Twig-based web application)

## Overview

This document defines the route contracts for the multi-user team collaboration system. Routes include authenticated user-facing routes for team management (invitations, role management, card assignments) and integration with existing card management routes. Since this is a Twig-based web application, routes return HTML responses rendered via Twig templates.

## Authenticated Routes

### 1. Team Management Page

**Route**: `GET /team`  
**Controller**: `TeamController::index()`  
**Template**: `team/index.html.twig`  
**Authorization**: ROLE_USER (Enterprise plan only)

**Purpose**: Display team member list and management interface for Enterprise account owners and ADMINs

**Request**:
- Method: GET
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only

**Response**:
- Success: 200 OK
- Access Denied: 403 Forbidden (Free/Pro plan users)
- Content-Type: text/html
- Body: Twig-rendered HTML page

**Response Data** (available in template):
```php
[
    'account' => Account,                    // Current user's account
    'teamMembers' => TeamMember[],          // List of team members
    'invitationForm' => FormView,           // Invitation form
    'canManageTeam' => bool,                // Whether user can manage team (owner or ADMIN)
    'isAccountOwner' => bool,               // Whether current user is account owner
]
```

**Success Scenarios**:
- Enterprise account owner: Shows team member list with invitation form
- Enterprise team ADMIN: Shows team member list (cannot remove owner or change plan)
- Enterprise team MEMBER: Shows access denied (redirected)

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user attempts to access (redirect to upgrade page)
- 302 Redirect: User not authenticated (redirect to login)

---

### 2. Invite Team Member

**Route**: `POST /team/invite`  
**Controller**: `TeamController::invite()`  
**Template**: `team/index.html.twig` (on validation errors) or redirect  
**Authorization**: ROLE_USER (Enterprise plan only, owner or ADMIN)

**Purpose**: Send team member invitation email

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `invitation[email]`: string (required, valid email format)
  - `invitation[role]`: string (required, enum: admin, member)
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to `/team` with success flash message
- Validation Error: 200 OK with form errors displayed
- Access Denied: 403 Forbidden (Free/Pro plan users, MEMBERs)
- Content-Type: text/html (on error) or redirect (on success)

**Success Scenarios**:
- Valid invitation sent: Redirects with success message, invitation email sent
- Duplicate invitation prevented: Shows error message if email already invited

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user or MEMBER attempts to invite
- Validation Error: Invalid email format, invalid role, duplicate invitation
- 500 Internal Server Error: Email sending failure, database error

---

### 3. Accept Team Invitation

**Route**: `GET /team/accept/{token}`  
**Controller**: `TeamController::acceptInvitation(string $token)`  
**Template**: `team/accept_invitation.html.twig`  
**Authorization**: ROLE_USER (for logged-in users) or public (for invitation acceptance)

**Purpose**: Display invitation acceptance page and process acceptance

**Request**:
- Method: GET
- Authentication: Optional (public access allowed, but user must be logged in to accept)
- Parameters:
  - `token` (path): Invitation token (64-character hex string)

**Response**:
- Success: 200 OK (display acceptance page) or 302 Redirect (after acceptance)
- Not Found: 404 Not Found (invalid or expired token)
- Content-Type: text/html

**Response Data** (available in template):
```php
[
    'teamMember' => TeamMember,             // Team member record
    'account' => Account,                   // Enterprise account
    'isExpired' => bool,                    // Whether invitation is expired
    'isLoggedIn' => bool,                   // Whether user is logged in
    'userEmail' => string|null,            // Current user's email (if logged in)
]
```

**Success Scenarios**:
- Valid invitation: Shows acceptance page with account details
- User logged in with matching email: Can accept immediately
- User not logged in: Prompted to log in or register

**Error Scenarios**:
- 404 Not Found: Invalid token, expired invitation, already accepted/declined
- 400 Bad Request: Email mismatch (user logged in with different email)

---

### 4. Process Invitation Acceptance

**Route**: `POST /team/accept/{token}`  
**Controller**: `TeamController::processAcceptance(string $token)`  
**Template**: Redirect  
**Authorization**: ROLE_USER (required for acceptance)

**Purpose**: Process team invitation acceptance

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `token` (path): Invitation token
  - `_token`: CSRF token
  - `action`: string (required, enum: accept, decline)

**Response**:
- Success: 302 Redirect to `/team` or dashboard with success message
- Not Found: 404 Not Found (invalid or expired token)
- Access Denied: 403 Forbidden (email mismatch, not logged in)
- Content-Type: Redirect

**Success Scenarios**:
- Invitation accepted: Team member status updated to 'accepted', user linked, redirects to team page
- Invitation declined: Team member status updated to 'declined', redirects to dashboard

**Error Scenarios**:
- 404 Not Found: Invalid token, expired invitation, already processed
- 403 Forbidden: User not logged in, email mismatch
- 400 Bad Request: Invalid action value

---

### 5. Change Team Member Role

**Route**: `POST /team/{id}/role`  
**Controller**: `TeamController::changeRole(int $id)`  
**Template**: Redirect  
**Authorization**: ROLE_USER (Enterprise plan only, owner only)

**Purpose**: Change team member role (ADMIN ↔ MEMBER)

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Team member ID
  - `role[role]`: string (required, enum: admin, member)
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to `/team` with success flash message
- Access Denied: 403 Forbidden (Free/Pro plan users, ADMINs, MEMBERs)
- Not Found: 404 Not Found (team member not found)
- Content-Type: Redirect

**Success Scenarios**:
- Role changed: Team member role updated, permissions updated immediately

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user, ADMIN, or MEMBER attempts to change role
- 404 Not Found: Team member not found or doesn't belong to user's account
- 400 Bad Request: Attempting to change account owner's role (not allowed)

---

### 6. Remove Team Member

**Route**: `POST /team/{id}/remove`  
**Controller**: `TeamController::remove(int $id)`  
**Template**: Redirect  
**Authorization**: ROLE_USER (Enterprise plan only, owner only)

**Purpose**: Remove team member from account

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Team member ID
  - `_token`: CSRF token
  - `confirm`: string (required, must be "yes" to confirm)

**Response**:
- Success: 302 Redirect to `/team` with success flash message
- Access Denied: 403 Forbidden (Free/Pro plan users, ADMINs, MEMBERs)
- Not Found: 404 Not Found (team member not found)
- Content-Type: Redirect

**Success Scenarios**:
- Team member removed: Team member deleted, card assignments preserved (become unassigned)

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user, ADMIN, or MEMBER attempts to remove
- 404 Not Found: Team member not found or doesn't belong to user's account
- 400 Bad Request: Attempting to remove account owner (not allowed), confirmation not provided

---

### 7. Assign Card to Team Member

**Route**: `POST /cards/{id}/assign`  
**Controller**: `CardController::assign(int $id)` (modified)  
**Template**: Redirect  
**Authorization**: ROLE_USER (Enterprise plan only, owner or ADMIN)

**Purpose**: Assign card to one or more team members

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Card ID
  - `assignment[teamMembers]`: array (required, array of team member IDs)
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to card management page with success flash message
- Access Denied: 403 Forbidden (Free/Pro plan users, MEMBERs, card not owned by account)
- Not Found: 404 Not Found (card not found)
- Content-Type: Redirect

**Success Scenarios**:
- Card assigned: CardAssignment records created, team members can access card

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user, MEMBER, or card not owned by account
- 404 Not Found: Card not found or team members not found
- 400 Bad Request: Invalid team member IDs, duplicate assignments

---

### 8. Unassign Card from Team Member

**Route**: `POST /cards/{id}/unassign/{teamMemberId}`  
**Controller**: `CardController::unassign(int $id, int $teamMemberId)` (modified)  
**Template**: Redirect  
**Authorization**: ROLE_USER (Enterprise plan only, owner or ADMIN)

**Purpose**: Remove card assignment from team member

**Request**:
- Method: POST
- Authentication: Required (ROLE_USER)
- Plan Requirement: Enterprise only
- Content-Type: application/x-www-form-urlencoded
- Parameters:
  - `id` (path): Card ID
  - `teamMemberId` (path): Team member ID
  - `_token`: CSRF token

**Response**:
- Success: 302 Redirect to card management page with success flash message
- Access Denied: 403 Forbidden (Free/Pro plan users, MEMBERs)
- Not Found: 404 Not Found (card or assignment not found)
- Content-Type: Redirect

**Success Scenarios**:
- Assignment removed: CardAssignment deleted, team member loses access to card

**Error Scenarios**:
- 403 Forbidden: Free/Pro plan user or MEMBER attempts to unassign
- 404 Not Found: Card or assignment not found

---

## Modified Routes

### Card Management Routes (Modified)

**Route**: `GET /cards` (existing route from Feature 005)  
**Controller**: `CardController::index()` (modified)  
**Template**: `card/index.html.twig` (modified)  
**Authorization**: ROLE_USER

**Changes**:
- Display card assignments for Enterprise accounts
- Show which cards are assigned to which team members
- Filter cards by assignment for MEMBERs (show only assigned cards)

**Response Data** (extended):
```php
[
    'cards' => Card[],                      // User's cards (or assigned cards for MEMBERs)
    'account' => Account,                   // User's account
    'isEnterprise' => bool,                 // Whether account is Enterprise
    'teamMember' => TeamMember|null,       // Current user's team membership (if any)
    'cardAssignments' => CardAssignment[],  // Card assignments for Enterprise accounts
]
```

---

### Card Edit Route (Modified)

**Route**: `GET /cards/{id}/edit` (existing route from Feature 005)  
**Controller**: `CardController::edit(int $id)` (modified)  
**Template**: `card/edit.html.twig` (modified)  
**Authorization**: ROLE_USER

**Changes**:
- Check team member access for Enterprise accounts
- MEMBERs can only edit assigned cards
- ADMINs and owners can edit all cards in account

**Access Control**:
- Card owner: Full access
- Team ADMIN: Full access (all cards in account)
- Team MEMBER: Only assigned cards
- Others: Access denied

---

## Route Requirements

### Authentication Requirements

- All team management routes require `ROLE_USER` authentication
- Invitation acceptance route allows public access (but requires login to accept)
- Card assignment routes require `ROLE_USER` authentication

### Plan-Based Access Control

- `/team/*`: Enterprise plan only
- `/cards/*/assign`: Enterprise plan only
- `/cards/*/unassign`: Enterprise plan only
- Free/Pro users see upgrade prompts when accessing team features

### Role-Based Access Control

- Team management (invite, remove, change role): Account owner only
- Card assignment: Account owner or team ADMIN
- Card access: Card owner, team ADMIN (all cards), or team MEMBER (assigned cards only)

### CSRF Protection

- All POST routes require CSRF token validation
- Forms must include `{{ csrf_token('team') }}` or equivalent

---

## Flash Messages

### Success Messages

- `team.invite.success`: "Team member invitation sent successfully"
- `team.accept.success`: "You have successfully joined the team"
- `team.decline.success`: "Invitation declined"
- `team.role.change.success`: "Team member role updated successfully"
- `team.remove.success`: "Team member removed successfully"
- `card.assign.success`: "Card assigned to team member(s) successfully"
- `card.unassign.success`: "Card assignment removed successfully"

### Error Messages

- `team.access_denied`: "Team features are only available for Enterprise plans"
- `team.invite.duplicate`: "This user has already been invited to this team"
- `team.invite.invalid_email`: "Invalid email address"
- `team.invitation.expired`: "This invitation has expired"
- `team.invitation.invalid`: "Invalid or already processed invitation"
- `team.role.change.denied`: "Only account owners can change team member roles"
- `team.remove.denied`: "Only account owners can remove team members"
- `card.assign.denied`: "You do not have permission to assign this card"
- `card.access.denied`: "You do not have access to this card"

---

## Notes

- Team routes integrate with existing authentication system
- Plan-based access enforced at service layer (not just route level)
- Role-based access enforced via TeamMemberVoter
- Invitation tokens expire after 7 days
- Card assignments preserved when team members removed
- Account downgrade revokes team access but preserves data
- All routes follow Symfony conventions and return appropriate HTTP status codes

