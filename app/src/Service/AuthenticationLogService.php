<?php

namespace App\Service;

use App\Entity\AuthenticationLog;
use App\Entity\User;
use App\Repository\AuthenticationLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthenticationLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuthenticationLogRepository $logRepository
    ) {
    }

    public function log(
        string $eventType,
        ?User $user = null,
        bool $successful = true,
        ?Request $request = null,
        ?string $details = null
    ): AuthenticationLog {
        $log = new AuthenticationLog();
        $log->setEventType($eventType);
        $log->setUser($user);
        $log->setSuccessful($successful);
        
        if ($request) {
            $log->setIpAddress($request->getClientIp() ?? 'unknown');
            $log->setUserAgent($request->headers->get('User-Agent') ?? 'unknown');
        } else {
            $log->setIpAddress('unknown');
            $log->setUserAgent('unknown');
        }
        
        if ($details) {
            $log->setDetails($details);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    public function logLoginSuccess(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_LOGIN_SUCCESS, $user, true, $request);
    }

    public function logLoginFailure(?User $user, Request $request, string $reason = ''): void
    {
        $this->log(
            AuthenticationLog::EVENT_LOGIN_FAILURE,
            $user,
            false,
            $request,
            $reason
        );
    }

    public function logLogout(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_LOGOUT, $user, true, $request);
    }

    public function logRegistration(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_REGISTRATION, $user, true, $request);
    }

    public function logEmailVerified(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_EMAIL_VERIFIED, $user, true, $request);
    }

    public function logPasswordResetRequested(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_PASSWORD_RESET_REQUESTED, $user, true, $request);
    }

    public function logPasswordResetCompleted(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_PASSWORD_RESET_COMPLETED, $user, true, $request);
    }

    public function logPasswordChanged(User $user, Request $request): void
    {
        $this->log(AuthenticationLog::EVENT_PASSWORD_CHANGED, $user, true, $request);
    }

    public function getRecentFailedAttempts(string $ipAddress, int $minutes = 15): int
    {
        return $this->logRepository->countRecentFailedAttempts($ipAddress, $minutes);
    }

    public function getUserActivityLog(User $user, int $limit = 10): array
    {
        return $this->logRepository->findRecentByUser($user, $limit);
    }
}
