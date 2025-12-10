# Contracts: Stripe Billing Integration

**Date**: December 8, 2025  
**Feature**: Stripe Billing Integration

## Contract Files

- **routes.md**: Symfony route definitions for Stripe Checkout, webhooks, and subscription management
- **README.md**: This file - overview of contracts

## Contract Overview

This feature defines contracts for:

1. **Stripe Checkout Integration**: Routes for creating checkout sessions and handling success/cancel callbacks
2. **Webhook Processing**: Public webhook endpoint for Stripe event processing
3. **Subscription Management**: Routes for viewing and managing active subscriptions
4. **Customer Portal**: Route for accessing Stripe Customer Portal
5. **Payment History**: Route for viewing payment history and invoices

## External API Contracts

### Stripe API

**Base URL**: `https://api.stripe.com/v1` (production) or `https://api.stripe.com/v1` (test mode)

**Authentication**: Bearer token using Stripe secret key

**Key Endpoints Used**:
- `POST /v1/checkout/sessions` - Create Checkout session
- `POST /v1/billing_portal/sessions` - Create Customer Portal session
- `GET /v1/customers/{id}` - Retrieve customer
- `GET /v1/subscriptions/{id}` - Retrieve subscription
- `GET /v1/payment_intents/{id}` - Retrieve payment intent
- `GET /v1/invoices` - List invoices for customer

**Webhook Events Processed**:
- `payment_intent.succeeded` - Payment completed successfully
- `payment_intent.payment_failed` - Payment failed
- `customer.subscription.created` - Subscription created
- `customer.subscription.updated` - Subscription updated
- `customer.subscription.deleted` - Subscription cancelled/deleted

## Internal Service Contracts

### StripeService

**Purpose**: Wrapper for Stripe PHP SDK

**Key Methods**:
- `createCheckoutSession(array $params): CheckoutSession`
- `createCustomerPortalSession(string $customerId): BillingPortalSession`
- `retrieveCustomer(string $customerId): Customer`
- `retrieveSubscription(string $subscriptionId): Subscription`

### StripeWebhookService

**Purpose**: Process Stripe webhook events

**Key Methods**:
- `processEvent(Event $event): void`
- `validateSignature(string $payload, string $signature): bool`
- `isEventProcessed(string $eventId): bool`
- `markEventProcessed(string $eventId, bool $success): void`

### SubscriptionService

**Purpose**: Manage subscriptions and synchronize with Account entity

**Key Methods**:
- `syncSubscriptionFromStripe(string $subscriptionId): Subscription`
- `updateAccountFromSubscription(Subscription $subscription): void`
- `downgradeAccountToFree(User $user): void`

---

## Notes

- All Stripe API calls use official Stripe PHP SDK
- Webhook signature validation is mandatory for security
- Idempotent webhook processing prevents duplicate updates
- Account entity is synchronized with Stripe subscription status

