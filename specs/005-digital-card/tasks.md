# Tasks: Digital Card Management

**Input**: Design documents from `/specs/005-digital-card/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are OPTIONAL and not included in this task list. Add test tasks if TDD approach is desired.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Symfony web app**: `app/src/`, `app/templates/`, `app/migrations/`
- All paths shown below use Symfony project structure

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Verify project structure and install dependencies

- [x] T001 Verify Symfony 8.0+ and PHP 8.4+ are installed and configured
- [x] T002 Verify Doctrine ORM 3.x is installed and database connection is configured
- [x] T003 [P] Verify User entity exists in app/src/Entity/User.php with authentication working
- [x] T004 [P] Verify Account entity and QuotaService exist (Feature 003 completed)
- [x] T005 Install endroid/qr-code library via composer require endroid/qr-code in app/

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T006 [P] Create Card entity in app/src/Entity/Card.php with properties: id, user (ManyToOne to User), slug (unique, 100 chars), content (JSON), status (active/deleted), createdAt, updatedAt, deletedAt, and lifecycle callbacks
- [x] T007 [US1] Modify User entity in app/src/Entity/User.php to add OneToMany relationship to Card (mappedBy: 'user', cascade: ['persist', 'remove']) with Collection $cards property and getCards(), addCard(), removeCard() methods
- [x] T008 Create CardRepository in app/src/Repository/CardRepository.php extending ServiceEntityRepository with findOneBySlug(), findByUser(), slugExists() methods
- [x] T009 Create database migration for cards table in app/migrations/Version[timestamp].php with columns: id, user_id (FK to users, CASCADE), slug (unique), content (JSON), status, created_at, updated_at, deleted_at, and indexes
- [x] T010 Run migration and verify cards table exists in database
- [x] T011 Update QuotaService in app/src/Service/QuotaService.php to inject CardRepository in constructor and update getCurrentUsage() to count active cards: $this->cardRepository->count(['user' => $user, 'status' => 'active'])
- [x] T012 Create CardService in app/src/Service/CardService.php with constructor injection of CardRepository, QuotaService, EntityManagerInterface, and methods: createCard(), updateCard(), deleteCard(), generateUniqueSlug(), slugify()
- [x] T013 Create QrCodeService in app/src/Service/QrCodeService.php with generateQrCode() and generateQrCodeBase64() methods using endroid/qr-code Builder

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Create Digital Card (Priority: P1) 🎯 MVP

**Goal**: Users can create digital cards with unique slugs, subject to quota validation

**Independent Test**: Log in, navigate to card creation interface, enter card information, submit form, and verify card is created with unique slug and accessible at `/c/<slug>`

### Implementation for User Story 1

- [x] T014 [P] [US1] Create CardFormType in app/src/Form/CardFormType.php with fields: name (TextType, required), email (EmailType), phone (TextType), company (TextType), title (TextType), bio (TextareaType), website (UrlType), linkedin (UrlType), twitter (UrlType)
- [x] T015 [US1] Create CardController in app/src/Controller/CardController.php with create() method handling GET and POST /cards/create, validating quota, calling CardService::createCard(), and redirecting with flash message
- [x] T016 [US1] Add route configuration for GET /cards/create in CardController with route name app_card_create and ROLE_USER authorization
- [x] T017 [US1] Add route configuration for POST /cards/create in CardController with route name app_card_create_post and ROLE_USER authorization
- [x] T018 [P] [US1] Create create.html.twig template in app/templates/card/create.html.twig displaying card creation form with quota limit information
- [x] T019 [US1] Implement CardService::createCard() to validate quota via QuotaService, generate unique slug, set user and status, persist card, and return Card entity
- [x] T020 [US1] Implement CardService::generateUniqueSlug() to create slug from card name, check uniqueness via CardRepository, append random suffix if conflict, and ensure URL-safe format
- [x] T021 [US1] Implement CardService::slugify() helper method to convert text to URL-safe slug (lowercase, replace spaces with hyphens, remove special chars, trim hyphens, max 100 chars)
- [x] T022 [US1] Add error handling in CardController::create() to catch QuotaExceededException and display user-friendly error message with upgrade options
- [x] T023 [P] [US1] Add translation keys for card creation in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (card.create.title, card.form.*, card.created.success, card.quota.exceeded)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. Users can create cards with unique slugs, subject to quota limits.

---

## Phase 4: User Story 2 - View Public Card Page (Priority: P1)

**Goal**: Anyone can view digital cards via public URL `/c/<slug>` without authentication

**Independent Test**: Navigate to `/c/<slug>` for existing card and verify card information displays correctly in styled template without login

### Implementation for User Story 2

- [x] T024 [P] [US2] Create PublicCardController in app/src/Controller/PublicCardController.php with show() method handling GET /c/{slug}, finding card by slug via CardRepository, and rendering template
- [x] T025 [US2] Add route configuration for GET /c/{slug} in PublicCardController with route name app_public_card, requirements: ['slug' => '[a-z0-9-]+'], and no authentication requirement
- [x] T026 [US2] Implement PublicCardController::show() to find card by slug with status='active', throw NotFoundHttpException if not found, and pass card to template with publicUrl
- [x] T027 [P] [US2] Create card.html.twig template in app/templates/public/card.html.twig displaying card information (name, email, phone, company, title, bio, social links, website) in styled, professional format
- [x] T028 [US2] Style public card template to be mobile-responsive (works on 320px to 1920px screen widths) and load quickly (< 2 seconds)
- [x] T029 [P] [US2] Add translation keys for public card page in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (card.public.*)
- [x] T030 [US2] Add 404 error handling in PublicCardController::show() for deleted cards or non-existent slugs with user-friendly error page

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently. Users can create cards and view them publicly via `/c/<slug>`.

---

## Phase 5: User Story 3 - Generate QR Code for Card (Priority: P2)

**Goal**: Card owners can generate QR codes linking to their card's public URL

**Independent Test**: Create card, generate QR code, scan with mobile device, and verify it navigates to card's public URL

### Implementation for User Story 3

- [x] T031 [US3] Add qrCode() method to CardController in app/src/Controller/CardController.php handling GET /cards/{id}/qr-code, validating ownership, generating QR code via QrCodeService, and rendering template
- [x] T032 [US3] Add route configuration for GET /cards/{id}/qr-code in CardController with route name app_card_qr_code and ROLE_USER authorization
- [x] T033 [US3] Implement ownership validation in CardController::qrCode() to ensure user owns the card before generating QR code
- [x] T034 [US3] Implement QrCodeService::generateQrCode() to create QR code using endroid/qr-code Builder with card's public URL, size 300, PNG format
- [x] T035 [US3] Implement QrCodeService::generateQrCodeBase64() to return base64-encoded QR code image data for embedding in HTML
- [x] T036 [P] [US3] Create qr_code.html.twig template in app/templates/card/qr_code.html.twig displaying QR code image, public URL, and download/print options
- [x] T037 [US3] Add link to QR code generation in card list and card detail pages
- [x] T038 [P] [US3] Add translation keys for QR code in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (card.qr_code.*)

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently. Users can create cards, view them publicly, and generate QR codes.

---

## Phase 6: User Story 4 - Manage Own Cards (Priority: P2)

**Goal**: Users can view, edit, and delete their own cards

**Independent Test**: Log in, view card list, edit card information, save changes, verify updates on public page, delete card, verify public URL returns 404

### Implementation for User Story 4

- [x] T039 [P] [US4] Add index() method to CardController in app/src/Controller/CardController.php handling GET /cards, fetching user's cards via CardRepository, calculating quota usage, and rendering template
- [x] T040 [US4] Add route configuration for GET /cards in CardController with route name app_card_index and ROLE_USER authorization
- [x] T041 [P] [US4] Create index.html.twig template in app/templates/card/index.html.twig displaying list of user's cards with edit/delete links, public URLs, and quota information
- [x] T042 [US4] Add edit() method to CardController in app/src/Controller/CardController.php handling GET and POST /cards/{id}/edit, validating ownership, processing form, calling CardService::updateCard(), and redirecting
- [x] T043 [US4] Add route configuration for GET /cards/{id}/edit in CardController with route name app_card_edit and ROLE_USER authorization
- [x] T044 [US4] Add route configuration for POST /cards/{id}/edit in CardController with route name app_card_edit_post and ROLE_USER authorization
- [x] T045 [US4] Implement ownership validation in CardController::edit() to ensure user owns the card before allowing edit
- [x] T046 [P] [US4] Create edit.html.twig template in app/templates/card/edit.html.twig displaying card editing form with current card data
- [x] T047 [US4] Implement CardService::updateCard() to update card content and flush changes to database
- [x] T048 [US4] Add delete() method to CardController in app/src/Controller/CardController.php handling POST /cards/{id}/delete, validating ownership, calling CardService::deleteCard(), and redirecting
- [x] T049 [US4] Add route configuration for POST /cards/{id}/delete in CardController with route name app_card_delete and ROLE_USER authorization
- [x] T050 [US4] Implement CardService::deleteCard() to call card->delete() (soft delete), update status to 'deleted', set deletedAt, and flush changes
- [x] T051 [US4] Verify deleted cards return 404 on public URL by updating PublicCardController::show() to only find active cards
- [x] T052 [US4] Verify quota decreases when card is deleted (only active cards count toward quota)
- [x] T053 [P] [US4] Add translation keys for card management in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (card.list.*, card.edit.*, card.delete.*, card.updated.success, card.deleted.success)

**Checkpoint**: At this point, all user stories should be independently functional. Users can create, view publicly, generate QR codes, and manage their cards.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T054 [P] Complete all translation keys for card management feature in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (verify all user-facing text is translated)
- [ ] T055 [P] Add navigation links to card management pages in base template or navigation menu in app/templates/base.html.twig
- [ ] T056 [P] Style card templates to match application design system (CSS/styling updates in app/assets/styles/)
- [ ] T057 Verify CardService slug generation handles edge cases: empty names, special characters, very long names, concurrent slug creation
- [ ] T058 Add error handling for edge cases: card not found, unauthorized access, invalid slug format, quota calculation errors
- [ ] T059 Add logging for card operations (creation, update, deletion) in CardService and CardController
- [ ] T060 Verify all routes have proper CSRF protection and authorization checks
- [ ] T061 Verify public card routes are accessible without authentication and properly handle 404 errors
- [ ] T062 Verify quota validation works correctly: Free (1 card), Pro (10 cards), Enterprise (unlimited)
- [ ] T063 Verify soft delete preserves quota calculation (deleted cards don't count toward quota)
- [ ] T064 Run quickstart.md validation to ensure implementation matches quickstart guide
- [ ] T065 Code review: Verify Controllers → Services → Repositories architecture is followed
- [ ] T066 Code review: Verify all business logic is in services, not controllers
- [ ] T067 Code review: Verify PSR-12 coding standards are followed throughout
- [ ] T068 Verify Card entity JSON content structure matches form fields and template expectations
- [ ] T069 Test QR code generation with various card URLs and verify scannability
- [ ] T070 Verify mobile responsiveness of public card pages and management interfaces

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - Uses Card entity from foundational phase, can run in parallel with US1
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - Uses Card entity and QrCodeService, depends on US2 for public URL
- **User Story 4 (P2)**: Can start after Foundational (Phase 2) - Uses Card entity and CardService, can run in parallel with US3

### Within Each User Story

- Entities/Models before services
- Services before controllers
- Controllers before templates
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, User Stories 1 and 2 can start in parallel
- User Stories 3 and 4 can run in parallel after US1 and US2 are complete
- Controllers and templates marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all parallel tasks for User Story 1 together:
Task: "Create CardFormType in app/src/Form/CardFormType.php"
Task: "Create create.html.twig template in app/templates/card/create.html.twig"
Task: "Add translation keys for card creation in app/translations/messages.en.yaml"
```

