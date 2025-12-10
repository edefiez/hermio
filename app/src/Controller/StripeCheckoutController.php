<?php

namespace App\Controller;

use App\Enum\PlanType;
use App\Service\StripeCheckoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class StripeCheckoutController extends AbstractController
{
    public function __construct(
        private StripeCheckoutService $checkoutService
    ) {
    }

    #[Route('/subscription/checkout/create', name: 'app_subscription_checkout_create', methods: ['POST'])]
    public function createCheckoutSession(Request $request): Response
    {
        $planTypeValue = $request->request->get('planType');
        
        if (!$planTypeValue) {
            $this->addFlash('error', 'Plan type is required');
            return $this->redirectToRoute('app_account_my_plan');
        }

        try {
            $planType = PlanType::from($planTypeValue);
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Invalid plan type');
            return $this->redirectToRoute('app_account_my_plan');
        }

        // Validate plan type (only PRO and ENTERPRISE allowed for upgrades)
        if ($planType === PlanType::FREE) {
            $this->addFlash('error', 'Cannot upgrade to Free plan');
            return $this->redirectToRoute('app_account_my_plan');
        }

        $user = $this->getUser();
        $currentPlan = $user->getAccount()?->getPlanType();

        // Check if already on this plan or higher
        if ($currentPlan === $planType || ($currentPlan === PlanType::ENTERPRISE && $planType === PlanType::PRO)) {
            $this->addFlash('info', 'You are already on this plan or higher');
            return $this->redirectToRoute('app_account_my_plan');
        }

        try {
            $checkoutUrl = $this->checkoutService->createCheckoutSession($user, $planType);
            return $this->redirect($checkoutUrl);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create checkout session. Please try again later.');
            return $this->redirectToRoute('app_account_my_plan');
        }
    }

    #[Route('/subscription/checkout/success', name: 'app_subscription_checkout_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');
        
        if ($sessionId) {
            $this->addFlash('success', 'Payment successful! Your subscription will be activated shortly.');
        } else {
            $this->addFlash('success', 'Payment successful! Your subscription has been activated.');
        }

        return $this->redirectToRoute('app_account_my_plan');
    }

    #[Route('/subscription/checkout/cancel', name: 'app_subscription_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('info', 'Checkout was cancelled. Your plan remains unchanged.');

        return $this->redirectToRoute('app_account_my_plan');
    }
}

