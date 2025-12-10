# Tasks: Stripe Billing Integration

**Input**: Design documents from `/specs/004-stripe-billing/`
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

**Purpose**: Install Stripe SDK and configure environment

- [x] T001 Install Stripe PHP SDK via Composer: `docker-compose exec app composer require stripe/stripe-php`
- [x] T002 [P] Add Stripe environment variables to `.env` and `.env.local`: `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_ID_PRO`, `STRIPE_PRICE_ID_ENTERPRISE`
- [x] T003 [P] Verify Feature 003 (Account Management) is complete - Account entity and PlanType enum must exist
- [x] T004 [P] Verify User entity exists in `app/src/Entity/User.php` with authentication working

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 [P] Create StripeCustomer entity in `app/src/Entity/StripeCustomer.php` with properties: id, user (OneToOne), stripeCustomerId, createdAt, updatedAt, and lifecycle callbacks
- [x] T006 [P] Create Payment entity in `app/src/Entity/Payment.php` with properties: id, user (ManyToOne), stripePaymentIntentId, status, amount, currency, planType, paidAt, createdAt, stripeEventData
- [x] T007 [P] Create Subscription entity in `app/src/Entity/Subscription.php` with properties: id, user (OneToOne), stripeSubscriptionId, planType, status, currentPeriodStart, currentPeriodEnd, canceledAt, cancelAtPeriodEnd, createdAt, updatedAt
- [x] T008 [P] Create ProcessedWebhookEvent entity in `app/src/Entity/ProcessedWebhookEvent.php` with properties: id, stripeEventId, eventType, processedAt, success, errorMessage (optional but recommended for idempotency)
- [x] T009 [P] Modify User entity in `app/src/Entity/User.php` to add relationships: OneToOne to StripeCustomer (mappedBy: 'user'), OneToOne to Subscription (mappedBy: 'user'), OneToMany to Payment (mappedBy: 'user')
- [x] T010 Create StripeCustomerRepository in `app/src/Repository/StripeCustomerRepository.php` extending ServiceEntityRepository
- [x] T011 Create PaymentRepository in `app/src/Repository/PaymentRepository.php` extending ServiceEntityRepository
- [x] T012 Create SubscriptionRepository in `app/src/Repository/SubscriptionRepository.php` extending ServiceEntityRepository
- [x] T013 Create ProcessedWebhookEventRepository in `app/src/Repository/ProcessedWebhookEventRepository.php` extending ServiceEntityRepository (optional but recommended)
- [x] T014 Create database migration for billing entities in `app/migrations/Version[timestamp].php` with tables: stripe_customers, payments, subscriptions, processed_webhook_events
- [x] T015 Run migration: `docker-compose exec app php bin/console doctrine:migrations:migrate`
- [x] T016 Create StripeService in `app/src/Service/StripeService.php` with constructor injection of STRIPE_SECRET_KEY, methods: createCheckoutSession(), createCustomerPortalSession(), retrieveCustomer(), retrieveSubscription()
- [x] T017 Create StripeCheckoutService in `app/src/Service/StripeCheckoutService.php` with constructor injection of StripeService, StripeCustomerRepository, EntityManagerInterface, successUrl, cancelUrl, methods: createCheckoutSession(), getOrCreateStripeCustomer(), getPriceIdForPlan()
- [x] T018 Create StripeWebhookService in `app/src/Service/StripeWebhookService.php` with constructor injection of ProcessedWebhookEventRepository, SubscriptionRepository, PaymentRepository, UserRepository, AccountService, EntityManagerInterface, methods: processEvent(), handleSubscriptionEvent(), handleSubscriptionDeleted(), handlePaymentSucceeded(), handlePaymentFailed(), isEventProcessed(), markEventProcessed()
- [x] T019 Create SubscriptionService in `app/src/Service/SubscriptionService.php` with constructor injection of SubscriptionRepository, AccountService, StripeService, methods: syncSubscriptionFromStripe(), updateAccountFromSubscription(), downgradeAccountToFree()

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Upgrade Subscription Plan via Stripe Checkout (Priority: P1) 🎯 MVP

