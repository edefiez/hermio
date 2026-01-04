<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\PlanType;
use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class TeamInvitationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TeamMemberRepository $teamMemberRepository,
        private MailerInterface $mailer,
        private ?InvitationRateLimiter $rateLimiter = null
    ) {
    }

    public function createInvitation(Account $account, string $email, TeamRole $role): TeamMember
    {
        // Validate Enterprise plan
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            throw new \InvalidArgumentException('team.access_denied');
        }

        // Check rate limit
        if ($this->rateLimiter) {
            $this->rateLimiter->checkRateLimit($account);
        }

        // Check for duplicate invitation
        $existing = $this->teamMemberRepository->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->andWhere('tm.email = :email')
            ->setParameter('account', $account)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing && $existing->getInvitationStatus() === 'pending') {
            throw new \InvalidArgumentException('team.invite.duplicate');
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32)); // 64-character token

        $teamMember = new TeamMember();
        $teamMember->setAccount($account);
        $teamMember->setEmail($email);
        $teamMember->setRole($role);
        $teamMember->setInvitationToken($token);
        $teamMember->setInvitationStatus('pending');
        $teamMember->setInvitationExpiresAt(new \DateTime('+7 days'));

        $this->entityManager->persist($teamMember);
        $this->entityManager->flush();

        // Increment rate limit counter
        if ($this->rateLimiter) {
            $this->rateLimiter->incrementRateLimit($account);
        }

        return $teamMember;
    }

    /**
     * Resend invitation with new token (token rotation)
     */
    public function resendInvitation(TeamMember $teamMember, string $acceptUrl): void
    {
        if ($teamMember->getInvitationStatus() !== 'pending') {
            throw new \InvalidArgumentException('team.invite.cannot_resend');
        }

        // Check rate limit
        if ($this->rateLimiter) {
            $this->rateLimiter->checkRateLimit($teamMember->getAccount());
        }

        // Rotate token for security
        $newToken = bin2hex(random_bytes(32));
        $teamMember->setInvitationToken($newToken);
        $teamMember->setInvitationExpiresAt(new \DateTime('+7 days')); // Reset expiration

        $this->entityManager->flush();

        // Send email with new token
        $this->sendInvitationEmail($teamMember, $acceptUrl);

        // Increment rate limit counter
        if ($this->rateLimiter) {
            $this->rateLimiter->incrementRateLimit($teamMember->getAccount());
        }
    }

    public function sendInvitationEmail(TeamMember $teamMember, string $acceptUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@hermio.local', 'Hermio'))
            ->to(new Address($teamMember->getEmail()))
            ->subject('You have been invited to join a team')
            ->htmlTemplate('email/team_invitation.html.twig')
            ->context([
                'teamMember' => $teamMember,
                'account' => $teamMember->getAccount(),
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $teamMember->getInvitationExpiresAt(),
            ]);

        $this->mailer->send($email);
    }

    public function acceptInvitation(string $token, User $user): TeamMember
    {
        $teamMember = $this->teamMemberRepository->findByToken($token);

        if (!$teamMember) {
            throw new \InvalidArgumentException('team.invitation.invalid');
        }

        if ($teamMember->getInvitationStatus() !== 'pending') {
            throw new \InvalidArgumentException('team.invitation.invalid');
        }

        if ($teamMember->getInvitationExpiresAt() < new \DateTime()) {
            $teamMember->setInvitationStatus('expired');
            $this->entityManager->flush();
            throw new \InvalidArgumentException('team.invitation.expired');
        }

        if ($teamMember->getEmail() !== $user->getEmail()) {
            throw new \InvalidArgumentException('team.invitation.email_mismatch');
        }

        $teamMember->setUser($user);
        $teamMember->setInvitationStatus('accepted');
        $teamMember->setInvitationToken(null);
        $teamMember->setJoinedAt(new \DateTime());

        $this->entityManager->flush();

        return $teamMember;
    }

    public function getInvitationByToken(string $token): ?TeamMember
    {
        return $this->teamMemberRepository->findByToken($token);
    }

    /**
     * Mark expired invitations as expired
     * Can be called by scheduled task or cron job
     *
     * @return int Number of invitations marked as expired
     */
    public function markExpiredInvitations(): int
    {
        $expiredInvitations = $this->teamMemberRepository->findExpiredInvitations();
        $count = 0;

        foreach ($expiredInvitations as $teamMember) {
            if ($teamMember->getInvitationStatus() === 'pending') {
                $teamMember->setInvitationStatus('expired');
                $count++;
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}

