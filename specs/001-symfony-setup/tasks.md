# Tasks: Initial Project Infrastructure Setup

**Input**: Design documents from `/specs/001-symfony-setup/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Tests are NOT explicitly requested in the feature specification. This implementation focuses on infrastructure setup only.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

This is a Symfony web application with the following structure:
- Application root: `/app`
- Symfony source: `app/src/`
- Templates: `app/templates/`
- Assets: `app/assets/`
- Configuration: `app/config/`

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [X] T001 Create project structure per implementation plan in app/ directory
- [X] T002 Initialize Symfony 8 project with core dependencies in app/composer.json
- [X] T003 [P] Configure PHP CS Fixer in app/.php-cs-fixer.dist.php
- [X] T004 [P] Configure PHPStan in app/phpstan.neon
- [X] T005 [P] Setup basic .gitignore in app/.gitignore
- [X] T006 Initialize Node.js project with package.json in app/package.json

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T007 Configure Doctrine ORM with database connection in app/config/packages/doctrine.yaml
- [X] T008 [P] Setup Symfony Security bundle with role hierarchy in app/config/packages/security.yaml
- [X] T009 [P] Configure Translation bundle with locale settings in app/config/packages/translation.yaml
- [X] T010 [P] Setup Monolog logging configuration in app/config/packages/monolog.yaml
- [X] T011 [P] Configure framework bundle settings in app/config/packages/framework.yaml
- [X] T012 Setup environment variables in app/.env and app/.env.local
- [X] T013 Create Symfony Kernel configuration in app/src/Kernel.php
- [X] T014 Configure service container in app/config/services.yaml

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Fresh Project Foundation (Priority: P1) 🎯 MVP

**Goal**: Clean Symfony 8 installation with standard directory structure and dependencies configured

**Independent Test**: Run `php bin/console about` and verify Symfony 8 is installed with correct PHP version and environment configuration

### Implementation for User Story 1

- [X] T015 [P] [US1] Create standard Symfony directory structure in app/src/Controller/
- [X] T016 [P] [US1] Create standard Symfony directory structure in app/src/Entity/
- [X] T017 [P] [US1] Create standard Symfony directory structure in app/src/Repository/
- [X] T018 [P] [US1] Create standard Symfony directory structure in app/src/Service/
- [X] T019 [P] [US1] Create standard Symfony directory structure in app/src/Form/
- [X] T020 [P] [US1] Create standard Symfony directory structure in app/src/Security/
- [X] T021 [P] [US1] Create standard Symfony directory structure in app/src/EventSubscriber/
- [X] T022 [US1] Install core Symfony bundles via composer in app/composer.json
- [X] T023 [US1] Configure console commands availability via app/bin/console
- [X] T024 [US1] Setup migrations directory in app/migrations/
- [X] T025 [US1] Validate Symfony installation with console about command

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Twig Templating Ready (Priority: P1)

**Goal**: Twig templating engine fully configured with base layouts and component structure

**Independent Test**: Create a test controller with a Twig template and render "Hello World" through the browser

### Implementation for User Story 2

- [X] T026 [P] [US2] Configure Twig bundle in app/config/packages/twig.yaml
- [X] T027 [P] [US2] Create base layout template in app/templates/base.html.twig
- [X] T028 [P] [US2] Create components directory structure in app/templates/components/
- [X] T029 [P] [US2] Create pages directory structure in app/templates/pages/
- [X] T030 [US2] Configure Twig translation integration in app/templates/base.html.twig
- [X] T031 [US2] Create test controller for template validation in app/src/Controller/TestController.php
- [X] T032 [US2] Create test template with translation filter in app/templates/pages/test.html.twig
- [X] T033 [US2] Configure route for test controller in app/config/routes.yaml

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Webpack Encore Asset Pipeline (Priority: P1)

**Goal**: Webpack Encore configured to compile JavaScript and CSS assets

**Independent Test**: Run `npm run dev`, verify build output in `public/build/`, and load assets in a test page

### Implementation for User Story 3

- [X] T034 [P] [US3] Install Webpack Encore via npm in app/package.json
- [X] T035 [P] [US3] Configure Webpack Encore settings in app/webpack.config.js
- [X] T036 [P] [US3] Configure Encore bundle in app/config/packages/webpack_encore.yaml
- [X] T037 [P] [US3] Create main JavaScript entrypoint in app/assets/app.js
- [X] T038 [P] [US3] Create main CSS entrypoint in app/assets/styles/app.css
- [X] T039 [P] [US3] Setup Sass/SCSS compilation support in app/webpack.config.js
- [X] T040 [P] [US3] Configure Stimulus bridge in app/assets/bootstrap.js
- [X] T041 [P] [US3] Create Stimulus controllers directory in app/assets/controllers/
- [X] T042 [P] [US3] Create sample Stimulus controller in app/assets/controllers/hello_controller.js
- [X] T043 [US3] Configure controllers.json manifest in app/assets/controllers.json
- [X] T044 [US3] Update base.html.twig to load Encore entrypoints via encore_entry_link_tags() and encore_entry_script_tags()
- [X] T045 [US3] Add public/build/ to .gitignore in app/.gitignore
- [X] T046 [US3] Create build scripts in app/package.json (dev, watch, build)

**Checkpoint**: All P1 user stories should now be independently functional

---

## Phase 6: User Story 4 - Development Environment (Priority: P2)

**Goal**: Working local development environment with Symfony server and hot-reload capabilities

**Independent Test**: Start Symfony server, make code changes, and verify browser shows updates

### Implementation for User Story 4

- [ ] T047 [P] [US4] Configure Symfony server settings in app/.env
- [ ] T048 [P] [US4] Setup Twig debug mode in app/config/packages/twig.yaml
- [ ] T049 [P] [US4] Configure debug toolbar in app/config/packages/web_profiler.yaml
- [ ] T050 [P] [US4] Setup development routing in app/config/routes/dev/web_profiler.yaml
- [ ] T051 [US4] Configure asset watch mode in app/webpack.config.js
- [ ] T052 [US4] Update test controller to demonstrate hot-reload functionality in app/src/Controller/TestController.php
- [ ] T053 [US4] Create development documentation in specs/001-symfony-setup/quickstart.md

**Checkpoint**: Development environment should be fully functional

---

## Phase 7: User Story 5 - Security Foundation (Priority: P2)

**Goal**: Basic security bundle configured with authentication structure prepared

**Independent Test**: Verify security.yaml exists with role hierarchy defined and password hasher configured

### Implementation for User Story 5

- [ ] T054 [P] [US5] Configure role hierarchy (ROLE_USER, ROLE_ADMIN, ROLE_SUPER_ADMIN) in app/config/packages/security.yaml
- [ ] T055 [P] [US5] Configure password hasher service in app/config/packages/security.yaml
- [ ] T056 [P] [US5] Setup firewall configuration for public routes in app/config/packages/security.yaml
- [ ] T057 [P] [US5] Create Security directory structure in app/src/Security/
- [ ] T058 [US5] Configure access control patterns in app/config/packages/security.yaml
- [ ] T059 [US5] Validate password hasher service functionality

**Checkpoint**: Security foundation should be properly configured

---

## Phase 8: User Story 6 - Internationalization Setup (Priority: P3)

**Goal**: Translation system configured with English and French locale files

**Independent Test**: Add a translation key in messages.en.yaml and messages.fr.yaml, then use it in a template and verify it renders correctly based on locale

### Implementation for User Story 6

- [X] T060 [P] [US6] Create English translation file in app/translations/messages.en.yaml
- [X] T061 [P] [US6] Create French translation file in app/translations/messages.fr.yaml
- [X] T062 [P] [US6] Configure default locale in app/config/packages/translation.yaml
- [X] T063 [P] [US6] Add sample translation keys in both language files
- [X] T064 [US6] Update test template to demonstrate translation filter usage in app/templates/pages/test.html.twig
- [X] T065 [US6] Configure locale switching mechanism in test controller
- [X] T066 [US6] Test translation fallback for missing keys

**Checkpoint**: Internationalization should be fully functional

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [X] T067 [P] Update documentation in specs/001-symfony-setup/quickstart.md
- [X] T068 [P] Code cleanup and PSR-12 compliance check across all files
- [X] T069 [P] Validate all Symfony console commands work correctly
- [X] T070 [P] Ensure all configuration files follow Symfony conventions
- [X] T071 Run complete quickstart.md validation workflow
- [X] T072 Final integration test of all user stories working together

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-8)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P1 → P1 → P2 → P2 → P3)
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 3 (P1)**: Can start after Foundational (Phase 2) - Templates from US2 helpful but not blocking
- **User Story 4 (P2)**: Should complete after US1-US3 for best experience
- **User Story 5 (P2)**: Can start after Foundational (Phase 2) - Independent of other stories
- **User Story 6 (P3)**: Can start after Foundational (Phase 2) - Templates from US2 helpful but not blocking

### Within Each User Story

- Directory structure before implementation
- Configuration files before using services
- Core implementation before integration
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, all user stories can start in parallel (if team capacity allows)
- Models, configurations, and directory creation within a story marked [P] can run in parallel
- Different user stories can be worked on in parallel by different team members

---

## Parallel Example: User Story 1

```bash
# Launch all directory structure tasks for User Story 1 together:
Task: "Create standard Symfony directory structure in app/src/Controller/"
Task: "Create standard Symfony directory structure in app/src/Entity/"
Task: "Create standard Symfony directory structure in app/src/Repository/"
Task: "Create standard Symfony directory structure in app/src/Service/"
Task: "Create standard Symfony directory structure in app/src/Form/"
Task: "Create standard Symfony directory structure in app/src/Security/"
Task: "Create standard Symfony directory structure in app/src/EventSubscriber/"
```

## Parallel Example: User Story 3

```bash
# Launch all asset-related tasks for User Story 3 together:
Task: "Install Webpack Encore via npm in app/package.json"
Task: "Create main JavaScript entrypoint in app/assets/app.js"
Task: "Create main CSS entrypoint in app/assets/styles/app.css"
Task: "Create Stimulus controllers directory in app/assets/controllers/"
Task: "Create sample Stimulus controller in app/assets/controllers/hello_controller.js"
```

---

## Implementation Strategy

### MVP First (User Stories 1-3 Only - All P1)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Fresh Project Foundation)
4. Complete Phase 4: User Story 2 (Twig Templating)
5. Complete Phase 5: User Story 3 (Webpack Encore)
6. **STOP and VALIDATE**: Test all P1 stories independently
7. Deploy/demo basic Symfony application with Twig and Encore

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Test independently → Basic Symfony working
3. Add User Story 2 → Test independently → Templating working
4. Add User Story 3 → Test independently → Asset pipeline working (MVP!)
5. Add User Story 4 → Development environment enhanced
6. Add User Story 5 → Security foundation ready
7. Add User Story 6 → Internationalization ready
8. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 + User Story 4
   - Developer B: User Story 2 + User Story 6
   - Developer C: User Story 3 + User Story 5
3. Stories complete and integrate independently

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Focus on P1 stories (1-3) for minimum viable infrastructure
- P2 and P3 stories enhance developer experience but aren't critical for basic functionality
