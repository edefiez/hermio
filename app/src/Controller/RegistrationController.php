<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Service\AuthenticationLogService;
use App\Service\EmailVerificationService;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private UserRegistrationService $registrationService,
        private EmailVerificationService $emailVerificationService,
        private AuthenticationLogService $logService
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $email = $form->get('email')->getData();
                $plainPassword = $form->get('plainPassword')->getData();
                $verifyUrlTemplate = $this->generateUrl(
                    'app_verify_email',
                    ['token' => '{token}'],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                
                $user = $this->registrationService->registerUser(
                    $email,
                    $plainPassword,
                    $verifyUrlTemplate
                );
                
                // Log registration event
                $this->logService->logRegistration($user, $request);
                
                $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');
                
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Registration failed: ' . $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, Request $request): Response
    {
        $user = $this->emailVerificationService->verifyToken($token);
        
        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification token.');
            return $this->redirectToRoute('app_login');
        }
        
        // Log email verification
        $this->logService->logEmailVerified($user, $request);
        
        $this->addFlash('success', 'Your email has been verified! You can now log in.');
        
        return $this->redirectToRoute('app_login');
    }
}
