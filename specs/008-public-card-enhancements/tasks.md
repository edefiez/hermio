# Tasks: Public Card Enhancements

**Input**: Design documents from `/specs/008-public-card-enhancements/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are included for critical functionality (vCard generation, download endpoint, form validation).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Symfony web app**: `app/src/`, `app/templates/`, `app/assets/`, `app/translations/`, `app/tests/`
- All paths shown below use Symfony project structure from repository root

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Install dependencies and verify Feature 005 is complete

- [ ] T001 Verify Feature 005 (Digital Card Management) is implemented with Card entity in app/src/Entity/Card.php and PublicCardController in app/src/Controller/PublicCardController.php
- [ ] T002 Install sabre/vobject library via composer require sabre/vobject in app/
- [ ] T003 Verify Bootstrap Icons are available in project (check app/assets/ or CDN in app/templates/base.html.twig)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core services and validators that ALL user stories depend on

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T004 [P] Create VCardService in app/src/Service/VCardService.php with generate() and generateFilename() methods, constructor injecting CacheInterface, implementing vCard 4.0 generation using sabre/vobject
- [ ] T005 [P] Create SocialProfile constraint in app/src/Validator/Constraints/SocialProfile.php with platform parameter and message property
- [ ] T006 Create SocialProfileValidator in app/src/Validator/Constraints/SocialProfileValidator.php with platform-specific URL regex patterns for linkedin, twitter, x, instagram, tiktok, facebook, snapchat
- [ ] T007 Update CardFormType in app/src/Form/CardFormType.php to add 8 new social network fields: instagram, tiktok, facebook, x, bluebirds, snapchat, planity, other (all UrlType, not required, with SocialProfile validators)
- [ ] T008 Create public-card.scss in app/assets/styles/public-card.scss with mobile-first styling, fixed download button, touch-friendly elements (min 48px), WCAG AA color contrast
- [ ] T009 Configure Webpack Encore in app/webpack.config.js to compile public-card.scss as new entry point 'public-card'

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Download Contact to Phone (Priority: P1) 🎯 MVP

**Goal**: Users can download card contacts as vCard files compatible with iOS, Android, Windows, Gmail, Outlook

**Independent Test**: Navigate to public card page, click "Download Contact" button, verify .vcf file downloads and imports correctly on iPhone Contacts, Android Contacts, and desktop contacts apps with all fields populated

### Tests for User Story 1

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T010 [P] [US1] Create VCardServiceTest in app/tests/Service/VCardServiceTest.php testing generate() with basic card, card with social profiles, generateFilename() method, cache behavior
- [ ] T011 [P] [US1] Create PublicCardControllerDownloadTest in app/tests/Controller/PublicCardControllerDownloadTest.php testing successful download, 404 for non-existent card, correct headers (Content-Type, Content-Disposition)

### Implementation for User Story 1

- [ ] T012 [US1] Implement VCardService::generate() in app/src/Service/VCardService.php to create vCard 4.0 with sabre/vobject, map Card content fields to vCard properties (FN, N, EMAIL, TEL, ORG, TITLE, URL, NOTE, X-SOCIALPROFILE), cache for 1 hour
- [ ] T013 [US1] Implement VCardService::addSocialProfiles() private method in app/src/Service/VCardService.php to add X-SOCIALPROFILE for known platforms (linkedin, instagram, tiktok, facebook, x, snapchat) and URL with TYPE=social for others (planity, bluebirds, other)
- [ ] T014 [US1] Implement VCardService::generateFilename() in app/src/Service/VCardService.php to normalize card name, slugify, return format contact-{normalized-name}.vcf
- [ ] T015 [US1] Add download() action to PublicCardController in app/src/Controller/PublicCardController.php with route #[Route('/c/{slug}/download', name: 'public_card_download', methods: ['GET'])]
- [ ] T016 [US1] Implement PublicCardController::download() to find active card by slug, call VCardService::generate(), return Response with Content-Type: text/vcard and Content-Disposition: attachment headers
- [ ] T017 [US1] Add error handling in PublicCardController::download() to catch exceptions from VCardService, log errors, throw 500 with user-friendly message
- [ ] T018 [P] [US1] Add translation keys for download functionality in app/translations/messages.en.yaml: card.download, card.download.error, card.action.call, card.action.email
- [ ] T019 [P] [US1] Add translation keys for download functionality in app/translations/messages.fr.yaml: card.download, card.download.error, card.action.call, card.action.email

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. Users can download vCard files from public cards.

---

## Phase 4: User Story 2 - Expanded Social Network Sharing (Priority: P2)

**Goal**: Card owners can add links to 8+ social networks with recognizable icons on public cards

**Independent Test**: Edit a card, add Instagram, TikTok, Facebook, X, Snapchat URLs, save, view public card, verify all icons display correctly with proper links

### Tests for User Story 2

- [ ] T020 [P] [US2] Create CardFormTypeTest in app/tests/Form/CardFormTypeTest.php testing form submission with new social fields, URL validation for each platform, error messages for invalid URLs
- [ ] T021 [P] [US2] Create SocialProfileValidatorTest in app/tests/Validator/Constraints/SocialProfileValidatorTest.php testing platform-specific regex patterns, valid/invalid URLs for each platform

### Implementation for User Story 2

- [ ] T022 [US2] Update CardFormType::buildForm() in app/src/Form/CardFormType.php to add POST_SUBMIT event listener mapping form data to Card.content['social'] array, removing empty values
- [ ] T023 [US2] Implement SocialProfileValidator::validate() in app/src/Validator/Constraints/SocialProfileValidator.php with PATTERNS array containing regex for each platform, validating URL format matches expected platform domain
- [ ] T024 [US2] Update card edit template in app/templates/card/edit.html.twig to add social networks section with two columns displaying new social network fields (instagram, tiktok, facebook, x, bluebirds, snapchat, planity, other)
- [ ] T025 [P] [US2] Add translation keys for social networks in app/translations/messages.en.yaml: card.social.instagram, card.social.tiktok, card.social.facebook, card.social.x, card.social.bluebirds, card.social.snapchat, card.social.planity, card.social.other with labels, placeholders, help text, validation messages
- [ ] T026 [P] [US2] Add translation keys for social networks in app/translations/messages.fr.yaml: card.social.instagram, card.social.tiktok, card.social.facebook, card.social.x, card.social.bluebirds, card.social.snapchat, card.social.planity, card.social.other with French labels, placeholders, help text, validation messages
- [ ] T027 [US2] Update public card template in app/templates/public/card.html.twig to add social networks section displaying icons for each populated social field with Bootstrap Icons (bi-instagram, bi-tiktok, bi-facebook, bi-twitter-x, bi-snapchat, bi-calendar-check for planity, bi-chat-dots for bluebirds, bi-link-45deg for other)
- [ ] T028 [US2] Add social network link styling in app/assets/styles/public-card.scss with .social-links flex layout, .social-link buttons with min 48px height, hover states, platform-specific colors (optional), aria-labels for screen readers

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently. Users can add social networks and download vCards with social profiles preserved.

---

## Phase 5: User Story 3 - Mobile-Optimized Contact Card Display (Priority: P1)

**Goal**: Public cards display with mobile-first design, large tap-friendly buttons, and prominent download CTA

**Independent Test**: View public card on iPhone (375px), Android (412px), tablet (768px), verify download button fixed at bottom on mobile, action buttons (call, email) are large and tappable, no horizontal scrolling, layout resembles native contact apps

### Implementation for User Story 3

- [ ] T029 [US3] Update public card template in app/templates/public/card.html.twig to add fixed download button for mobile with d-md-none class, inline download button for desktop with d-none d-md-inline-block class
- [ ] T030 [US3] Add contact actions section in app/templates/public/card.html.twig with large call and email buttons (tel: and mailto: links) displayed prominently below header
- [ ] T031 [US3] Restructure public card template in app/templates/public/card.html.twig to use card-header-section with centered name/title/company, contact-actions section with large buttons, contact-info section with icon/label/value layout
- [ ] T032 [US3] Implement mobile-first CSS in app/assets/styles/public-card.scss with .btn-download-mobile fixed positioning (bottom: 1rem, left: 1rem, right: 1rem, z-index: 1000), box-shadow, slideUp animation
- [ ] T033 [US3] Style contact actions in app/assets/styles/public-card.scss with .contact-actions flex layout, .btn-action min-height 56px, large icons (1.5rem), gap for spacing, flex-direction column on mobile
- [ ] T034 [US3] Style contact info section in app/assets/styles/public-card.scss with .contact-info-item flex layout, .contact-icon 1.5rem primary color, .contact-label uppercase small text, .contact-value large readable text with hover states
- [ ] T035 [US3] Add responsive breakpoints in app/assets/styles/public-card.scss using Bootstrap mixins (@include media-breakpoint-up(md)) for tablet/desktop enhancements (increased padding, larger fonts, inline download button)
- [ ] T036 [US3] Implement card header styling in app/assets/styles/public-card.scss with .card-name 2rem mobile/2.5rem desktop, .card-title 1.25rem, .card-company 1rem, centered text, bottom border with primary color
- [ ] T037 [P] [US3] Add translation keys for mobile UI in app/translations/messages.en.yaml: card.action.call.short, card.action.email.short, card.bio.title, card.social.title, card.powered_by
- [ ] T038 [P] [US3] Add translation keys for mobile UI in app/translations/messages.fr.yaml: card.action.call.short, card.action.email.short, card.bio.title, card.social.title, card.powered_by

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently. Public cards are mobile-optimized with large buttons and fixed download CTA.

---

## Phase 6: User Story 4 - Accessibility for All Users (Priority: P2)

**Goal**: Public cards meet WCAG 2.1 Level AA accessibility standards with screen reader support, keyboard navigation, and proper color contrast

**Independent Test**: Use NVDA/VoiceOver to navigate public card, verify all elements are announced correctly, use keyboard only (Tab key) to navigate all interactive elements with visible focus indicators, zoom browser to 200% and verify layout doesn't break

### Implementation for User Story 4

- [ ] T039 [US4] Add ARIA labels to download button in app/templates/public/card.html.twig with aria-label describing action, aria-hidden="true" on decorative icons
- [ ] T040 [US4] Add ARIA labels to contact action buttons in app/templates/public/card.html.twig with descriptive aria-label for call/email actions including phone number/email address
- [ ] T041 [US4] Add ARIA labels to social network links in app/templates/public/card.html.twig with aria-label for each platform (e.g., "Visit Instagram profile"), aria-hidden="true" on icon elements
- [ ] T042 [US4] Ensure proper heading hierarchy in app/templates/public/card.html.twig with h1 for card name, h2 for section titles (About, Connect), semantic HTML (address tag if applicable)
- [ ] T043 [US4] Implement keyboard focus styles in app/assets/styles/public-card.scss with :focus pseudo-class, 2px outline, outline-offset 2px, visible focus indicators on all interactive elements
- [ ] T044 [US4] Ensure color contrast in app/assets/styles/public-card.scss meets WCAG AA standards: normal text 4.5:1 minimum, large text 3:1 minimum, icons 3:1 against background
- [ ] T045 [US4] Add responsive text sizing in app/assets/styles/public-card.scss using relative units (rem, em), base font size 16px (prevents iOS zoom), supports browser zoom to 200% without horizontal scroll
- [ ] T046 [US4] Add prefers-reduced-motion media query in app/assets/styles/public-card.scss to disable animations for users who prefer reduced motion (animation: none on .btn-download-mobile, no transform on hover)
- [ ] T047 [US4] Add dark mode support in app/assets/styles/public-card.scss with @media (prefers-color-scheme: dark) adjusting text colors, background colors, maintaining contrast ratios
- [ ] T048 [P] [US4] Add ARIA translation keys in app/translations/messages.en.yaml: card.social.*.aria for each platform's aria-label (e.g., "Visit LinkedIn profile")
- [ ] T049 [P] [US4] Add ARIA translation keys in app/translations/messages.fr.yaml: card.social.*.aria for each platform's aria-label in French (e.g., "Visiter le profil LinkedIn")

**Checkpoint**: All user stories should now be independently functional with full accessibility support meeting WCAG 2.1 Level AA standards.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories, testing, validation, and documentation

- [ ] T050 [P] Run PHPUnit tests for VCardService in app/tests/Service/VCardServiceTest.php and verify all tests pass
- [ ] T051 [P] Run PHPUnit tests for PublicCardController download endpoint in app/tests/Controller/PublicCardControllerDownloadTest.php and verify all tests pass
- [ ] T052 [P] Run PHPUnit tests for CardFormType in app/tests/Form/CardFormTypeTest.php and verify all tests pass
- [ ] T053 [P] Run PHPUnit tests for SocialProfileValidator in app/tests/Validator/Constraints/SocialProfileValidatorTest.php and verify all tests pass
- [ ] T054 Test vCard download on iOS device (iPhone Safari) by navigating to public card, downloading vCard, importing to Contacts app, verifying all fields including social networks
- [ ] T055 Test vCard download on Android device (Chrome Android) by navigating to public card, downloading vCard, importing to Contacts app, verifying all fields
- [ ] T056 Test vCard import on desktop (Gmail Contacts or Outlook) by downloading vCard file, importing via web interface, verifying contact imports with all fields
- [ ] T057 Test mobile responsiveness by viewing public card on different screen sizes (320px, 375px, 412px, 768px, 1024px, 1920px), verifying layout adapts correctly, no horizontal scrolling, buttons are touch-friendly
- [ ] T058 Test accessibility with axe DevTools or WAVE by running automated accessibility scan on public card page, addressing any issues found, aiming for WCAG 2.1 Level AA compliance
- [ ] T059 Test keyboard navigation by using Tab key to navigate all interactive elements on public card, verifying focus indicators are visible, no keyboard traps, logical tab order
- [ ] T060 Test screen reader by using VoiceOver (macOS/iOS) or NVDA (Windows) to navigate public card, verifying all elements are announced correctly with descriptive labels
- [ ] T061 Update Card entity documentation in app/src/Entity/Card.php to document extended social network fields in content JSON structure (instagram, tiktok, facebook, x, bluebirds, snapchat, planity, other)
- [ ] T062 Clear Symfony cache and rebuild assets by running php bin/console cache:clear and npm run build in app/ directory
- [ ] T063 Verify backward compatibility by viewing existing cards created before Feature 008, ensuring they display correctly with only linkedin/twitter fields, no errors for missing new social fields
- [ ] T064 Run quickstart.md validation by following all test scenarios in specs/008-public-card-enhancements/quickstart.md, verifying each test passes
- [ ] T065 [P] Add print styles in app/assets/styles/public-card.scss with @media print to hide download button and footer, adjust card styling for printing
- [ ] T066 [P] Update public card template in app/templates/public/card.html.twig to add vcard_filename variable passed from controller for download filename attribute
- [ ] T067 Add Cache-Control headers in PublicCardController::download() in app/src/Controller/PublicCardController.php to enable browser caching (public, max-age=3600)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phases 3-6)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order: US1 (P1) → US3 (P1) → US2 (P2) → US4 (P2)
- **Polish (Phase 7)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories - Requires VCardService
- **User Story 2 (P2)**: Can start after Foundational (Phase 2) - No dependencies on other stories - Requires SocialProfileValidator and updated CardFormType
- **User Story 3 (P1)**: Can start after Foundational (Phase 2) - May integrate with US1 (download button) but independently testable - Requires public-card.scss
- **User Story 4 (P2)**: Can start after US1, US2, US3 are complete - Enhances existing templates and styles with accessibility features

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Services before controllers
- Validators before forms
- Templates before styling
- Translations can run in parallel with implementation
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T001, T002, T003)
- All Foundational tasks marked [P] can run in parallel: VCardService (T004), SocialProfile constraint (T005), public-card.scss (T008)
- Once Foundational phase completes, User Stories 1, 2, and 3 can start in parallel (different files, minimal conflicts)
- All tests for a user story marked [P] can run in parallel (T010, T011 for US1)
- Translation updates can run in parallel (EN/FR files are separate)
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Create VCardServiceTest in app/tests/Service/VCardServiceTest.php"
Task: "Create PublicCardControllerDownloadTest in app/tests/Controller/PublicCardControllerDownloadTest.php"

# Launch translation updates together:
Task: "Add translation keys in app/translations/messages.en.yaml"
Task: "Add translation keys in app/translations/messages.fr.yaml"
```

