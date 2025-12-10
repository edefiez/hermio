# Tasks: Branding & Theme (Pro / Enterprise)

**Input**: Design documents from `/specs/006-branding-theme/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `app/src/`, `app/templates/`, `app/migrations/`
- Paths shown below follow Symfony 8 structure

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [x] T001 Create logo storage directory at `app/public/uploads/branding/logos/` with proper permissions (755)
- [x] T002 [P] Add branding translation keys to `app/translations/messages.en.yaml` for branding interface
- [x] T003 [P] Add branding translation keys to `app/translations/messages.fr.yaml` for branding interface

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 [US1] [US2] [US3] [US4] Create AccountBranding entity in `app/src/Entity/AccountBranding.php` with OneToOne relationship to Account, color fields (primaryColor, secondaryColor), logo fields (logoFilename, logoPosition, logoSize), customTemplate field, and lifecycle callbacks
- [x] T005 [US1] [US2] [US3] [US4] Update Account entity in `app/src/Entity/Account.php` to add OneToOne relationship to AccountBranding (mappedBy: 'account', cascade: ['persist', 'remove'])
- [x] T006 [US1] [US2] [US3] [US4] Create AccountBrandingRepository in `app/src/Repository/AccountBrandingRepository.php` with findOneByAccount method
- [x] T007 [US1] [US2] [US3] [US4] Create Doctrine migration for account_branding table with all required columns, indexes, and foreign key constraints
- [x] T008 [US1] [US2] [US3] [US4] Run migration to create account_branding table in database

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Configure Brand Colors (Priority: P1) 🎯 MVP

**Goal**: Pro/Enterprise account owners can configure primary and secondary brand colors that are applied to all their public card pages

**Independent Test**: Log in as Pro/Enterprise user, navigate to branding settings, configure brand colors, save, and verify that public card pages display with configured colors instead of default colors

### Implementation for User Story 1

- [x] T009 [P] [US1] Create BrandingFormType in `app/src/Form/BrandingFormType.php` with primaryColor and secondaryColor fields (TextType) with hex color validation constraints
- [x] T010 [US1] Create BrandingService in `app/src/Service/BrandingService.php` with canConfigureBranding method (checks Pro/Enterprise plan), getBrandingForAccount method, and configureBranding method for colors
- [x] T011 [US1] Create BrandingController in `app/src/Controller/BrandingController.php` with configure method (GET /branding/configure) that displays branding form and handles plan-based access control
- [x] T012 [US1] Update BrandingController in `app/src/Controller/BrandingController.php` to handle POST /branding/configure for saving color configuration
- [x] T013 [US1] Create branding configuration template in `app/templates/branding/configure.html.twig` with form for color configuration and plan-based access messages
- [x] T014 [US1] Update PublicCardController in `app/src/Controller/PublicCardController.php` to retrieve branding via BrandingService and pass to template
- [x] T015 [US1] Update public card template in `app/templates/public/card.html.twig` to apply brand colors via CSS custom properties (CSS variables) when branding is configured

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently - Pro/Enterprise users can configure colors and see them on public card pages

---

## Phase 4: User Story 2 - Upload and Configure Brand Logo (Priority: P1) 🎯 MVP

**Goal**: Pro/Enterprise account owners can upload logos and configure their display position and size on public card pages

**Independent Test**: Log in as Pro/Enterprise user, upload a logo image through the branding interface, save, and verify that the logo appears on public card pages in the configured location

### Implementation for User Story 2

- [x] T016 [US2] Update BrandingFormType in `app/src/Form/BrandingFormType.php` to add logo field (FileType), logoPosition field (ChoiceType), and logoSize field (ChoiceType) with validation constraints
- [x] T017 [US2] Update BrandingService in `app/src/Service/BrandingService.php` to add uploadLogo method (handles file upload, secure filename generation, file validation), deleteLogoFile method, and validateLogoFile method
- [x] T018 [US2] Update BrandingService in `app/src/Service/BrandingService.php` configureBranding method to handle logo file upload, old logo deletion, and logo position/size configuration
- [x] T019 [US2] Update BrandingController in `app/src/Controller/BrandingController.php` configure method to handle logo file upload in form submission
- [x] T020 [US2] Add removeLogo method to BrandingController in `app/src/Controller/BrandingController.php` for POST /branding/logo/remove route
- [x] T021 [US2] Update branding configuration template in `app/templates/branding/configure.html.twig` to display logo upload field, current logo preview (if exists), logo position and size selectors, and remove logo button
- [x] T022 [US2] Update public card template in `app/templates/public/card.html.twig` to display logo image when branding.logoFilename is configured, using configured position and size

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently - users can configure colors and logos, and see them on public card pages

---

## Phase 5: User Story 4 - View Branded Public Card Pages (Priority: P1) 🎯 MVP

**Goal**: Public card pages display configured branding (colors, logo) correctly for accounts with branding configured, and default styling for accounts without branding

**Independent Test**: Navigate to a public card URL for an account with configured branding and verify that colors, logo, and template customizations are correctly applied and displayed

### Implementation for User Story 4

- [x] T023 [US4] Update BrandingService in `app/src/Service/BrandingService.php` getBrandingForAccount method to apply plan restrictions (Free plan returns null, Pro plan disables template feature)
- [x] T024 [US4] Ensure PublicCardController in `app/src/Controller/PublicCardController.php` passes branding data to template (already done in T014, verify integration)
- [x] T025 [US4] Update public card template in `app/templates/public/card.html.twig` to handle cases where branding is null (display default styling) and where branding exists (apply colors and logo)
- [x] T026 [US4] Add CSS styling in public card template for logo positioning (top-left, top-center, top-right, center, bottom-left, bottom-center, bottom-right) and sizing (small, medium, large)
- [x] T027 [US4] Verify branding changes reflect immediately on public card pages without requiring cache clearing (ensure no caching issues)

**Checkpoint**: At this point, User Stories 1, 2, and 4 should all work together - complete MVP for branding colors and logos on public card pages

---

## Phase 6: User Story 3 - Customize Template Inheritance (Priority: P2)

**Goal**: Enterprise account owners can configure custom Twig templates that override default templates for their public card pages while maintaining template inheritance structure

**Independent Test**: Log in as Enterprise user, configure a custom template (or template overrides), save, and verify that public card pages render using the custom template structure while maintaining proper inheritance

### Implementation for User Story 3

- [x] T028 [P] [US3] Create TemplateFormType in `app/src/Form/TemplateFormType.php` with customTemplate field (TextareaType) for Enterprise template configuration
- [x] T029 [US3] Update BrandingService in `app/src/Service/BrandingService.php` to add canConfigureTemplate method (checks Enterprise plan only) and validateTemplateSyntax method (validates Twig syntax, checks for dangerous functions, ensures extends base template)
- [x] T030 [US3] Add configureTemplate method to BrandingService in `app/src/Service/BrandingService.php` for saving custom template content with validation
- [x] T031 [US3] Create TemplateResolverService in `app/src/Service/TemplateResolverService.php` with resolveTemplate method that returns custom template for Enterprise accounts or default template
- [x] T032 [US3] Update BrandingController in `app/src/Controller/BrandingController.php` configure method to create and handle TemplateFormType for Enterprise users
- [x] T033 [US3] Add saveTemplate method to BrandingController in `app/src/Controller/BrandingController.php` for POST /branding/template route (Enterprise only)
- [x] T034 [US3] Update branding configuration template in `app/templates/branding/configure.html.twig` to display template configuration section for Enterprise users only (check canConfigureTemplate flag)
- [x] T035 [US3] Update PublicCardController in `app/src/Controller/PublicCardController.php` to use TemplateResolverService to determine which template to render (custom or default)
- [x] T036 [US3] Update public card rendering logic to support custom templates while maintaining template inheritance (ensure custom templates extend base template)

**Checkpoint**: At this point, all user stories should be independently functional - Enterprise users can configure custom templates, and all branding features work together

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T037 [P] Add resetBranding method to BrandingService in `app/src/Service/BrandingService.php` for resetting all branding configuration
- [x] T038 Add reset method to BrandingController in `app/src/Controller/BrandingController.php` for POST /branding/reset route with confirmation
- [x] T039 Update branding configuration template in `app/templates/branding/configure.html.twig` to add reset branding button with confirmation dialog
- [x] T040 [P] Add error handling and validation error messages display in branding configuration template
- [x] T041 [P] Add flash message display for success/error feedback in branding configuration template
- [x] T042 [P] Add plan downgrade handling in BrandingService to gracefully disable features when plan changes (preserve data, disable features)
- [x] T043 [P] Add integration with AccountService to handle plan changes and update branding access accordingly
- [ ] T044 [P] Add accessibility considerations for color contrast warnings (optional enhancement)
- [x] T045 [P] Add logo file cleanup when account is deleted (ensure orphaned files are removed)
- [x] T046 [P] Update translations with all branding-related messages (error messages, success messages, labels, help text)
- [x] T047 [P] Add security hardening for file uploads (additional validation, sanitization)
- [x] T048 [P] Add performance optimization for branding queries (ensure no N+1 queries)
- [x] T044 [P] Add accessibility considerations for color contrast warnings (optional enhancement)
- [x] T049 Run quickstart.md validation to ensure all implementation steps are complete

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
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - Shares BrandingService and BrandingController with US1, but can be implemented incrementally
- **User Story 4 (P1)**: Can start after Foundational (Phase 2) - Depends on US1 and US2 for branding data, but integration can be done after US1/US2 are complete
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - Depends on BrandingService infrastructure from US1/US2, but template feature is independent

### Within Each User Story

- Models/Entities before services
- Services before controllers
- Controllers before templates
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T002, T003)
- Foundational tasks T004-T007 can run in parallel (different files)
- User Story 1: T009 can run in parallel with T010 (form and service)
- User Story 2: T016 can run in parallel with T017 (form updates and service updates)
- User Story 3: T028 can run in parallel with T029 (form and service methods)
- Polish phase tasks marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members once Foundational phase completes

---

## Parallel Example: User Story 1

```bash
# Launch foundational tasks together:
Task: "Create AccountBranding entity in app/src/Entity/AccountBranding.php"
Task: "Update Account entity in app/src/Entity/Account.php"
Task: "Create AccountBrandingRepository in app/src/Repository/AccountBrandingRepository.php"
Task: "Create Doctrine migration for account_branding table"