**Goal**: Users can upgrade their subscription plan by completing payment through Stripe Checkout

**Independent Test**: Initiate upgrade from Free to Pro plan, complete Stripe Checkout payment flow, and verify that user's plan is automatically upgraded and quota limits are updated immediately after successful payment

### Implementation for User Story 1

- [x] T020 [P] [US1] Create StripeCheckoutController in `app/src/Controller/StripeCheckoutController.php` with createCheckoutSession() method handling POST /subscription/checkout/create, validating planType parameter, calling StripeCheckoutService, and redirecting to Stripe Checkout URL
- [x] T021 [US1] Add route configuration for POST /subscription/checkout/create in StripeCheckoutController with route name `app_subscription_checkout_create` and ROLE_USER authorization
- [x] T022 [US1] Implement StripeCheckoutController::success() method handling GET /subscription/checkout/success callback, retrieving session_id from query params, verifying payment success, and displaying success message
- [x] T023 [US1] Add route configuration for GET /subscription/checkout/success in StripeCheckoutController with route name `app_subscription_checkout_success` and ROLE_USER authorization
- [x] T024 [US1] Implement StripeCheckoutController::cancel() method handling GET /subscription/checkout/cancel callback and displaying cancellation message
- [x] T025 [US1] Add route configuration for GET /subscription/checkout/cancel in StripeCheckoutController with route name `app_subscription_checkout_cancel` and ROLE_USER authorization
- [x] T026 [P] [US1] Update my_plan.html.twig template in `app/templates/account/my_plan.html.twig` to add "Upgrade to Pro" and "Upgrade to Enterprise" buttons with pricing display
- [x] T027 [US1] Add translation keys for checkout in `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml` (subscription.upgrade.pro, subscription.upgrade.enterprise, subscription.checkout.success, subscription.checkout.cancel, subscription.pricing.pro, subscription.pricing.enterprise)
- [x] T028 [US1] Implement StripeCheckoutService::getOrCreateStripeCustomer() to create Stripe customer via API if not exists, create StripeCustomer entity, and return entity
- [x] T029 [US1] Implement StripeCheckoutService::createCheckoutSession() to create Stripe Checkout session with subscription mode, correct price ID, success/cancel URLs, and user metadata
- [x] T030 [US1] Add error handling in StripeCheckoutController for invalid plan types, missing Stripe customer, and API errors

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. Users can initiate upgrades via Stripe Checkout, but plan won't update until webhooks are implemented (US2).

---

## Phase 4: User Story 2 - Automatic Plan Updates via Stripe Webhooks (Priority: P1)

**Goal**: Application automatically processes Stripe webhook events to synchronize subscription status with Stripe

**Independent Test**: Simulate Stripe webhook events (payment success, subscription created, subscription cancelled) and verify that user plans are updated correctly in application database

### Implementation for User Story 2

- [x] T031 [P] [US2] Create StripeWebhookController in `app/src/Controller/StripeWebhookController.php` with handleWebhook() method handling POST /stripe/webhook, extracting raw request body, validating webhook signature, and delegating to StripeWebhookService
- [x] T032 [US2] Add route configuration for POST /stripe/webhook in StripeWebhookController with route name `app_stripe_webhook` (no authentication, signature validation only)
- [x] T033 [US2] Implement webhook signature validation in StripeWebhookController using Stripe webhook secret and Stripe-Signature header, rejecting invalid signatures with 400 response
- [x] T034 [US2] Implement StripeWebhookService::processEvent() to check idempotency via ProcessedWebhookEventRepository, route events to appropriate handlers, and mark events as processed
- [x] T035 [US2] Implement StripeWebhookService::handleSubscriptionEvent() to process customer.subscription.created and customer.subscription.updated events, create/update Subscription entity, determine planType from subscription items, and sync Account.planType via AccountService
- [x] T036 [US2] Implement StripeWebhookService::handleSubscriptionDeleted() to process customer.subscription.deleted events, remove Subscription entity, and downgrade Account to FREE via AccountService
- [x] T037 [US2] Implement StripeWebhookService::handlePaymentSucceeded() to process payment_intent.succeeded events and create Payment entity record
- [x] T038 [US2] Implement StripeWebhookService::handlePaymentFailed() to process payment_intent.payment_failed events, log failure, and optionally notify user (without downgrading plan)
- [x] T039 [US2] Implement StripeWebhookService::isEventProcessed() to check ProcessedWebhookEventRepository for existing event ID
- [x] T040 [US2] Implement StripeWebhookService::markEventProcessed() to create ProcessedWebhookEvent entity with success status and error message if failed
- [x] T041 [US2] Add error handling in StripeWebhookService for missing users, invalid event data, and processing exceptions (log errors but return 200 to prevent Stripe retries)
- [x] T042 [US2] Add logging for all webhook processing operations in StripeWebhookService for audit and debugging
- [x] T043 [US2] Configure Stripe webhook endpoint in Stripe Dashboard: add endpoint URL, select events (payment_intent.succeeded, payment_intent.payment_failed, customer.subscription.created, customer.subscription.updated, customer.subscription.deleted), and copy webhook secret to STRIPE_WEBHOOK_SECRET

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently. Users can upgrade via Checkout and plans are automatically updated via webhooks.

