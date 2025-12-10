# Feature Specification: Digital Card Management

**Feature Branch**: `005-digital-card`  
**Created**: December 8, 2025  
**Status**: Draft  
**Input**: User description: "5️⃣ Feature 05 — Digital Card Management"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create Digital Card (Priority: P1)

A logged-in user wants to create a digital card that can be shared publicly via a unique URL. They provide information for their card (such as name, contact details, or other relevant information), and the system creates a card with a unique slug that can be accessed at `/c/<slug>`. The system validates that the user has not exceeded their plan's quota limit before creating the card.

**Why this priority**: This is the core functionality of the feature. Without card creation, users cannot generate digital cards to share. This is the foundation for all other card-related features.

**Independent Test**: Can be fully tested by logging in, navigating to card creation interface, entering card information, submitting the form, and verifying that a card is created with a unique slug and is accessible at the public URL `/c/<slug>`.

**Acceptance Scenarios**:

1. **Given** a logged-in user with available quota is on the card creation page, **When** they enter valid card information and submit the form, **Then** a card is created with a unique slug and they are shown the public URL
2. **Given** a logged-in user has reached their quota limit (e.g., Free user with 1 card), **When** they attempt to create a new card, **Then** they see a clear error message explaining their quota limit and upgrade options
3. **Given** a logged-in user is creating a card, **When** they submit the form with invalid or missing required information, **Then** they see validation errors indicating what needs to be corrected
4. **Given** a user creates a card, **When** the system generates the slug, **Then** the slug is unique and URL-safe (no special characters, spaces, or conflicts)

---

### User Story 2 - View Public Card Page (Priority: P1)

Anyone (including non-authenticated users) wants to view a digital card by accessing its public URL. They navigate to `/c/<slug>` and see a styled, professional card page displaying the card owner's information in an attractive format.

**Why this priority**: The public card page is the primary value proposition - it's how card owners share their information with others. Without this, created cards have no purpose.

**Independent Test**: Can be fully tested by navigating to `/c/<slug>` for an existing card and verifying that the card information displays correctly in a styled template, even without being logged in.

**Acceptance Scenarios**:

1. **Given** a valid card slug exists in the system, **When** anyone navigates to `/c/<slug>`, **Then** they see the card information displayed in a styled template
2. **Given** an invalid or non-existent card slug, **When** someone navigates to `/c/<invalid-slug>`, **Then** they see a user-friendly 404 error page
3. **Given** a card exists and is viewed, **When** the page loads, **Then** it displays correctly on both desktop and mobile devices
4. **Given** a card page is accessed, **When** viewing the page, **Then** it loads quickly (under 2 seconds) and displays all card information clearly

---

### User Story 3 - Generate QR Code for Card (Priority: P2)

A card owner wants to generate a QR code for their digital card so they can easily share it in physical or digital formats. The system generates a QR code that, when scanned, directs to the card's public URL `/c/<slug>`.

**Why this priority**: QR codes enhance the sharing capabilities of digital cards, making them more versatile for real-world use cases. Important for user experience but not critical for initial MVP.

**Independent Test**: Can be fully tested by creating a card, generating its QR code, scanning the QR code with a mobile device, and verifying it correctly navigates to the card's public URL.

**Acceptance Scenarios**:

1. **Given** a logged-in user owns a card, **When** they request to generate or view the QR code, **Then** a QR code image is generated that links to `/c/<slug>`
2. **Given** a QR code is generated, **When** scanned with a QR code reader, **Then** it correctly navigates to the card's public page
3. **Given** a user generates a QR code, **When** viewing it, **Then** the QR code is displayed in a format suitable for downloading or printing
4. **Given** a card's slug changes, **When** a new QR code is generated, **Then** it reflects the updated URL

---

### User Story 4 - Manage Own Cards (Priority: P2)

A logged-in user wants to view, edit, and delete their own digital cards. They can see a list of all their cards, edit card information, and remove cards they no longer need. When a card is deleted, its public URL becomes inaccessible.

**Why this priority**: Card management is essential for user autonomy and allows users to maintain their cards over time. Important for user experience but the system can function initially with just creation and viewing.

**Independent Test**: Can be fully tested by logging in, viewing the list of owned cards, editing a card's information, saving changes, and verifying updates appear on the public page. Then deleting a card and verifying the public URL no longer works.

**Acceptance Scenarios**:

1. **Given** a logged-in user has created cards, **When** they navigate to their card management page, **Then** they see a list of all their cards with basic information and links
2. **Given** a logged-in user is viewing their card list, **When** they click to edit a card, **Then** they can modify card information and save changes that update the public page
3. **Given** a logged-in user edits a card, **When** they save changes, **Then** the public card page immediately reflects the updated information
4. **Given** a logged-in user wants to delete a card, **When** they confirm deletion, **Then** the card is removed and its public URL returns a 404 error
5. **Given** a user deletes a card, **When** the deletion occurs, **Then** their quota usage decreases, allowing them to create new cards if they were at their limit

---

### Edge Cases