# Launch User Story 1 tasks together:
Task: "Create BrandingFormType in app/src/Form/BrandingFormType.php"
Task: "Create BrandingService in app/src/Service/BrandingService.php"
```

---

## Parallel Example: User Story 2

```bash
# Launch User Story 2 tasks together:
Task: "Update BrandingFormType in app/src/Form/BrandingFormType.php"
Task: "Update BrandingService in app/src/Service/BrandingService.php" (add logo methods)
```

---

## Implementation Strategy

### MVP First (User Stories 1, 2, 4 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Configure Brand Colors)
4. Complete Phase 4: User Story 2 (Upload and Configure Brand Logo)
5. Complete Phase 5: User Story 4 (View Branded Public Card Pages)
6. **STOP and VALIDATE**: Test all MVP stories independently
7. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP colors!)
3. Add User Story 2 → Test independently → Deploy/Demo (MVP colors + logos!)
4. Add User Story 4 → Test independently → Deploy/Demo (Complete MVP!)
5. Add User Story 3 → Test independently → Deploy/Demo (Enterprise feature)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (colors)
   - Developer B: User Story 2 (logos) - can start after T010 completes
   - Developer C: User Story 4 (public display) - can start after T014 completes
3. After MVP stories complete:
   - Developer A: User Story 3 (templates)
4. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- Branding is account-scoped (one branding per account)
- Plan-based access enforced at service layer
- Logo files stored in `app/public/uploads/branding/logos/`
- Custom templates stored in database (TEXT field)
- Branding applied only to public card pages (`/c/<slug>`), not authenticated dashboard pages

