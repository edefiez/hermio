<?php

namespace App\Service;

use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    public function createCheckoutSession(array $params): Session
    {
        return $this->stripe->checkout->sessions->create($params);
    }

    public function createCustomerPortalSession(string $customerId, string $returnUrl): PortalSession
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    public function createCustomer(array $params)
    {
        return $this->stripe->customers->create($params);
    }

    public function retrieveCustomer(string $customerId)
    {
        return $this->stripe->customers->retrieve($customerId);
    }

    public function retrieveSubscription(string $subscriptionId)
    {
        return $this->stripe->subscriptions->retrieve($subscriptionId);
    }
}

