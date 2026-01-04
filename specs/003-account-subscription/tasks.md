# Tasks: Account Management / Subscription Model

**Input**: Design documents from `/specs/003-account-subscription/`
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

**Purpose**: Verify project structure and prepare for feature implementation

- [x] T001 Verify Symfony 8.0+ and PHP 8.4+ are installed and configured
- [x] T002 Verify Doctrine ORM 3.x is installed and database connection is configured
- [x] T003 [P] Verify User entity exists in app/src/Entity/User.php with authentication working
- [x] T004 [P] Verify UserRegistrationService exists in app/src/Service/UserRegistrationService.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 [P] Create PlanType enum in app/src/Enum/PlanType.php with FREE, PRO, ENTERPRISE cases and getQuotaLimit(), isUnlimited(), getDisplayName() methods
- [x] T006 [P] Create Account entity in app/src/Entity/Account.php with properties: id, user (OneToOne), planType (PlanType enum), createdAt, updatedAt, updatedBy, and lifecycle callbacks
- [x] T007 [US1] Modify User entity in app/src/Entity/User.php to add OneToOne relationship to Account (mappedBy: 'user', cascade: ['persist', 'remove'])
- [x] T008 Create AccountRepository in app/src/Repository/AccountRepository.php extending ServiceEntityRepository
- [x] T009 Create database migration for accounts table in app/migrations/Version[timestamp].php with columns: id, user_id (FK to users, unique), plan_type, created_at, updated_at, updated_by
- [x] T010 Run migration and create data migration script to create Account records for all existing users with FREE plan in app/migrations/Version[timestamp].php
- [x] T011 Create QuotaService in app/src/Service/QuotaService.php with constructor injection of CardRepository (or placeholder), canCreateContent(), validateQuota(), getCurrentUsage() methods
- [x] T012 Create AccountService in app/src/Service/AccountService.php with constructor injection of AccountRepository and QuotaService, createDefaultAccount(), changePlan(), isDowngrade() methods
- [x] T013 [US1] Modify UserRegistrationService in app/src/Service/UserRegistrationService.php to call AccountService::createDefaultAccount() after user creation

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - View My Subscription Plan (Priority: P1) 🎯 MVP

**Goal**: Users can view their current subscription plan details, quota limits, and usage on the "My Plan" page

**Independent Test**: Navigate to `/account/my-plan` as logged-in user and verify that current plan type, quota limit, and current usage are displayed accurately for Free, Pro, and Enterprise plans

### Implementation for User Story 1

- [x] T014 [P] [US1] Create AccountController in app/src/Controller/AccountController.php with myPlan() method that gets user's account, calculates usage via QuotaService, and renders template
- [x] T015 [US1] Add route configuration for GET /account/my-plan in app/config/routes.yaml or AccountController with route name app_account_my_plan
- [x] T016 [P] [US1] Create my_plan.html.twig template in app/templates/account/my_plan.html.twig displaying plan type, quota limit, current usage, usage percentage, and plan activation date
- [x] T017 [US1] Add translation keys for plan display in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (account.plan.free, account.plan.pro, account.plan.enterprise, account.quota.limit, account.quota.unlimited, account.quota.usage, account.plan.activated)
- [x] T018 [US1] Handle case where user has no account in AccountController::myPlan() by creating default account and redirecting

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. Users can view their plan details on the "My Plan" page.

---

## Phase 4: User Story 2 - Understand Quota Limits When Creating Content (Priority: P1)

**Goal**: Users see clear feedback about quota limits when attempting to create content that exceeds their plan's allowance

**Independent Test**: Attempt to create content that exceeds user's quota limit and verify that a clear message is displayed explaining the limit and suggesting upgrade options

### Implementation for User Story 2

