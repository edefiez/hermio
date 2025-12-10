# Routes Contract: Stripe Billing Integration

**Date**: December 8, 2025  
**Feature**: Stripe Billing Integration

## Route Definitions

### User-Facing Routes

#### 1. Initiate Checkout Session

**Route**: `POST /subscription/checkout/create`  
**Name**: `app_subscription_checkout_create`  
**Controller**: `StripeCheckoutController::createCheckoutSession()`  
**Authorization**: `ROLE_USER`  
**Method**: POST  
**CSRF Protection**: Yes

**Purpose**: Creates a Stripe Checkout session for plan upgrade and redirects user to Stripe-hosted payment page.

**Request Parameters**:
- `planType` (string, required): Plan type to upgrade to (`pro` or `enterprise`)
- CSRF token (required)

**Response**:
- Success (302): Redirect to Stripe Checkout URL
- Error (400): Invalid plan type or missing parameters
- Error (403): User not authenticated

**Example**:
```php
#[Route('/subscription/checkout/create', name: 'app_subscription_checkout_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function createCheckoutSession(Request $request): Response
```

---

#### 2. Checkout Success Callback

**Route**: `GET /subscription/checkout/success`  
**Name**: `app_subscription_checkout_success`  
**Controller**: `StripeCheckoutController::success()`  
**Authorization**: `ROLE_USER`  
**Method**: GET

**Purpose**: Handles user redirect after successful Stripe Checkout payment.

**Query Parameters**:
- `session_id` (string, optional): Stripe Checkout session ID

**Response**:
- Success (200): Display success message and updated plan information
- Error (400): Invalid session ID or session not found

**Example**:
```php
#[Route('/subscription/checkout/success', name: 'app_subscription_checkout_success', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function success(Request $request): Response
```

---

#### 3. Checkout Cancel Callback

**Route**: `GET /subscription/checkout/cancel`  
**Name**: `app_subscription_checkout_cancel`  
**Controller**: `StripeCheckoutController::cancel()`  
**Authorization**: `ROLE_USER`  
**Method**: GET

**Purpose**: Handles user redirect when Stripe Checkout is cancelled.

**Response**:
- Success (200): Display cancellation message, plan remains unchanged

**Example**:
```php
#[Route('/subscription/checkout/cancel', name: 'app_subscription_checkout_cancel', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function cancel(): Response
```

---

#### 4. Subscription Management Page

**Route**: `GET /subscription/manage`  
**Name**: `app_subscription_manage`  
**Controller**: `SubscriptionController::manage()`  
**Authorization**: `ROLE_USER`  
**Method**: GET

**Purpose**: Displays user's active subscription details and management options.

**Response**:
- Success (200): Subscription management page with current plan, billing date, payment method
- Error (404): No active subscription found (for Free plan users)

**Example**:
```php
#[Route('/subscription/manage', name: 'app_subscription_manage', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function manage(): Response
```

---

#### 5. Customer Portal Session

**Route**: `POST /subscription/portal`  
**Name**: `app_subscription_portal`  
**Controller**: `SubscriptionController::createPortalSession()`  
**Authorization**: `ROLE_USER`  
**Method**: POST  
**CSRF Protection**: Yes

**Purpose**: Creates Stripe Customer Portal session and redirects user to Stripe-hosted portal.

**Response**:
- Success (302): Redirect to Stripe Customer Portal URL
- Error (404): No Stripe customer found
- Error (403): User not authenticated

**Example**:
```php
#[Route('/subscription/portal', name: 'app_subscription_portal', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function createPortalSession(): Response
```

---

#### 6. Payment History Page

**Route**: `GET /subscription/payments`  
**Name**: `app_subscription_payments`  
**Controller**: `SubscriptionController::paymentHistory()`  
**Authorization**: `ROLE_USER`  
**Method**: GET

**Purpose**: Displays user's payment history with invoices and receipts.

**Response**:
- Success (200): Payment history page with list of all payments
- Empty (200): No payments found (for Free plan users)

**Example**:
```php
#[Route('/subscription/payments', name: 'app_subscription_payments', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function paymentHistory(): Response
```

---

### Webhook Routes

#### 7. Stripe Webhook Endpoint

**Route**: `POST /stripe/webhook`  
**Name**: `app_stripe_webhook`  
**Controller**: `StripeWebhookController::handleWebhook()`  
**Authorization**: None (public endpoint, signature validation)  
**Method**: POST  
**CSRF Protection**: No (webhook uses signature validation instead)

**Purpose**: Receives and processes Stripe webhook events for payment and subscription updates.

**Request Headers**:
- `Stripe-Signature` (required): Webhook signature for validation

**Request Body**:
- Stripe event JSON payload

**Response**:
- Success (200): Webhook processed successfully
- Error (400): Invalid signature or malformed event
- Error (500): Processing error (but return 200 to prevent Stripe retries)

**Example**:
```php
#[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
public function handleWebhook(Request $request): Response
```

**Security**: 
- Webhook signature validation using Stripe webhook secret
- No Symfony Security firewall (public endpoint)
- Idempotent processing (duplicate events ignored)

---

## Route Summary Table

| Route | Method | Name | Auth | Purpose |
|-------|--------|------|------|---------|
| `/subscription/checkout/create` | POST | `app_subscription_checkout_create` | ROLE_USER | Create Checkout session |
| `/subscription/checkout/success` | GET | `app_subscription_checkout_success` | ROLE_USER | Checkout success callback |
| `/subscription/checkout/cancel` | GET | `app_subscription_checkout_cancel` | ROLE_USER | Checkout cancel callback |
| `/subscription/manage` | GET | `app_subscription_manage` | ROLE_USER | Subscription management |
| `/subscription/portal` | POST | `app_subscription_portal` | ROLE_USER | Customer Portal access |
| `/subscription/payments` | GET | `app_subscription_payments` | ROLE_USER | Payment history |
| `/stripe/webhook` | POST | `app_stripe_webhook` | None | Webhook endpoint |

---

## Route Configuration

Routes can be defined using Symfony attributes in controllers:

```php
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
```

**Example Controller Structure**:
```php
#[IsGranted('ROLE_USER')]
class StripeCheckoutController extends AbstractController
{
    #[Route('/subscription/checkout/create', name: 'app_subscription_checkout_create', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response { }
}
```

---

## Notes

- All user-facing routes require `ROLE_USER` authentication
- Webhook route is public but secured via Stripe signature validation
- CSRF protection enabled for all POST routes except webhook
- Webhook endpoint must be publicly accessible from Stripe servers
- Checkout success/cancel routes handle redirects from Stripe

