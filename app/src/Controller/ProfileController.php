<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\UpdateEmailFormType;
use App\Repository\UserRepository;
use App\Service\AuthenticationLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile_change_password');
            }

            // Hash and set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been changed successfully.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/update-email', name: 'app_profile_update_email')]
    public function updateEmail(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(UpdateEmailFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newEmail = $form->get('newEmail')->getData();
            $currentPassword = $form->get('currentPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_profile_update_email');
            }

            // Check if email is already in use
            $existingUser = $userRepository->findOneBy(['email' => $newEmail]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'This email address is already in use.');
                return $this->redirectToRoute('app_profile_update_email');
            }

            // Update email and mark as unverified
            $user->setEmail($newEmail);
            $user->setIsEmailVerified(false);
            
            $entityManager->flush();

            $this->addFlash('success', 'Your email has been updated successfully. Please verify your new email address.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/update_email.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
