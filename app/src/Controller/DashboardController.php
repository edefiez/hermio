<?php

namespace App\Controller;

use App\Service\AccountService;
use App\Service\AuthenticationLogService;
use App\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private AccountService $accountService,
        private QuotaService $quotaService,
        private AuthenticationLogService $authenticationLogService
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
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
        
        // Get total cards count
        $totalCards = $user->getCards()->count();
        $activeCards = $user->getActiveCards()->count();
        
        // Get recent activity logs
        $recentActivity = $this->authenticationLogService->getUserActivityLog($user, 5);
        
        // Calculate member since
        $memberSince = $user->getCreatedAt();
        $daysSince = $memberSince ? (new \DateTime())->diff($memberSince)->days : 0;

        return $this->render('admin/dashboard.html.twig', [
            'account' => $account,
            'planType' => $planType,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'usagePercentage' => $usagePercentage,
            'isUnlimited' => $isUnlimited,
            'recentActivity' => $recentActivity,
            'totalCards' => $totalCards,
            'activeCards' => $activeCards,
            'memberSince' => $memberSince,
            'daysSince' => $daysSince,
        ]);
    }
}

