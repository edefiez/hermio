# Research: Stripe Billing Integration

**Date**: December 8, 2025  
**Feature**: Stripe Billing Integration  
**Phase**: 0 - Technical Research

## Technical Decisions

### 1. Stripe PHP SDK Selection

**Decision**: Use official `stripe/stripe-php` package (latest stable version)

**Rationale**:
- Official Stripe SDK maintained by Stripe team
- Full API coverage for Checkout, Subscriptions, Webhooks, Customer Portal
- Well-documented and widely used in PHP/Symfony projects
- Supports both test and production modes via API keys
- Provides webhook signature validation utilities

**Alternative Considered**: Custom HTTP client with Stripe REST API
- **Rejected**: More maintenance overhead, no built-in signature validation, higher risk of errors

**Implementation**: Install via Composer: `composer require stripe/stripe-php`

---

### 2. Stripe Checkout vs Stripe Elements

**Decision**: Use Stripe Checkout (hosted payment page)

**Rationale**:
- Spec requirement: "Stripe Checkout is the preferred payment method"
- Lower PCI compliance burden (Stripe handles card data)
- Better mobile experience (responsive, optimized)
- Simpler implementation (redirect-based flow)
- Built-in support for subscriptions and one-time payments

**Alternative Considered**: Stripe Elements (embedded forms)
- **Rejected**: Not specified in requirements, more complex implementation, higher PCI scope

**Implementation**: Create Checkout Session via Stripe API, redirect user to Stripe-hosted page

---

### 3. Webhook Processing Architecture

**Decision**: Dedicated webhook controller with signature validation and idempotency handling

**Rationale**:
- Security: MUST validate webhook signatures to prevent spoofing
- Reliability: Idempotent processing prevents duplicate plan updates
- Separation of concerns: WebhookController handles HTTP, WebhookService processes events
- Error handling: Centralized logging and error handling for webhook events

**Implementation Pattern**:
1. `StripeWebhookController` receives POST requests from Stripe
2. Validates webhook signature using Stripe webhook secret
3. Extracts event payload and delegates to `StripeWebhookService`
4. `StripeWebhookService` processes events idempotently (check if already processed)
5. Updates Account entity and creates Payment/Subscription records

**Idempotency Strategy**: Store processed event IDs in database to prevent duplicate processing

---

### 4. Customer Portal Integration

**Decision**: Use Stripe Customer Portal (hosted) for subscription management

**Rationale**:
- Stripe handles payment method updates securely
- Built-in subscription cancellation and modification UI
- Reduces development and maintenance burden
- Better security (Stripe manages sensitive operations)
- Spec requirement: "access Stripe's customer portal to update my card information securely"

**Alternative Considered**: Custom subscription management UI
- **Rejected**: More complex, higher security risk, more maintenance

**Implementation**: Generate Customer Portal session URL via Stripe API, redirect user to Stripe-hosted portal

---

### 5. Account ↔ Subscription Synchronization

**Decision**: Bidirectional sync with Account entity as source of truth for plan display, Stripe as source of truth for billing

**Rationale**:
- Account entity already exists (Feature 003) and controls quota limits
- Stripe manages billing lifecycle (payments, renewals, cancellations)
- Webhooks update Account.planType when Stripe events occur
- AccountService continues to manage plan changes, but Stripe events override manual changes

**Synchronization Flow**:
1. User upgrades via Checkout → Stripe creates subscription → Webhook updates Account.planType
2. User cancels in Portal → Stripe sends webhook → Account downgraded to FREE
3. Payment fails → Stripe sends webhook → Account remains unchanged (grace period)
4. Admin manually changes plan → Account updated → Stripe subscription updated via API (if exists)

**Edge Case**: If Account.planType doesn't match Stripe subscription, webhook events take precedence

---

### 6. Entity Design: StripeCustomer, Payment, Subscription

**Decision**: Three separate entities with clear relationships

**Rationale**:
- **StripeCustomer**: One-to-one with User, stores Stripe customer ID (required for all Stripe operations)
- **Payment**: One-to-many with User, audit trail for all transactions
- **Subscription**: One-to-one with User (active subscription), links to Stripe subscription ID

