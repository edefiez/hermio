<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationService $emailVerificationService,
        private AccountService $accountService
    ) {
    }

    public function createUser(string $email, string $plainPassword): User
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new \RuntimeException('A user with this email already exists');
        }
        
        $user = new User();
        $user->setEmail($email);
        $user->setStatus('pending');
        $user->setIsEmailVerified(false);
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Create default account with FREE plan
        $account = $this->accountService->createDefaultAccount($user);
        $user->setAccount($account);
        $this->entityManager->flush();
        
        return $user;
    }

    public function registerUser(string $email, string $plainPassword, string $verifyUrlTemplate): User
    {
        $user = $this->createUser($email, $plainPassword);
        
        // Create and send verification token
        $token = $this->emailVerificationService->createVerificationToken($user, $email);
        $verifyUrl = str_replace('{token}', $token->getToken(), $verifyUrlTemplate);
        $this->emailVerificationService->sendVerificationEmail($user, $token, $verifyUrl);
        
        return $user;
    }

    public function resendVerificationEmail(User $user, string $verifyUrlTemplate): void
    {
        if ($user->isEmailVerified()) {
            throw new \RuntimeException('User email is already verified');
        }
        
        $token = $this->emailVerificationService->createVerificationToken($user, $user->getEmail());
        $verifyUrl = str_replace('{token}', $token->getToken(), $verifyUrlTemplate);
        $this->emailVerificationService->sendVerificationEmail($user, $token, $verifyUrl);
    }
}