---

## Phase 5: User Story 3 - Manage Active Subscription (Priority: P2)

**Goal**: Users can view and manage their active Stripe subscription, including cancelling or changing plans

**Independent Test**: Access subscription management page, view current subscription details, and successfully cancel or modify subscription through Stripe Customer Portal

### Implementation for User Story 3

- [x] T044 [P] [US3] Create SubscriptionController in `app/src/Controller/SubscriptionController.php` with manage() method displaying subscription details, next billing date, and management options
- [x] T045 [US3] Add route configuration for GET /subscription/manage in SubscriptionController with route name `app_subscription_manage` and ROLE_USER authorization
- [x] T046 [US3] Implement SubscriptionController::createPortalSession() method handling POST /subscription/portal, creating Stripe Customer Portal session via StripeService, and redirecting to portal URL
- [x] T047 [US3] Add route configuration for POST /subscription/portal in SubscriptionController with route name `app_subscription_portal` and ROLE_USER authorization
- [x] T048 [P] [US3] Create manage.html.twig template in `app/templates/subscription/manage.html.twig` displaying subscription details (plan type, status, billing dates, payment method), cancel button, and link to Customer Portal
- [x] T049 [US3] Add translation keys for subscription management in `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml` (subscription.manage.title, subscription.status.active, subscription.status.cancelled, subscription.next_billing, subscription.cancel, subscription.portal)
- [x] T050 [US3] Implement SubscriptionService::syncSubscriptionFromStripe() to retrieve subscription from Stripe API and update Subscription entity
- [x] T051 [US3] Add error handling in SubscriptionController for users without active subscriptions (Free plan users) and missing Stripe customers
- [x] T052 [US3] Add logic to handle subscription cancellation via Customer Portal - webhook will handle Account downgrade, but UI should show cancellation status

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently. Users can manage their subscriptions through the application and Customer Portal.

---

## Phase 6: User Story 4 - View Payment History (Priority: P3)

**Goal**: Users can view their payment history and invoices for accounting or reimbursement purposes

**Independent Test**: Access payment history page and verify that all past payments, invoices, and subscription changes are displayed accurately

### Implementation for User Story 4

- [x] T053 [P] [US4] Implement SubscriptionController::paymentHistory() method in `app/src/Controller/SubscriptionController.php` retrieving user's Payment entities ordered by paidAt DESC
- [x] T054 [US4] Add route configuration for GET /subscription/payments in SubscriptionController with route name `app_subscription_payments` and ROLE_USER authorization
- [x] T055 [P] [US4] Create history.html.twig template in `app/templates/subscription/history.html.twig` displaying list of payments with dates, amounts, status, plan changes, and links to invoices
- [x] T056 [US4] Add translation keys for payment history in `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml` (subscription.payments.title, subscription.payment.date, subscription.payment.amount, subscription.payment.status, subscription.payment.invoice)
- [x] T057 [US4] Implement PaymentRepository::findByUserOrderedByDate() method for efficient payment history queries
- [x] T058 [US4] Add logic to display plan changes associated with each payment (upgrade/downgrade indicators)
- [x] T059 [US4] Add empty state handling in payment history template for users with no payment history

