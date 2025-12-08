# Tasks: User Account & Authentication

**Input**: Design documents from `/specs/002-user-account-auth/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are NOT requested in the feature specification, so no test tasks are included.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Based on plan.md structure: `app/src/`, `app/templates/`, `app/assets/` for Symfony 8 web application

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 Create database entities structure per data model in app/src/Entity/
- [ ] T002 [P] Configure Symfony security bundle in config/packages/security.yaml
- [ ] T003 [P] Configure mailer settings for email verification in config/packages/mailer.yaml

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T004 Create User entity in app/src/Entity/User.php
- [ ] T005 Create EmailVerificationToken entity in app/src/Entity/EmailVerificationToken.php
- [ ] T006 Create PasswordResetToken entity in app/src/Entity/PasswordResetToken.php
- [ ] T007 Create AuthenticationLog entity in app/src/Entity/AuthenticationLog.php
- [ ] T008 [P] Create UserRepository in app/src/Repository/UserRepository.php
- [ ] T009 [P] Create EmailVerificationTokenRepository in app/src/Repository/EmailVerificationTokenRepository.php
- [ ] T010 [P] Create PasswordResetTokenRepository in app/src/Repository/PasswordResetTokenRepository.php
- [ ] T011 [P] Create AuthenticationLogRepository in app/src/Repository/AuthenticationLogRepository.php
- [ ] T012 Generate and run database migration for authentication tables
- [ ] T013 Create AuthenticationLogService for security audit logging in app/src/Service/AuthenticationLogService.php
- [ ] T014 [P] Configure base templates structure in app/templates/base.html.twig
- [ ] T015 [P] Setup authentication styles in app/assets/styles/auth.scss

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - User Registration (Priority: P1) 🎯 MVP

**Goal**: Enable new users to create accounts with email verification

**Independent Test**: Navigate to /register, enter valid credentials, submit form, verify account created and confirmation email sent

### Implementation for User Story 1

- [ ] T016 [P] [US1] Create RegistrationFormType in app/src/Form/RegistrationFormType.php
- [ ] T017 [P] [US1] Create UserRegistrationService in app/src/Service/UserRegistrationService.php
- [ ] T018 [P] [US1] Create EmailVerificationService in app/src/Service/EmailVerificationService.php
- [ ] T019 [US1] Create RegistrationController in app/src/Controller/RegistrationController.php
- [ ] T020 [US1] Implement registration route handler in RegistrationController::register method
- [ ] T021 [US1] Implement email verification route handler in RegistrationController::verifyEmail method
- [ ] T022 [P] [US1] Create registration form template in app/templates/security/register.html.twig
- [ ] T023 [P] [US1] Create email verification template in app/templates/registration/confirmation_email.html.twig
- [ ] T024 [P] [US1] Create registration form JavaScript controller in app/assets/controllers/registration_controller.js
- [ ] T025 [US1] Add registration routes to config/routes.yaml
- [ ] T026 [US1] Add form validation and error handling for registration
- [ ] T027 [US1] Add security logging for registration events

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - User Login (Priority: P1)

**Goal**: Enable registered users to authenticate and access the application

**Independent Test**: Navigate to /login, enter valid credentials, verify successful authentication and redirect

### Implementation for User Story 2

- [ ] T028 [P] [US2] Create LoginFormType in app/src/Form/LoginFormType.php
- [ ] T029 [P] [US2] Create LoginFormAuthenticator in app/src/Security/LoginFormAuthenticator.php
- [ ] T030 [P] [US2] Create UserChecker for email verification check in app/src/Security/UserChecker.php
- [ ] T031 [US2] Create SecurityController in app/src/Controller/SecurityController.php
- [ ] T032 [US2] Implement login route handler in SecurityController::login method
- [ ] T033 [US2] Implement logout route handler in SecurityController::logout method
- [ ] T034 [P] [US2] Create login form template in app/templates/security/login.html.twig
- [ ] T035 [P] [US2] Create login form JavaScript controller in app/assets/controllers/login_controller.js
- [ ] T036 [US2] Configure security firewall for form login authentication
- [ ] T037 [US2] Add login and logout routes to config/routes.yaml
- [ ] T038 [US2] Implement rate limiting for failed login attempts
- [ ] T039 [US2] Add security logging for login/logout events
- [ ] T040 [US2] Add remember me functionality configuration

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Password Reset (Priority: P2)

**Goal**: Enable users to reset forgotten passwords via secure email links

**Independent Test**: Navigate to /reset-password, enter email, receive reset email, use link to set new password, login with new credentials

### Implementation for User Story 3

- [ ] T041 [P] [US3] Create PasswordResetRequestFormType in app/src/Form/PasswordResetRequestFormType.php
- [ ] T042 [P] [US3] Create ChangePasswordFormType in app/src/Form/ChangePasswordFormType.php
- [ ] T043 [P] [US3] Create PasswordResetService in app/src/Service/PasswordResetService.php
- [ ] T044 [US3] Create ResetPasswordController in app/src/Controller/ResetPasswordController.php
- [ ] T045 [US3] Implement password reset request handler in ResetPasswordController::request method
- [ ] T046 [US3] Implement password reset form handler in ResetPasswordController::reset method
- [ ] T047 [P] [US3] Create password reset request template in app/templates/security/reset_password/request.html.twig
- [ ] T048 [P] [US3] Create password reset form template in app/templates/security/reset_password/reset.html.twig
- [ ] T049 [P] [US3] Create password reset email template in app/templates/emails/password_reset.html.twig
- [ ] T050 [US3] Add password reset routes to config/routes.yaml
- [ ] T051 [US3] Add token expiration and validation logic
- [ ] T052 [US3] Add security logging for password reset events
- [ ] T053 [US3] Implement rate limiting for password reset requests

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently

---

## Phase 6: User Story 4 - Session Management (Priority: P2)

**Goal**: Manage user sessions with proper timeout and logout functionality

**Independent Test**: Login, verify session persists across pages, test session timeout, verify logout terminates session

### Implementation for User Story 4

- [ ] T054 [P] [US4] Configure session management in config/packages/framework.yaml
- [ ] T055 [P] [US4] Create SessionService for session handling in app/src/Service/SessionService.php
- [ ] T056 [US4] Implement session timeout handling in security configuration
- [ ] T057 [US4] Add session activity tracking to AuthenticationLogService
- [ ] T058 [P] [US4] Create logout confirmation modal in templates
- [ ] T059 [US4] Configure remember me functionality with secure cookies
- [ ] T060 [US4] Add session cleanup console command for expired sessions
- [ ] T061 [US4] Implement concurrent session detection and logging

**Checkpoint**: At this point, User Stories 1-4 should all work with proper session management

---

## Phase 7: User Story 5 - Profile Management (Priority: P3)

**Goal**: Allow users to view and update their account information

**Independent Test**: Login, navigate to /profile, update email and password, verify changes persist and email verification required for email changes

### Implementation for User Story 5

- [ ] T062 [P] [US5] Create UserProfileFormType in app/src/Form/UserProfileFormType.php
- [ ] T063 [P] [US5] Create ChangePasswordFormType for current password validation in app/src/Form/ChangePasswordFormType.php
- [ ] T064 [US5] Create ProfileController in app/src/Controller/ProfileController.php
- [ ] T065 [US5] Implement profile view handler in ProfileController::index method
- [ ] T066 [US5] Implement profile update handler in ProfileController::update method
- [ ] T067 [US5] Implement password change handler in ProfileController::changePassword method
- [ ] T068 [P] [US5] Create profile page template in app/templates/profile/index.html.twig
- [ ] T069 [P] [US5] Create password change template in app/templates/profile/change_password.html.twig
- [ ] T070 [US5] Add profile routes to config/routes.yaml
- [ ] T071 [US5] Integrate email verification for email address changes
- [ ] T072 [US5] Add security logging for profile changes
- [ ] T073 [P] [US5] Create password visibility toggle controller in app/assets/controllers/password_controller.js

**Checkpoint**: All user stories should now be independently functional

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T074 [P] Add translation keys for all user-facing messages in translations/messages.en.yaml
- [ ] T075 [P] Add French translations in translations/messages.fr.yaml
- [ ] T076 [P] Create authentication event subscriber in app/src/EventSubscriber/AuthenticationSubscriber.php
- [ ] T077 [P] Add password strength validation constraint in app/src/Validator/PasswordStrength.php
- [ ] T078 [P] Create email uniqueness validator in app/src/Validator/UniqueEmail.php
- [ ] T079 Code cleanup and PSR-12 compliance verification across all files
- [ ] T080 [P] Add comprehensive error pages in templates/bundles/TwigBundle/Exception/
- [ ] T081 [P] Optimize database queries and add proper indexing
- [ ] T082 [P] Add security headers and CSRF protection verification
- [ ] T083 [P] Create console commands for user management in app/src/Command/
- [ ] T084 Run quickstart.md validation and documentation updates

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-7)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Phase 8)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1) - Registration**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1) - Login**: Can start after Foundational (Phase 2) - Integrates with US1 for user verification but independently testable
- **User Story 3 (P2) - Password Reset**: Can start after Foundational (Phase 2) - Uses User entity from US1 but independently testable
- **User Story 4 (P2) - Session Management**: Can start after Foundational (Phase 2) - Enhances US2 but independently testable
- **User Story 5 (P3) - Profile Management**: Can start after Foundational (Phase 2) - Uses components from US1 and US3 but independently testable

### Within Each User Story

- Form types can be created in parallel with services
- Controllers depend on forms and services being complete
- Templates can be created in parallel with controllers
- JavaScript controllers can be created in parallel with templates
- Routes and configuration depend on controllers
- Logging and validation are final integration steps

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- Within each story, all tasks marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1 (Registration)

```bash
# These can all start together after Foundational phase:
Task T016: "Create RegistrationFormType in app/src/Form/RegistrationFormType.php"
Task T017: "Create UserRegistrationService in app/src/Service/UserRegistrationService.php" 
Task T018: "Create EmailVerificationService in app/src/Service/EmailVerificationService.php"