- What happens when two users try to create cards with the same slug simultaneously? → System should ensure slug uniqueness, potentially by appending a random suffix or number
- How does the system handle very long card information that might break the page layout? → Card page should gracefully handle long content with appropriate text wrapping and scrolling
- What happens when a user's plan is downgraded and they have more cards than the new plan allows? → System should prevent plan downgrade if it would exceed quota, or require deletion of excess cards first
- How does the system handle special characters or emojis in card information? → Should sanitize or properly escape content to prevent XSS while preserving user intent
- What happens if a QR code is generated but the card is later deleted? → QR code should still resolve but show a 404 page, or QR code generation should be disabled for deleted cards
- How does the system handle slug collisions from deleted cards? → Deleted card slugs should remain reserved or be recycled after a grace period to prevent confusion
- What happens when a user reaches exactly their quota limit and tries to create another card? → Should show clear quota exceeded message with upgrade options
- How does the system handle card creation if the user's account is suspended or inactive? → Should prevent card creation and display appropriate error message

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow logged-in users to create digital cards with unique, URL-safe slugs
- **FR-002**: System MUST enforce quota limits based on user's subscription plan: Free plan allows 1 card, Pro plan allows 10 cards, Enterprise plan allows unlimited cards
- **FR-003**: System MUST validate quota before allowing card creation and display clear error messages when quota is exceeded
- **FR-004**: System MUST provide a public page at `/c/<slug>` that displays card information in a styled template
- **FR-005**: System MUST make public card pages accessible to anyone (including non-authenticated users) without requiring login
- **FR-006**: System MUST return a user-friendly 404 error page when accessing `/c/<slug>` for non-existent cards
- **FR-007**: System MUST generate QR codes for cards that link to the card's public URL `/c/<slug>`
- **FR-008**: System MUST ensure QR codes are scannable and correctly navigate to the card's public page
- **FR-009**: System MUST allow card owners to view a list of all their cards
- **FR-010**: System MUST allow card owners to edit their own card information
- **FR-011**: System MUST allow card owners to delete their own cards
- **FR-012**: System MUST update public card pages immediately when card information is edited
- **FR-013**: System MUST make deleted cards' public URLs inaccessible (return 404)
- **FR-014**: System MUST decrease quota usage when a card is deleted, allowing users to create new cards if they were at their limit
- **FR-015**: System MUST ensure card slugs are unique across all cards in the system
- **FR-016**: System MUST generate slugs that are URL-safe (no special characters, spaces, or problematic characters)
- **FR-017**: System MUST display card information in a professional, styled template that works on desktop and mobile devices
- **FR-018**: System MUST prevent users from editing or deleting cards they do not own
- **FR-019**: System MUST validate card information before saving (required fields, data format, length limits)
- **FR-020**: System MUST handle quota validation gracefully when users' plans change (prevent downgrades that would exceed new quota)

### Key Entities *(include if feature involves data)*

- **Card**: Represents a digital card created by a user. Contains unique identifier, unique slug (URL-safe identifier), associated user (owner), card information/content (name, contact details, or other customizable fields), creation timestamp, last update timestamp, and status (active/deleted). The slug is used to generate the public URL `/c/<slug>` and must be unique across all cards.

- **QR Code**: Represents a QR code generated for a card. Contains associated card identifier, QR code image data or file reference, generation timestamp, and the target URL (which is `/c/<slug>`). QR codes are generated on-demand and can be regenerated if the card's slug changes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Logged-in users can create a digital card from start to finish (entering information and submitting) in under 2 minutes
- **SC-002**: Public card pages at `/c/<slug>` load and display card information in under 2 seconds for 95% of requests
- **SC-003**: Card pages display correctly and are readable on mobile devices (screen widths from 320px to 1920px)
- **SC-004**: QR codes generated for cards are successfully scannable by standard QR code readers 100% of the time
- **SC-005**: When users attempt to create cards exceeding their quota, they receive clear error messages with upgrade options within 1 second
- **SC-006**: Card owners can edit their card information and see updates reflected on the public page within 5 seconds
- **SC-007**: Card owners can view a list of all their cards with basic information displayed within 1 second
- **SC-008**: System enforces quota limits correctly: Free users cannot create more than 1 card, Pro users cannot create more than 10 cards, Enterprise users have no limit
- **SC-009**: Deleted cards' public URLs return 404 errors immediately after deletion
- **SC-010**: Card slugs are unique across all cards with zero conflicts, even under concurrent creation scenarios
- **SC-011**: 95% of users successfully create their first card without encountering validation errors (after being informed of requirements)
- **SC-012**: Card pages are accessible to non-authenticated users without requiring login or registration

## Assumptions

- Users understand that digital cards are publicly accessible via the `/c/<slug>` URL
- Card information may include personal contact details (name, email, phone, social media links, etc.)
- QR codes will be scanned primarily with mobile devices
- Card slugs should be human-readable when possible (not just random strings)
- Users may want to share cards via QR codes in both digital and physical formats (business cards, presentations, etc.)
- Card templates should be professional and suitable for business use
- The system will handle a reasonable volume of card views without performance issues (standard web application expectations)
- Card information may be updated frequently by users
- Users may create and delete multiple cards over time as their needs change
- Quota limits are enforced at creation time, not retroactively when plans change
- Card slugs remain stable (do not change) unless explicitly regenerated by the user

## Dependencies

- Existing Account entity and subscription plan system (FREE, PRO, ENTERPRISE plans)
- Existing QuotaService for quota validation and enforcement
- User authentication system (users must be logged in to create/manage cards)
- QR code generation library or service (for generating QR code images)
- Template rendering system for styled card pages
- URL routing system to handle `/c/<slug>` public routes

## Scope

### In Scope

- Card entity with unique slug generation
- Card creation interface for logged-in users
- Public card page at `/c/<slug>` with styled template
- QR code generation for cards
- Card management (list, edit, delete) for card owners
- Quota validation and enforcement based on subscription plans
- Card slug uniqueness validation
- Mobile-responsive card page templates
- Error handling for non-existent cards (404 pages)
- Quota exceeded error messages with upgrade suggestions

### Out of Scope

- Card sharing via social media buttons or email
- Card analytics or view tracking
- Custom card templates or themes (beyond initial styled template)
- Card collaboration or multi-user editing
- Card expiration dates or automatic deletion
- Card password protection or private cards
- Bulk card import or export functionality
- Card versioning or history tracking
- Advanced QR code customization (colors, logos, etc.)
- Card search or discovery features
- Public card directory or listing

