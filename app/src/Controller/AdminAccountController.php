<?php

namespace App\Controller;

use App\Entity\Account;
use App\Enum\PlanType;
use App\Form\PlanChangeFormType;
use App\Repository\AccountRepository;
use App\Service\AccountService;
use App\Service\QuotaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/accounts')]
#[IsGranted('ROLE_ADMIN')]
class AdminAccountController extends AbstractController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private AccountService $accountService,
        private QuotaService $quotaService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_admin_account_index')]
    public function index(Request $request): Response
    {
        $planFilter = $request->query->get('plan');
        
        $queryBuilder = $this->accountRepository->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u');

        if ($planFilter && in_array($planFilter, ['free', 'pro', 'enterprise'], true)) {
            $queryBuilder->andWhere('a.planType = :plan')
                ->setParameter('plan', $planFilter);
        }

        $queryBuilder->orderBy('a.createdAt', 'DESC');
        
        $accounts = $queryBuilder->getQuery()->getResult();
        
        // Count by plan type
        $planCounts = [
            'free' => $this->accountRepository->count(['planType' => PlanType::FREE]),
            'pro' => $this->accountRepository->count(['planType' => PlanType::PRO]),
            'enterprise' => $this->accountRepository->count(['planType' => PlanType::ENTERPRISE]),
        ];

        return $this->render('admin/account/index.html.twig', [
            'accounts' => $accounts,
            'totalUsers' => count($accounts),
            'planCounts' => $planCounts,
            'currentFilter' => $planFilter,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_account_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $account = $this->accountRepository->find($id);
        
        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        $user = $account->getUser();
        $planType = $account->getPlanType();
        $quotaLimit = $planType->getQuotaLimit();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        
        // Check if downgrade is possible
        $canDowngrade = true;
        $downgradeWarning = null;
        
        if ($planType !== PlanType::FREE) {
            // Check if user has more content than lower plans allow
            $lowerPlans = $planType === PlanType::ENTERPRISE 
                ? [PlanType::PRO, PlanType::FREE]
                : [PlanType::FREE];
            
            foreach ($lowerPlans as $lowerPlan) {
                $lowerLimit = $lowerPlan->getQuotaLimit();
                if ($lowerLimit !== null && $currentUsage > $lowerLimit) {
                    $canDowngrade = false;
                    $downgradeWarning = sprintf(
                        'User has %d cards, but %s plan only allows %d cards.',
                        $currentUsage,
                        $lowerPlan->getDisplayName(),
                        $lowerLimit
                    );
                    break;
                }
            }
        }

        $form = $this->createForm(PlanChangeFormType::class, $account, [
            'current_plan' => $planType,
            'current_usage' => $currentUsage,
        ]);

        return $this->render('admin/account/show.html.twig', [
            'account' => $account,
            'user' => $user,
            'planType' => $planType,
            'quotaLimit' => $quotaLimit,
            'currentUsage' => $currentUsage,
            'canDowngrade' => $canDowngrade,
            'downgradeWarning' => $downgradeWarning,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/change-plan', name: 'app_admin_account_change_plan', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changePlan(int $id, Request $request): Response
    {
        $account = $this->accountRepository->find($id);
        
        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        $user = $account->getUser();
        $currentUsage = $this->quotaService->getCurrentUsage($user);
        
        $form = $this->createForm(PlanChangeFormType::class, $account, [
            'current_plan' => $account->getPlanType(),
            'current_usage' => $currentUsage,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPlanType = PlanType::from($form->get('planType')->getData());
            $confirmDowngrade = $form->get('confirmDowngrade')->getData() ?? false;
            
            try {
                $currentUser = $this->getUser();
                $updatedBy = $currentUser instanceof \App\Entity\User 
                    ? $currentUser->getEmail() 
                    : 'admin';
                
                $this->accountService->changePlan($account, $newPlanType, $confirmDowngrade, $updatedBy);
                
                $this->addFlash('success', sprintf(
                    'Plan successfully changed to %s',
                    $newPlanType->getDisplayName()
                ));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->redirectToRoute('app_admin_account_show', ['id' => $id]);
    }
}