**Checkpoint**: At this point, all user stories should be independently functional. Users can view their complete payment history.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T060 [P] Complete all translation keys for Stripe billing feature in `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml` (verify all user-facing text is translated)
- [x] T061 [P] Add navigation links to subscription management pages in base template or navigation menu in `app/templates/base.html.twig`
- [x] T062 [P] Style subscription management templates to match application design system (CSS/styling updates)
- [x] T063 Add comprehensive error handling for Stripe API failures, network errors, and webhook processing errors
- [x] T064 Add logging for all Stripe operations (Checkout creation, webhook processing, subscription management) for audit and debugging
- [ ] T065 Verify webhook endpoint is publicly accessible and test webhook delivery from Stripe Dashboard
- [x] T066 Add admin interface for viewing webhook processing logs and failed events (optional but recommended)
- [x] T067 Add monitoring/alerting for failed webhook processing and Stripe API errors
- [ ] T068 Code review: Verify Controllers → Services → Repositories architecture is followed
- [ ] T069 Code review: Verify all business logic is in services, not controllers
- [ ] T070 Code review: Verify PSR-12 coding standards are followed throughout
- [ ] T071 Run quickstart.md validation to ensure implementation matches quickstart guide
- [ ] T072 Test complete upgrade flow: Free → Pro → Enterprise → Cancel → Free
- [ ] T073 Test webhook idempotency by sending duplicate events and verifying no duplicate updates
- [ ] T074 Test error scenarios: invalid webhook signatures, missing users, API failures

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3+)**: All depend on Foundational phase completion
  - User Stories 1 and 2 (P1) should be implemented first as MVP
  - User Story 3 (P2) can proceed after US1/US2
  - User Story 4 (P3) can proceed after US3
- **Polish (Final Phase)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - Creates Checkout sessions but requires US2 for plan updates
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - Processes webhooks to update plans. CRITICAL for US1 to work end-to-end
- **User Story 3 (P2)**: Depends on US1/US2 - Requires active subscriptions and webhook processing
- **User Story 4 (P3)**: Depends on US2 - Requires Payment entities created by webhooks

### Within Each User Story

- Entities before repositories
- Repositories before services
- Services before controllers
- Controllers before templates
- Core implementation before error handling
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel
- All Foundational tasks marked [P] can run in parallel (within Phase 2)
- Once Foundational phase completes, US1 and US2 can start in parallel (but US2 is needed for US1 to work end-to-end)
- All entity creation tasks marked [P] can run in parallel
- Template creation tasks marked [P] can run in parallel
- Translation tasks marked [P] can run in parallel

---

## Implementation Strategy

### MVP First (User Stories 1 & 2)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL - blocks all stories)
3. Complete Phase 3: User Story 1 (Checkout)
4. Complete Phase 4: User Story 2 (Webhooks) - **REQUIRED for US1 to work**
5. **STOP and VALIDATE**: Test complete upgrade flow independently
6. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready
2. Add User Story 1 → Users can initiate upgrades (but plans won't update yet)
3. Add User Story 2 → Plans update automatically via webhooks (MVP complete!)
4. Add User Story 3 → Users can manage subscriptions
5. Add User Story 4 → Users can view payment history
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together
2. Once Foundational is done:
   - Developer A: User Story 1 (Checkout)
   - Developer B: User Story 2 (Webhooks) - **Must complete before US1 works end-to-end**
   - Developer C: User Story 3 (Management) - Can start after US1/US2
   - Developer D: User Story 4 (History) - Can start after US2

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- US2 (Webhooks) is CRITICAL - US1 won't update plans without it
- Webhook endpoint must be publicly accessible for Stripe to deliver events
- Use Stripe test mode for development, production keys for production
- Webhook signature validation is mandatory for security
- Idempotent webhook processing prevents duplicate plan updates
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence

