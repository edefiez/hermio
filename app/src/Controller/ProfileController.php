<?php

namespace App\Controller;

use App\Service\AuthenticationLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private AuthenticationLogService $logService
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        $activityLogs = $this->logService->getUserActivityLog($user, 10);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'activity_logs' => $activityLogs,
        ]);
    }
}
