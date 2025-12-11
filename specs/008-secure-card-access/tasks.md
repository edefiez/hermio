---
description: "Task list for secure public card access enhancement"
---

# Tasks: Renforcer la sécurité d'accès aux cartes publiques

**Feature**: Add secure, non-guessable access key to public card URLs  
**Current State**: Public cards accessible via `/c/<slug>` with guessable slugs  
**Target State**: Public cards require secure key via `/c/<slug>?k=<secure_key>`  
**Project**: Symfony 8 (Hermio) - PHP 8.4+

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `/home/runner/work/hermio/hermio/app/src/`
- **Templates**: `/home/runner/work/hermio/hermio/app/templates/`
- **Migrations**: `/home/runner/work/hermio/hermio/app/migrations/`
- **Tests**: `/home/runner/work/hermio/hermio/app/tests/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Create foundational service and utilities for secure key generation

- [ ] T001 Create SecureKeyGenerator service in /home/runner/work/hermio/hermio/app/src/Service/SecureKeyGenerator.php with generateRandomKey() method using random_bytes() and base64url encoding
- [ ] T002 [P] Add unit tests for SecureKeyGenerator in /home/runner/work/hermio/hermio/app/tests/Unit/Service/SecureKeyGeneratorTest.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema changes that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T003 Add publicAccessKey property (string, length 128, nullable initially) to Card entity in /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T004 Add getter and setter methods for publicAccessKey in Card entity /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T005 Create Doctrine migration for adding public_access_key column to cards table in /home/runner/work/hermio/hermio/app/migrations/
- [ ] T006 Run migration to apply database schema changes
- [ ] T007 Add index on publicAccessKey field in Card entity if needed for performance in /home/runner/work/hermio/hermio/app/src/Entity/Card.php

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Automatic Key Generation for New Cards (Priority: P1) 🎯 MVP

**Goal**: All newly created cards automatically receive a secure, non-guessable access key

**Independent Test**: Create a new card via the web interface or CLI. Verify that the card has a publicAccessKey field populated with a 48-character random string. Verify the public URL includes the key parameter.

### Implementation for User Story 1

- [ ] T008 [US1] Add PrePersist lifecycle callback in Card entity to auto-generate publicAccessKey if not set in /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T009 [US1] Inject SecureKeyGenerator service into Card entity via EntityListener or update CardService to generate key in /home/runner/work/hermio/hermio/app/src/Service/CardService.php
- [ ] T010 [US1] Update Card::getPublicUrl() method to include ?k=<publicAccessKey> query parameter in /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T011 [US1] Update CardService::createCard() to ensure publicAccessKey is generated before persisting in /home/runner/work/hermio/hermio/app/src/Service/CardService.php
- [ ] T012 [P] [US1] Add unit tests for Card entity key generation lifecycle in /home/runner/work/hermio/hermio/app/tests/Unit/Entity/CardTest.php
- [ ] T013 [P] [US1] Add functional test for card creation with automatic key generation in /home/runner/work/hermio/hermio/app/tests/Functional/Service/CardServiceTest.php

**Checkpoint**: At this point, all new cards are created with secure access keys

---

## Phase 4: User Story 2 - Validate Access Key on Public Card View (Priority: P1) 🎯 MVP

**Goal**: Public card pages require valid access key to display, returning 403 for invalid/missing keys

**Independent Test**: Access `/c/<slug>` without key parameter → expect 403. Access `/c/<slug>?k=<valid_key>` → expect card displayed. Access `/c/<slug>?k=<invalid_key>` → expect 403.

### Implementation for User Story 2

- [ ] T014 [US2] Update PublicCardController::show() to extract key from query parameter ?k in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T015 [US2] Add key validation logic in PublicCardController::show() to compare provided key with card's publicAccessKey in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T016 [US2] Return 403 Forbidden response when key is missing or invalid in PublicCardController::show() in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T017 [P] [US2] Create error template for invalid access key at /home/runner/work/hermio/hermio/app/templates/error/403_invalid_key.html.twig
- [ ] T018 [P] [US2] Add functional test for public card access with valid key in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/PublicCardControllerTest.php
- [ ] T019 [P] [US2] Add functional test for public card access without key returning 403 in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/PublicCardControllerTest.php
- [ ] T020 [P] [US2] Add functional test for public card access with invalid key returning 403 in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/PublicCardControllerTest.php

**Checkpoint**: Public cards now require valid keys to be accessed

---

## Phase 5: User Story 3 - Display Secure URL in Card Management UI (Priority: P1) 🎯 MVP

**Goal**: Card owners can see and copy the full secure public URL with access key in the card edit interface

**Independent Test**: Navigate to card edit page. Verify full URL with ?k=<key> is displayed prominently. Verify QR code (if shown) encodes the full URL with key.

### Implementation for User Story 3

- [ ] T021 [US3] Update card edit template to display full public URL with key in /home/runner/work/hermio/hermio/app/templates/card/edit.html.twig
- [ ] T022 [US3] Add security warning message about sharing URLs in card edit template in /home/runner/work/hermio/hermio/app/templates/card/edit.html.twig
- [ ] T023 [US3] Add copy-to-clipboard functionality for secure URL in card edit template in /home/runner/work/hermio/hermio/app/templates/card/edit.html.twig
- [ ] T024 [P] [US3] Update QR code generation to include access key in URL in /home/runner/work/hermio/hermio/app/templates/card/qr_code.html.twig (if exists) or relevant QR code service
- [ ] T025 [P] [US3] Add functional test verifying edit page displays secure URL in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/CardControllerTest.php

**Checkpoint**: Users can view and share their secure card URLs

---

## Phase 6: User Story 4 - Manual Key Regeneration (Priority: P2)

**Goal**: Card owners can regenerate their card's access key if compromised, invalidating old URLs

**Independent Test**: Click "Regenerate Security Key" button on card edit page. Confirm action. Verify new key is generated, old URL no longer works, new URL works correctly.

### Implementation for User Story 4

- [ ] T026 [US4] Add regenerateAccessKey() method to Card entity in /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T027 [US4] Add CardService::regenerateCardAccessKey(Card $card) method in /home/runner/work/hermio/hermio/app/src/Service/CardService.php
- [ ] T028 [US4] Create CardController::regenerateKey() action (POST) with CSRF protection in /home/runner/work/hermio/hermio/app/src/Controller/CardController.php
- [ ] T029 [US4] Add route for regenerate key action in /home/runner/work/hermio/hermio/app/config/routes.yaml or via Route attribute
- [ ] T030 [US4] Add "Regenerate Security Key" button with confirmation modal to card edit template in /home/runner/work/hermio/hermio/app/templates/card/edit.html.twig
- [ ] T031 [P] [US4] Add functional test for key regeneration via controller in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/CardControllerTest.php
- [ ] T032 [P] [US4] Add functional test verifying old key becomes invalid after regeneration in /home/runner/work/hermio/hermio/app/tests/Functional/Controller/PublicCardControllerTest.php

**Checkpoint**: Users can regenerate compromised access keys

---

## Phase 7: User Story 5 - CLI Command for Key Management (Priority: P2)

**Goal**: Administrators can regenerate keys via CLI for individual cards or all cards (migration support)

**Independent Test**: Run `php bin/console app:card:regenerate-key <card-id>` and verify key changes. Run `php bin/console app:card:migrate-keys` to add keys to cards missing them.

### Implementation for User Story 5

- [ ] T033 [P] [US5] Create RegenerateCardKeyCommand in /home/runner/work/hermio/hermio/app/src/Command/RegenerateCardKeyCommand.php
- [ ] T034 [US5] Implement command logic to regenerate key for single card by ID in RegenerateCardKeyCommand
- [ ] T035 [US5] Add --all option to regenerate keys for all cards with confirmation prompt in RegenerateCardKeyCommand
- [ ] T036 [P] [US5] Create MigrateCardKeysCommand in /home/runner/work/hermio/hermio/app/src/Command/MigrateCardKeysCommand.php for adding keys to existing cards
- [ ] T037 [US5] Implement migration logic to add keys only to cards missing publicAccessKey in MigrateCardKeysCommand
- [ ] T038 [P] [US5] Add unit tests for RegenerateCardKeyCommand in /home/runner/work/hermio/hermio/app/tests/Unit/Command/RegenerateCardKeyCommandTest.php
- [ ] T039 [P] [US5] Add unit tests for MigrateCardKeysCommand in /home/runner/work/hermio/hermio/app/tests/Unit/Command/MigrateCardKeysCommandTest.php

**Checkpoint**: Administrators have CLI tools for key management and migration

---

## Phase 8: User Story 6 - Service Layer Validation (Priority: P2)

**Goal**: CardService provides clean API for key validation used by controllers

**Independent Test**: Create unit tests that verify validateAccessKey() method correctly validates keys

### Implementation for User Story 6

- [ ] T040 [US6] Add CardService::validateAccessKey(Card $card, ?string $key): bool method in /home/runner/work/hermio/hermio/app/src/Service/CardService.php
- [ ] T041 [US6] Implement constant-time comparison for key validation to prevent timing attacks in CardService::validateAccessKey()
- [ ] T042 [US6] Update PublicCardController to use CardService::validateAccessKey() instead of direct comparison in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T043 [P] [US6] Add unit tests for CardService::validateAccessKey() in /home/runner/work/hermio/hermio/app/tests/Unit/Service/CardServiceTest.php

**Checkpoint**: Service layer provides secure validation abstraction

---

## Phase 9: User Story 7 - Backward Compatibility & Migration (Priority: P3)

**Goal**: Existing cards without keys get migrated gracefully, users are notified

**Independent Test**: Query database for cards with NULL publicAccessKey. Run migration command. Verify all cards now have keys. Verify existing public URLs (without keys) show migration notice.

### Implementation for User Story 7

- [ ] T044 [US7] Add database query to identify cards with NULL publicAccessKey in CardRepository in /home/runner/work/hermio/hermio/app/src/Repository/CardRepository.php
- [ ] T045 [US7] Create migration script or Symfony command to populate keys for existing cards in /home/runner/work/hermio/hermio/app/src/Command/MigrateExistingCardsCommand.php
- [ ] T046 [US7] Add temporary backward compatibility: allow access without key but show deprecation warning banner in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T047 [P] [US7] Create migration notice template for cards accessed without keys in /home/runner/work/hermio/hermio/app/templates/public/migration_notice.html.twig
- [ ] T048 [US7] Update migration documentation in specs/008-secure-card-access/README.md or migration guide

**Checkpoint**: Existing cards migrated, users informed of URL changes

---

## Phase 10: User Story 8 - Security Enhancements (Priority: P3) [OPTIONAL]

**Goal**: Advanced security features including rate limiting and access logging

**Independent Test**: Attempt 10 failed access attempts in quick succession, verify rate limiting blocks further attempts. Check logs for access attempt records.

### Implementation for User Story 8

- [ ] T049 [P] [US8] Add rate limiting for invalid key attempts using Symfony RateLimiter in /home/runner/work/hermio/hermio/app/src/EventSubscriber/CardAccessRateLimitSubscriber.php
- [ ] T050 [P] [US8] Add logging for card access attempts (success and failure) in PublicCardController in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T051 [P] [US8] Add optional key hashing in database (bcrypt or sodium) instead of plaintext storage in Card entity in /home/runner/work/hermio/hermio/app/src/Entity/Card.php
- [ ] T052 [P] [US8] Add functional tests for rate limiting behavior in /home/runner/work/hermio/hermio/app/tests/Functional/Security/CardAccessRateLimitTest.php

**Checkpoint**: Advanced security measures in place

---

## Phase 11: User Story 9 - Alternative Implementation: HMAC-Derived Keys (Priority: P3) [OPTIONAL]

**Goal**: Document and optionally implement HMAC-based key derivation as alternative to stored keys

**Independent Test**: Generate HMAC key using HMAC(APP_SECRET, card.id + user.id + createdAt). Verify it's deterministic and validates correctly.

### Implementation for User Story 9

- [ ] T053 [P] [US9] Add SecureKeyGenerator::generateDerivedKey(Card $card, string $appSecret): string method in /home/runner/work/hermio/hermio/app/src/Service/SecureKeyGenerator.php
- [ ] T054 [P] [US9] Document HMAC approach in specs/008-secure-card-access/research.md with pros/cons vs stored keys
- [ ] T055 [P] [US9] Add configuration option to choose between stored and derived keys in /home/runner/work/hermio/hermio/app/config/services.yaml
- [ ] T056 [P] [US9] Add unit tests for HMAC key generation in /home/runner/work/hermio/hermio/app/tests/Unit/Service/SecureKeyGeneratorTest.php

**Checkpoint**: Alternative key strategy documented and optionally implemented

---

## Phase 12: User Story 10 - Expiring Signed URLs (Priority: P3) [OPTIONAL]

**Goal**: Support time-limited sharing via Symfony SignedUrl component

**Independent Test**: Generate signed URL with 24-hour expiration. Verify URL works within time limit. Verify URL fails after expiration.

### Implementation for User Story 10

- [ ] T057 [P] [US10] Add SignedUrlService using Symfony UriSigner component in /home/runner/work/hermio/hermio/app/src/Service/SignedUrlService.php
- [ ] T058 [US10] Add CardService::generateSignedUrl(Card $card, int $expiresInSeconds): string method in /home/runner/work/hermio/hermio/app/src/Service/CardService.php
- [ ] T059 [US10] Update PublicCardController to validate signed URLs with expiration in /home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php
- [ ] T060 [US10] Add UI option to generate temporary share links in card edit template in /home/runner/work/hermio/hermio/app/templates/card/edit.html.twig
- [ ] T061 [P] [US10] Add functional tests for signed URL generation and expiration in /home/runner/work/hermio/hermio/app/tests/Functional/Service/SignedUrlServiceTest.php

**Checkpoint**: Time-limited sharing capability available

---

## Phase 13: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T062 [P] Update main README.md with public URL structure documentation in /home/runner/work/hermio/hermio/README.md
- [ ] T063 [P] Create migration guide for users with existing cards in /home/runner/work/hermio/hermio/specs/008-secure-card-access/MIGRATION.md
- [ ] T064 [P] Document security best practices for sharing URLs in /home/runner/work/hermio/hermio/specs/008-secure-card-access/SECURITY.md
- [ ] T065 [P] Add French translations for new messages in /home/runner/work/hermio/hermio/app/translations/messages.fr.yaml
- [ ] T066 [P] Add English translations for new messages in /home/runner/work/hermio/hermio/app/translations/messages.en.yaml
- [ ] T067 Code review and refactoring for PSR-12 compliance
- [ ] T068 Performance testing for key validation on high-traffic cards
- [ ] T069 Update API documentation if card endpoints exist
- [ ] T070 Security audit of key generation and validation implementation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-12)**: All depend on Foundational phase completion
  - User Stories 1-3 (P1) form the MVP and should be done first
  - User Stories 4-6 (P2) add management capabilities
  - User Stories 7-10 (P3) are enhancements and can be done later or omitted
- **Polish (Phase 13)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P1)**: Can start after User Story 1 - Requires keys to exist for validation
- **User Story 3 (P1)**: Can start after User Story 1 - Requires keys to exist for display
- **User Story 4 (P2)**: Can start after User Stories 1-3 - Requires full key system in place
- **User Story 5 (P2)**: Can start after User Story 4 - Uses regeneration logic
- **User Story 6 (P2)**: Can start after User Story 2 - Refactors validation logic
- **User Story 7 (P3)**: Can start after User Stories 1-3 - Migration support
- **User Story 8 (P3)**: Can start after User Story 2 - Security enhancements
- **User Story 9 (P3)**: Can start after User Story 1 - Alternative implementation
- **User Story 10 (P3)**: Can start after User Stories 1-2 - Enhanced sharing

### Within Each User Story

- Database changes (Phase 2) before any implementation
- Service layer before controllers
- Controllers before templates
- Implementation before tests (or TDD: tests before implementation)
- Core functionality before optional enhancements

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks (Phase 2) must run sequentially due to database dependencies
- Within User Story 1: T012 and T013 (tests) can run in parallel with implementation
- Within User Story 2: T017, T018, T019, T020 (template + tests) can run in parallel after controller changes
- Within User Story 3: T024 and T025 can run in parallel with template changes
- Within User Story 4: T031 and T032 (tests) can run in parallel after implementation
- All User Story 5 tasks marked [P] can run in parallel (separate commands)
- User Story 8 tasks (all marked [P]) can run in parallel
- User Story 9 tasks (all marked [P]) can run in parallel
- User Story 10 tasks (mostly marked [P]) can run in parallel
- All Polish tasks (Phase 13) marked [P] can run in parallel

---

## Parallel Example: User Story 2 (Access Validation)

```bash
# After controller implementation (T014-T016), launch in parallel:
Task: "Create error template for invalid access key at templates/error/403_invalid_key.html.twig"
Task: "Add functional test for public card access with valid key"
Task: "Add functional test for public card access without key returning 403"
Task: "Add functional test for public card access with invalid key returning 403"
```

---

## Implementation Strategy

### MVP First (User Stories 1-3 Only)

1. Complete Phase 1: Setup (SecureKeyGenerator service)
2. Complete Phase 2: Foundational (Database changes)
3. Complete Phase 3: User Story 1 (Auto-generate keys for new cards)
4. Complete Phase 4: User Story 2 (Validate keys on public access)
5. Complete Phase 5: User Story 3 (Display secure URLs in UI)
6. **STOP and VALIDATE**: Test end-to-end flow:
   - Create new card → verify key generated
   - Access public URL with key → verify card displays
   - Access public URL without key → verify 403 error
   - View edit page → verify secure URL displayed
7. Deploy/demo if ready

### Incremental Delivery

1. **MVP** (Phases 1-5): Basic secure access → Deploy
2. **Management** (Phases 6-7): Key regeneration + CLI tools → Deploy
3. **Hardening** (Phase 8): Security enhancements → Deploy
4. **Alternatives** (Phases 9-10): Optional implementations → Deploy if needed
5. **Polish** (Phase 13): Documentation and translations → Deploy

### Recommended MVP Scope

**Minimum Viable Product should include:**
- Phase 1: Setup (T001-T002)
- Phase 2: Foundational (T003-T007)
- Phase 3: User Story 1 (T008-T013)
- Phase 4: User Story 2 (T014-T020)
- Phase 5: User Story 3 (T021-T025)
- Phase 6: User Story 4 (T026-T032) - Key regeneration is important for security
- Phase 7: User Story 5 (T033-T039) - Migration support critical for existing cards

**Total MVP Tasks**: ~45 tasks (including tests)

**Can be deferred:**
- Phase 8: Security enhancements (nice to have)
- Phase 9: HMAC alternative (advanced)
- Phase 10: Signed URLs (advanced feature)
- Phase 13: Some polish tasks (can be done iteratively)

---

## Task Complexity Estimates

### Simple Tasks (< 30 minutes)
- T001, T002, T003, T004, T007, T017, T021, T022, T024, T047, T062-T066

### Medium Tasks (30 minutes - 2 hours)
- T005, T006, T008, T009, T010, T011, T014, T015, T016, T023, T026, T027, T028, T029, T030, T033-T039, T040-T043, T044, T045, T048, T049, T050, T053, T054, T057, T058, T067-T070

### Complex Tasks (2+ hours)
- T012, T013, T018-T020, T025, T031, T032, T046, T051, T052, T055, T056, T059-T061

---

## Acceptance Criteria Summary

### User Story 1 - Auto-generate Keys
- ✅ New cards have publicAccessKey populated automatically
- ✅ Keys are 48 characters long (base64url encoded)
- ✅ Keys use cryptographically secure random_bytes()
- ✅ Card::getPublicUrl() returns URL with ?k= parameter

### User Story 2 - Validate Keys
- ✅ PublicCardController requires valid key to display card
- ✅ Missing key returns 403 Forbidden
- ✅ Invalid key returns 403 Forbidden
- ✅ Valid key displays card correctly
- ✅ Custom error template shown for invalid access

### User Story 3 - Display Secure URLs
- ✅ Edit page shows full URL with key prominently
- ✅ Copy-to-clipboard functionality works
- ✅ Security warning about sharing is visible
- ✅ QR codes include the key in the URL

### User Story 4 - Regenerate Keys
- ✅ "Regenerate Key" button exists with confirmation
- ✅ Regeneration creates new key
- ✅ Old URLs stop working after regeneration
- ✅ New URLs work immediately

### User Story 5 - CLI Commands
- ✅ php bin/console app:card:regenerate-key <id> works
- ✅ --all flag regenerates all keys with confirmation
- ✅ Migration command adds keys to existing cards
- ✅ Commands provide clear output and error messages

### User Story 6 - Service Validation
- ✅ CardService::validateAccessKey() exists
- ✅ Uses constant-time comparison
- ✅ Controller delegates to service
- ✅ Service method is well-tested

### User Story 7 - Migration
- ✅ Existing cards identified and migrated
- ✅ Migration command is idempotent
- ✅ Migration documentation exists
- ✅ Users notified of URL changes

---

## Testing Strategy

### Unit Tests
- SecureKeyGenerator service
- Card entity lifecycle callbacks
- CardService methods
- CLI commands

### Functional Tests
- Card creation with key generation
- Public card access with valid/invalid/missing keys
- Key regeneration via controller
- Edit page display of secure URLs

### Integration Tests
- End-to-end flow: create card → access public URL
- Migration command on test database
- Rate limiting behavior

### Manual Testing
- QR code scanning with mobile devices
- Copy-to-clipboard functionality
- Browser compatibility for error pages
- Accessibility of error messages

---

## Security Considerations

1. **Key Generation**: Use `random_bytes()` for cryptographically secure randomness
2. **Key Comparison**: Use constant-time comparison to prevent timing attacks
3. **Key Storage**: Consider hashing keys in database (optional enhancement)
4. **Rate Limiting**: Prevent brute-force key guessing attacks
5. **HTTPS**: Document requirement for HTTPS in production
6. **Key Length**: 48 characters provides sufficient entropy (288 bits)
7. **No Key Reuse**: Each regeneration creates completely new key
8. **Access Logging**: Log attempts for security monitoring

---

## Migration Path for Existing Deployments

1. **Deploy Phase 2** (database changes) - nullable column allows gradual migration
2. **Deploy Phases 1, 3-5** (key generation + validation) - new cards protected
3. **Run migration command** (Phase 7) - add keys to existing cards
4. **Notify users** - send email about URL changes
5. **Monitor logs** - watch for access without keys
6. **Phase out backward compatibility** - after grace period, require keys for all cards

---

## Notes

- [P] tasks = different files, no dependencies - can run in parallel
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Tests are required for this security feature to ensure correctness
- MVP can be delivered with just Phases 1-7 (skip optional enhancements)
- Use Symfony best practices: services, dependency injection, PSR-12
- Follow existing project conventions from Card entity and CardService
