<?php

namespace App\Service;

use App\Entity\StripeCustomer;
use App\Entity\User;
use App\Enum\PlanType;
use App\Repository\StripeCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class StripeCheckoutService
{
    public function __construct(
        private StripeService $stripeService,
        private StripeCustomerRepository $stripeCustomerRepository,
        private EntityManagerInterface $entityManager,
        private string $successUrl,
        private string $cancelUrl,
        private LoggerInterface $logger
    ) {
    }

    public function createCheckoutSession(User $user, PlanType $planType): string
    {
        $this->logger->info('Creating Stripe Checkout session', [
            'user_id' => $user->getId(),
            'plan_type' => $planType->value,
        ]);

        try {
            // Get or create Stripe customer
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);

            // Get Stripe price ID for plan (from environment or config)
            $priceId = $this->getPriceIdForPlan($planType);

            // Create Checkout session
            $session = $this->stripeService->createCheckoutSession([
                'customer' => $stripeCustomer->getStripeCustomerId(),
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $this->successUrl,
                'cancel_url' => $this->cancelUrl,
                'metadata' => [
                    'user_id' => (string) $user->getId(),
                    'plan_type' => $planType->value,
                ],
            ]);

            $this->logger->info('Stripe Checkout session created successfully', [
                'user_id' => $user->getId(),
                'session_id' => $session->id,
                'plan_type' => $planType->value,
            ]);

            return $session->url;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create Stripe Checkout session', [
                'user_id' => $user->getId(),
                'plan_type' => $planType->value,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function getOrCreateStripeCustomer(User $user): StripeCustomer
    {
        $stripeCustomer = $this->stripeCustomerRepository->findOneBy(['user' => $user]);
        
        if (!$stripeCustomer) {
            $this->logger->info('Creating new Stripe customer', ['user_id' => $user->getId()]);
            
            try {
                // Create Stripe customer
                $customer = $this->stripeService->createCustomer([
                    'email' => $user->getEmail(),
                    'metadata' => ['user_id' => (string) $user->getId()],
                ]);

                // Create StripeCustomer entity
                $stripeCustomer = new StripeCustomer();
                $stripeCustomer->setUser($user);
                $stripeCustomer->setStripeCustomerId($customer->id);
                $this->entityManager->persist($stripeCustomer);
                $this->entityManager->flush();

                $this->logger->info('Stripe customer created successfully', [
                    'user_id' => $user->getId(),
                    'stripe_customer_id' => $customer->id,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to create Stripe customer', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        return $stripeCustomer;
    }

    private function getPriceIdForPlan(PlanType $planType): string
    {
        // Get from environment variables
        return match($planType) {
            PlanType::PRO => $_ENV['STRIPE_PRICE_ID_PRO'] ?? throw new \InvalidArgumentException('STRIPE_PRICE_ID_PRO not configured'),
            PlanType::ENTERPRISE => $_ENV['STRIPE_PRICE_ID_ENTERPRISE'] ?? throw new \InvalidArgumentException('STRIPE_PRICE_ID_ENTERPRISE not configured'),
            default => throw new \InvalidArgumentException('Invalid plan type for checkout'),
        };
    }
}