**Relationships**:
- User → StripeCustomer (OneToOne, optional - only created when first payment initiated)
- User → Payment[] (OneToMany, all payment history)
- User → Subscription (OneToOne, optional - only when active subscription exists)

**Why Separate Entities**:
- Payment history persists even if subscription is cancelled
- Multiple payments can exist for one subscription (renewals, upgrades)
- StripeCustomer can exist without active subscription (for future payments)

---

### 7. Webhook Endpoint Security

**Decision**: Signature validation only (no Symfony Security firewall)

**Rationale**:
- Stripe webhooks come from Stripe servers, not authenticated users
- Signature validation is sufficient security (cryptographic verification)
- No user session required for webhook processing
- Public endpoint accessible from Stripe's servers

**Implementation**: 
- Validate `Stripe-Signature` header using webhook secret
- Reject requests with invalid signatures (log security warning)
- Process valid webhooks asynchronously if possible

**Alternative Considered**: IP whitelist + signature validation
- **Rejected**: Stripe IPs can change, signature validation is sufficient

---

### 8. Idempotency Strategy

**Decision**: Store processed webhook event IDs in database

**Rationale**:
- Stripe may retry webhook delivery (network issues, timeouts)
- Duplicate events must not cause duplicate plan upgrades
- Database lookup is reliable and fast for idempotency checks

**Implementation**:
- Create `ProcessedWebhookEvent` entity or add `processed_events` table
- Before processing webhook, check if `event.id` already exists
- If exists, skip processing and return 200 OK (Stripe expects success)
- If new, process event and store event ID

**Alternative Considered**: In-memory cache (Redis)
- **Rejected**: Not available in current stack, database is sufficient for MVP

---

### 9. Error Handling and Retry Logic

**Decision**: Log errors, return 200 OK to Stripe, implement manual reconciliation

**Rationale**:
- Stripe retries failed webhooks (non-200 responses)
- Returning 200 prevents infinite retry loops
- Logging enables manual reconciliation for failed events
- For MVP, manual reconciliation is acceptable (can add queue later)

**Implementation**:
- Try-catch around webhook processing
- Log errors with full event details
- Return 200 OK even on errors (prevents Stripe retries)
- Admin can manually reconcile failed events via admin interface (future)

**Future Enhancement**: Queue failed events for retry processing

---

### 10. Environment Configuration

**Decision**: Store Stripe keys in environment variables

**Rationale**:
- Security best practice (keys not in code)
- Support for test and production environments
- Symfony DotEnv integration

**Required Environment Variables**:
- `STRIPE_SECRET_KEY` (test/production)
- `STRIPE_PUBLISHABLE_KEY` (for frontend if needed)
- `STRIPE_WEBHOOK_SECRET` (for webhook signature validation)

**Implementation**: Use Symfony ParameterBag or direct `$_ENV` access in services

---

## Dependencies

### External Services
- Stripe account with API keys (test and production)
- Stripe products and prices configured for Pro and Enterprise plans
- Public webhook endpoint URL (for Stripe to deliver events)

### Internal Dependencies
- Feature 003 (Account Management) - Account entity and PlanType enum must exist
- Feature 002 (User Authentication) - User entity and authentication system

### PHP Packages
- `stripe/stripe-php` (to be installed)

---

## Open Questions / Future Considerations

1. **Annual Billing**: Currently monthly only - can be extended later
2. **Prorating**: Stripe handles automatically, but we may want to display prorated amounts in UI
3. **Failed Payment Retry**: Currently no automatic retry - Stripe handles this, but we may want to notify users
4. **Subscription Pause**: Not in scope for MVP, but could be added later
5. **Multi-currency**: Single currency for MVP, can be extended later
6. **Tax Handling**: Stripe handles tax calculation, but we may want to display tax breakdown

---

## References

- [Stripe PHP SDK Documentation](https://stripe.com/docs/api/php)
- [Stripe Checkout Documentation](https://stripe.com/docs/payments/checkout)
- [Stripe Webhooks Guide](https://stripe.com/docs/webhooks)
- [Stripe Customer Portal](https://stripe.com/docs/billing/subscriptions/integrating-customer-portal)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)

