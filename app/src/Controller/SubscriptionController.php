<?php

namespace App\Controller;

use App\Repository\PaymentRepository;
use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PaymentRepository $paymentRepository,
        private StripeService $stripeService,
        private SubscriptionService $subscriptionService,
        private string $defaultUri
    ) {
    }

    #[Route('/subscription/manage', name: 'app_subscription_manage', methods: ['GET'])]
    public function manage(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Check if user has an active subscription
        $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
        
        if (!$subscription) {
            // User is on Free plan - redirect to plan page
            $this->addFlash('info', 'You are currently on the Free plan. Upgrade to manage your subscription.');
            return $this->redirectToRoute('app_account_my_plan');
        }

        // Sync subscription from Stripe to ensure we have latest data
        try {
            $subscription = $this->subscriptionService->syncSubscriptionFromStripe($subscription->getStripeSubscriptionId());
        } catch (\Exception $e) {
            // Log error but continue with cached data
            // Error is already logged in SubscriptionService
            $this->addFlash('warning', 'Unable to sync latest subscription data. Showing cached information.');
        }

        $account = $user->getAccount();
        $planType = $subscription->getPlanType();
        $isCancelled = $subscription->getCancelAtPeriodEnd() !== null;
        $isActive = in_array($subscription->getStatus(), ['active', 'trialing']);

        return $this->render('subscription/manage.html.twig', [
            'subscription' => $subscription,
            'account' => $account,
            'planType' => $planType,
            'isCancelled' => $isCancelled,
            'isActive' => $isActive,
        ]);
    }

    #[Route('/subscription/portal', name: 'app_subscription_portal', methods: ['POST'])]
    public function createPortalSession(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $stripeCustomer = $user->getStripeCustomer();

        if (!$stripeCustomer) {
            $this->addFlash('error', 'No Stripe customer found. Please contact support.');
            return $this->redirectToRoute('app_subscription_manage');
        }

        try {
            $returnUrl = $this->defaultUri . '/subscription/manage';
            $portalSession = $this->stripeService->createCustomerPortalSession(
                $stripeCustomer->getStripeCustomerId(),
                $returnUrl
            );

            return $this->redirect($portalSession->url);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create portal session. Please try again later.');
            return $this->redirectToRoute('app_subscription_manage');
        }
    }

    #[Route('/subscription/payments', name: 'app_subscription_payments', methods: ['GET'])]
    public function paymentHistory(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $payments = $this->paymentRepository->findByUserOrderedByDate($user->getId());

        return $this->render('subscription/history.html.twig', [
            'payments' => $payments,
        ]);
    }
}

