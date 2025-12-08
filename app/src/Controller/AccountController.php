<?php

namespace App\Controller;

use App\Service\AccountService;
use App\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    public function __construct(
        private AccountService $accountService,
        private QuotaService $quotaService
    ) {
    }

    #[Route('/account/my-plan', name: 'app_account_my_plan')]
    #[IsGranted('ROLE_USER')]
    public function myPlan(): Response
    {
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            // Create default account if missing
            $account = $this->accountService->createDefaultAccount($user);
            $user->setAccount($account);
        }

        $planType = $account->getPlanType();
        $quotaLimit = $planType->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        $usagePercentage = $quotaLimit !== null ? ($currentUsage / $quotaLimit) * 100 : null;
        $isUnlimited = $planType->isUnlimited();

        return $this->render('account/my_plan.html.twig', [
            'account' => $account,
            'planType' => $planType,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'usagePercentage' => $usagePercentage,
            'isUnlimited' => $isUnlimited,
        ]);
    }

    #[Route('/account', name: 'app_account_index')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Ensure user has an account
        $account = $user->getAccount();
        if (!$account) {
            $account = $this->accountService->createDefaultAccount($user);
            $user->setAccount($account);
        }

        $planType = $account->getPlanType();
        $quotaLimit = $planType->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);

        return $this->render('account/index.html.twig', [
            'account' => $account,
            'user' => $user,
            'planSummary' => [
                'type' => $planType,
                'limit' => $quotaLimit,
                'usage' => $currentUsage,
            ],
        ]);
    }
}