- [x] T019 [US2] Create QuotaExceededException in app/src/Exception/QuotaExceededException.php with user-friendly message including current usage, limit, and upgrade suggestions
- [x] T020 [US2] Implement quota validation logic in QuotaService::validateQuota() that throws QuotaExceededException when limit would be exceeded
- [x] T021 [US2] Add getQuotaMessage() method to QuotaService that generates user-friendly quota exceeded messages with upgrade suggestions
- [x] T022 [US2] Add translation keys for quota messages in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (account.quota.exceeded, account.quota.upgrade.pro, account.quota.upgrade.enterprise)
- [x] T023 [US2] Integrate QuotaService::validateQuota() into content creation flow (wherever Card entities are created) to enforce quota limits before creation
- [x] T024 [US2] Create error handling in content creation controllers to catch QuotaExceededException and display user-friendly error message with link to "My Plan" page

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently. Users see clear quota limit messages when attempting to exceed their plan limits.

---

## Phase 5: User Story 3 - Administrators Can Manage User Accounts and Plans (Priority: P2)

**Goal**: Administrators can view and modify user account plans and quotas through an administrative interface

**Independent Test**: Log in as administrator, access user management interface, and successfully view and modify a user's plan type and quota settings

### Implementation for User Story 3

- [x] T025 [P] [US3] Create AdminAccountController in app/src/Controller/AdminAccountController.php with index() method listing all accounts with pagination and plan filtering
- [x] T026 [P] [US3] Add route configuration for GET /admin/accounts in app/config/routes.yaml or AdminAccountController with route name app_admin_account_index and ROLE_ADMIN authorization
- [x] T027 [P] [US3] Create AdminAccountController::show() method in app/src/Controller/AdminAccountController.php displaying account details with plan change form
- [x] T028 [P] [US3] Add route configuration for GET /admin/accounts/{id} in app/config/routes.yaml or AdminAccountController with route name app_admin_account_show and ROLE_ADMIN authorization
- [x] T029 [P] [US3] Create PlanChangeFormType in app/src/Form/PlanChangeFormType.php with planType (ChoiceType) and confirmDowngrade (CheckboxType) fields
- [x] T030 [US3] Add custom validation to PlanChangeFormType for downgrade confirmation requirement when user exceeds new plan's quota limit
- [x] T031 [US3] Implement AdminAccountController::changePlan() method in app/src/Controller/AdminAccountController.php handling POST /admin/accounts/{id}/change-plan with form processing and AccountService::changePlan() call
- [x] T032 [US3] Add route configuration for POST /admin/accounts/{id}/change-plan in app/config/routes.yaml or AdminAccountController with route name app_admin_account_change_plan and ROLE_ADMIN authorization
- [x] T033 [P] [US3] Create index.html.twig template in app/templates/admin/account/index.html.twig displaying paginated list of accounts with plan types, usage, and links to details
- [x] T034 [P] [US3] Create show.html.twig template in app/templates/admin/account/show.html.twig displaying account details and plan change form with downgrade warnings
- [x] T035 [US3] Add translation keys for admin interface in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (admin.account.list, admin.account.details, admin.account.change_plan, admin.account.plan_changed, admin.account.downgrade_warning)
- [x] T036 [US3] Implement AccountService::changePlan() logic to update planType, set updatedAt and updatedBy (from security context), and handle downgrade validation
- [x] T037 [US3] Add flash message handling in AdminAccountController for success and error messages when changing plans

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently. Administrators can manage user accounts and change plans through the admin interface.

---

## Phase 6: User Story 4 - Manage My Account Settings (Priority: P3)

**Goal**: Users can access and manage their account settings and subscription information from a dedicated account management page

**Independent Test**: Navigate to account management page and verify that users can view their account information, subscription details, and access relevant account settings

### Implementation for User Story 4

- [x] T038 [P] [US4] Implement AccountController::index() method in app/src/Controller/AccountController.php displaying account overview with plan summary
- [x] T039 [US4] Add route configuration for GET /account in app/config/routes.yaml or AccountController with route name app_account_index
- [x] T040 [P] [US4] Create index.html.twig template in app/templates/account/index.html.twig displaying account information, subscription plan summary, and links to "My Plan" page
- [x] T041 [US4] Add translation keys for account management page in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (account.management.title, account.management.overview, account.management.view_plan)

