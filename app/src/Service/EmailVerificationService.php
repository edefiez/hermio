<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationTokenRepository $tokenRepository,
        private MailerInterface $mailer
    ) {
    }

    public function createVerificationToken(User $user, string $email): EmailVerificationToken
    {
        // Invalidate any existing tokens for this user
        $this->tokenRepository->invalidateUserTokens($user);
        
        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setEmail($email);
        
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        
        return $token;
    }

    public function sendVerificationEmail(User $user, EmailVerificationToken $token, string $verifyUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@hermio.local', 'Hermio'))
            ->to(new Address($token->getEmail()))
            ->subject('Please confirm your email address')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'user' => $user,
                'token' => $token,
                'verifyUrl' => $verifyUrl,
                'expiresAt' => $token->getExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function verifyToken(string $tokenValue): ?User
    {
        $token = $this->tokenRepository->findValidToken($tokenValue);
        
        if (!$token) {
            return null;
        }
        
        // Mark token as used
        $token->setIsUsed(true);
        $token->setUsedAt(new \DateTime());
        
        // Update user email verification status
        $user = $token->getUser();
        $user->setIsEmailVerified(true);
        
        // If user was pending, activate them
        if ($user->getStatus() === 'pending') {
            $user->setStatus('active');
        }
        
        $this->entityManager->flush();
        
        return $user;
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpiredTokens();
    }
}