# These can start together after forms and services:
Task T022: "Create registration form template in app/templates/security/register.html.twig"
Task T023: "Create email verification template in app/templates/registration/confirmation_email.html.twig"
Task T024: "Create registration form JavaScript controller in app/assets/controllers/registration_controller.js"
```

---

## Parallel Example: User Story 2 (Login)

```bash
# These can all start together after Foundational phase:
Task T028: "Create LoginFormType in app/src/Form/LoginFormType.php"
Task T029: "Create LoginFormAuthenticator in app/src/Security/LoginFormAuthenticator.php"
Task T030: "Create UserChecker for email verification check in app/src/Security/UserChecker.php"

# These can start together after forms and security components:
Task T034: "Create login form template in app/templates/security/login.html.twig"
Task T035: "Create login form JavaScript controller in app/assets/controllers/login_controller.js"
```

---

## Implementation Strategy

### MVP First (User Stories 1 & 2 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Registration)
4. Complete Phase 4: User Story 2 (Login)
5. **STOP and VALIDATE**: Test registration and login flow independently
6. Deploy/demo if ready - this provides a functional authentication system

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 (Registration) → Test independently → Basic signup works
3. Add User Story 2 (Login) → Test independently → Deploy/Demo (MVP!)
4. Add User Story 3 (Password Reset) → Test independently → Deploy/Demo
5. Add User Story 4 (Session Management) → Test independently → Deploy/Demo
6. Add User Story 5 (Profile Management) → Test independently → Deploy/Demo
7. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers after Foundational phase completes:

- **Developer A**: User Story 1 (Registration) - Tasks T016-T027
- **Developer B**: User Story 2 (Login) - Tasks T028-T040  
- **Developer C**: User Story 3 (Password Reset) - Tasks T041-T053
- **Developer D**: User Story 4 (Session Management) - Tasks T054-T061
- **Developer E**: User Story 5 (Profile Management) - Tasks T062-T073

Stories complete and integrate independently, then move to Polish phase together.

---

## Notes

- **[P] tasks**: Different files, no dependencies, can run in parallel
- **[Story] label**: Maps task to specific user story (US1, US2, US3, US4, US5) for traceability  
- **File paths**: All paths relative to app/ directory following Symfony 8 structure
- **Independent stories**: Each user story should be completable and testable on its own
- **MVP scope**: User Stories 1 & 2 provide a minimal viable authentication system
- **Security first**: All authentication events logged, passwords hashed, CSRF protected
- **Symfony conventions**: Following Controller → Service → Repository pattern throughout
- **No tests**: Tests not requested in feature specification, focus on implementation
- **Commit strategy**: Commit after each task or logical group for clean history