**Checkpoint**: All user stories should now be independently functional. Users have a complete account management interface.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T042 [P] Complete all translation keys for account management feature in app/translations/messages.en.yaml and app/translations/messages.fr.yaml (verify all user-facing text is translated)
- [x] T043 [P] Add navigation links to account management pages in base template or navigation menu in app/templates/base.html.twig
- [x] T044 [P] Style account management templates to match application design system (CSS/styling updates)
- [x] T045 Verify UserRegistrationService creates Account for all new user registrations automatically
- [x] T046 Add error handling for edge cases: user without account, invalid plan types, quota calculation errors
- [x] T047 Add logging for plan changes and quota validation in AccountService and QuotaService
- [x] T048 Verify all routes have proper CSRF protection and authorization checks
- [x] T049 Run quickstart.md validation to ensure implementation matches quickstart guide
- [x] T050 Code review: Verify Controllers → Services → Repositories architecture is followed
- [x] T051 Code review: Verify all business logic is in services, not controllers
- [x] T052 Code review: Verify PSR-12 coding standards are followed throughout

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - Uses QuotaService from foundational phase, integrates with content creation (assumes Card entity exists)
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - Uses AccountService and QuotaService from foundational phase, no dependencies on US1/US2
- **User Story 4 (P3)**: Can start after Foundational (Phase 2) - Uses AccountController from US1, minimal dependencies

### Within Each User Story

- Models/Entities before services
- Services before controllers
- Controllers before templates
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- Models/entities within a story marked [P] can run in parallel
- Controllers and templates marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all parallel tasks for User Story 1 together:
Task: "Create AccountController in app/src/Controller/AccountController.php"
Task: "Create my_plan.html.twig template in app/templates/account/my_plan.html.twig"
Task: "Add translation keys for plan display in app/translations/messages.en.yaml"
```

---

## Parallel Example: User Story 3

```bash
# Launch all parallel tasks for User Story 3 together:
Task: "Create AdminAccountController in app/src/Controller/AdminAccountController.php"
Task: "Create PlanChangeFormType in app/src/Form/PlanChangeFormType.php"
Task: "Create index.html.twig template in app/templates/admin/account/index.html.twig"
Task: "Create show.html.twig template in app/templates/admin/account/show.html.twig"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (View My Plan)
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

**MVP Scope**: Users can view their subscription plan details and quota usage. This provides immediate value and enables all other subscription features.

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 2 → Test independently → Deploy/Demo (Quota enforcement)
4. Add User Story 3 → Test independently → Deploy/Demo (Admin management)
5. Add User Story 4 → Test independently → Deploy/Demo (Account management hub)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (View My Plan)
   - Developer B: User Story 2 (Quota Limits) - can start in parallel with US1
   - Developer C: User Story 3 (Admin Management) - can start in parallel
3. Stories complete and integrate independently
4. Developer D: User Story 4 (Account Management) - can start after US1 is complete

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- QuotaService assumes Card entity exists - if not, placeholder or mock can be used until Card entity is implemented
- All user-facing text must use translation keys (EN/FR support)
- All admin routes require ROLE_ADMIN authorization
- All forms require CSRF protection

---

## Task Summary

**Total Tasks**: 52
- Phase 1 (Setup): 4 tasks
- Phase 2 (Foundational): 9 tasks
- Phase 3 (US1 - View My Plan): 5 tasks
- Phase 4 (US2 - Quota Limits): 6 tasks
- Phase 5 (US3 - Admin Management): 13 tasks
- Phase 6 (US4 - Account Management): 4 tasks
- Phase 7 (Polish): 11 tasks

**Parallel Opportunities**: 
- 15 tasks marked [P] can run in parallel
- User stories can be implemented in parallel after foundational phase
- Multiple developers can work on different stories simultaneously

**Independent Test Criteria**:
- **US1**: Navigate to `/account/my-plan` and verify plan details display correctly
- **US2**: Attempt to create content exceeding quota and verify error message displays
- **US3**: Log in as admin, access `/admin/accounts`, view and modify user plans
- **US4**: Navigate to `/account` and verify account management interface displays

**Suggested MVP Scope**: Phase 1 + Phase 2 + Phase 3 (User Story 1 only) - provides core value of viewing subscription plans and quota usage.