---

## Parallel Example: User Story 2

```bash
# Launch all parallel tasks for User Story 2 together:
Task: "Create PublicCardController in app/src/Controller/PublicCardController.php"
Task: "Create card.html.twig template in app/templates/public/card.html.twig"
Task: "Add translation keys for public card page in app/translations/messages.en.yaml"
```

---

## Implementation Strategy

### MVP First (User Stories 1 & 2 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Create Card)
4. Complete Phase 4: User Story 2 (View Public Card)
5. **STOP and VALIDATE**: Test User Stories 1 and 2 independently
6. Deploy/demo if ready

**MVP Scope**: Users can create digital cards and share them via public URLs. This provides immediate value and enables card sharing functionality.

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (Card Creation)
3. Add User Story 2 → Test independently → Deploy/Demo (Public Viewing) - MVP!
4. Add User Story 3 → Test independently → Deploy/Demo (QR Codes)
5. Add User Story 4 → Test independently → Deploy/Demo (Card Management)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (Create Card)
   - Developer B: User Story 2 (Public View) - can start in parallel
3. After US1 and US2 complete:
   - Developer C: User Story 3 (QR Codes)
   - Developer D: User Story 4 (Management) - can start in parallel
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- All user-facing text must use translation keys (EN/FR support)
- Card management routes require ROLE_USER authorization
- Public routes `/c/<slug>` accessible without authentication
- All forms require CSRF protection
- Quota validation happens at service layer before card creation
- Cards use soft delete (status='deleted') to preserve quota calculation

---

## Task Summary

**Total Tasks**: 70
- Phase 1 (Setup): 5 tasks
- Phase 2 (Foundational): 8 tasks
- Phase 3 (US1 - Create Card): 10 tasks
- Phase 4 (US2 - Public View): 7 tasks
- Phase 5 (US3 - QR Code): 8 tasks
- Phase 6 (US4 - Management): 15 tasks
- Phase 7 (Polish): 17 tasks

**Parallel Opportunities**: 
- 25 tasks marked [P] can run in parallel
- User Stories 1 and 2 can be implemented in parallel after foundational phase
- User Stories 3 and 4 can be implemented in parallel after US1 and US2
- Multiple developers can work on different stories simultaneously

**Independent Test Criteria**:
- **US1**: Log in, navigate to `/cards/create`, create card, verify unique slug and public URL
- **US2**: Navigate to `/c/<slug>` without login, verify card displays correctly
- **US3**: Create card, generate QR code, scan with mobile device, verify navigation to public URL
- **US4**: Log in, view card list, edit card, verify updates on public page, delete card, verify 404

**Suggested MVP Scope**: Phase 1 + Phase 2 + Phase 3 + Phase 4 (User Stories 1 & 2 only) - provides core value of creating and sharing digital cards via public URLs.