## Parallel Example: User Story 2

```bash
# Launch all tests for User Story 2 together:
Task: "Create CardFormTypeTest in app/tests/Form/CardFormTypeTest.php"
Task: "Create SocialProfileValidatorTest in app/tests/Validator/Constraints/SocialProfileValidatorTest.php"

# Launch translation updates together:
Task: "Add social network translations in app/translations/messages.en.yaml"
Task: "Add social network translations in app/translations/messages.fr.yaml"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (verify dependencies)
2. Complete Phase 2: Foundational (CRITICAL - VCardService, validators, form updates)
3. Complete Phase 3: User Story 1 (download functionality)
4. **STOP and VALIDATE**: Test vCard download on iOS, Android, desktop
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 (P1) → Test independently → Deploy/Demo (MVP - download contacts!)
3. Add User Story 3 (P1) → Test independently → Deploy/Demo (mobile-optimized cards)
4. Add User Story 2 (P2) → Test independently → Deploy/Demo (expanded social networks)
5. Add User Story 4 (P2) → Test independently → Deploy/Demo (accessibility)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (Download Contact)
   - Developer B: User Story 2 (Social Networks)
   - Developer C: User Story 3 (Mobile Optimization)
3. Developer D adds User Story 4 (Accessibility) after others complete
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Feature extends existing Feature 005 - no database migrations needed (using Card.content JSON field)
- All new social networks stored in Card.content['social'] array
- Bootstrap Icons already available in project - no new dependencies for icons
- vCard files cached for 1 hour to improve performance
- Mobile-first design prioritizes smartphone users (most common use case)
- Accessibility is critical for legal compliance and inclusive design
- Backward compatibility maintained for existing cards with only linkedin/twitter fields
