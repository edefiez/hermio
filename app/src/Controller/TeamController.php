<?php

namespace App\Controller;

use App\Enum\PlanType;
use App\Enum\TeamRole;
use App\Form\TeamInvitationFormType;
use App\Form\TeamMemberRoleFormType;
use App\Repository\TeamMemberRepository;
use App\Service\TeamInvitationService;
use App\Service\TeamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team')]
#[IsGranted('ROLE_USER')]
class TeamController extends AbstractController
{
    public function __construct(
        private TeamService $teamService,
        private TeamInvitationService $invitationService,
        private EntityManagerInterface $entityManager,
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    #[Route('', name: 'app_team_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();

        if (!$account) {
            throw $this->createAccessDeniedException('No account found');
        }

        // Check Enterprise plan
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            $this->addFlash('error', 'team.access_denied');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $canManageTeam = $this->teamService->canManageTeam($account, $user);
        $teamMembers = $this->teamService->getTeamMembers($account);
        
        // Get team overview with assignment counts for owners/ADMINs
        $teamOverview = null;
        if ($canManageTeam) {
            $teamOverview = $this->teamService->getTeamOverview($account);
        }

        $invitationForm = null;
        if ($canManageTeam) {
            $invitationForm = $this->createForm(TeamInvitationFormType::class);
            $invitationForm->handleRequest($request);

            if ($invitationForm->isSubmitted() && $invitationForm->isValid()) {
                $data = $invitationForm->getData();
                
                try {
                    $teamMember = $this->invitationService->createInvitation(
                        $account,
                        $data['email'],
                        \App\Enum\TeamRole::from($data['role'] ?? 'member')
                    );

                    $acceptUrl = $this->generateUrl(
                        'app_team_accept',
                        ['token' => $teamMember->getInvitationToken()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    
                    $this->invitationService->sendInvitationEmail($teamMember, $acceptUrl);

                    $this->addFlash('success', 'team.invite.success');
                    return $this->redirectToRoute('app_team_index');
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
        }

        return $this->render('team/index.html.twig', [
            'account' => $account,
            'teamMembers' => $teamMembers,
            'teamOverview' => $teamOverview,
            'invitationForm' => $invitationForm?->createView(),
            'canManageTeam' => $canManageTeam,
            'isAccountOwner' => $account->getUser() === $user,
            'isEnterprise' => true,
        ]);
    }

    #[Route('/accept/{token}', name: 'app_team_accept', methods: ['GET', 'POST'])]
    public function acceptInvitation(string $token, Request $request): Response
    {
        $teamMember = $this->invitationService->getInvitationByToken($token);

        if (!$teamMember) {
            throw $this->createNotFoundException('team.invitation.invalid');
        }

        $isExpired = false;
        if ($teamMember->getInvitationExpiresAt() && $teamMember->getInvitationExpiresAt() < new \DateTime()) {
            $isExpired = true;
            if ($teamMember->getInvitationStatus() === 'pending') {
                $teamMember->setInvitationStatus('expired');
                $this->entityManager->flush();
            }
        }

        $isLoggedIn = $this->getUser() !== null;
        $user = $this->getUser();
        $userEmail = $isLoggedIn && $user instanceof \App\Entity\User ? $user->getEmail() : null;

        if ($request->isMethod('POST')) {
            if (!$isLoggedIn) {
                $this->addFlash('error', 'team.invitation.login_required');
                return $this->redirectToRoute('app_login');
            }

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $action = $request->request->get('action');

            if ($action === 'accept') {
                try {
                    $this->invitationService->acceptInvitation($token, $user);
                    $this->addFlash('success', 'team.invitation.accept.success');
                    return $this->redirectToRoute('app_team_index');
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } elseif ($action === 'decline') {
                $teamMember->setInvitationStatus('declined');
                $this->entityManager->flush();
                $this->addFlash('success', 'team.invitation.decline.success');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('team/accept_invitation.html.twig', [
            'teamMember' => $teamMember,
            'account' => $teamMember->getAccount(),
            'isExpired' => $isExpired,
            'isLoggedIn' => $isLoggedIn,
            'userEmail' => $userEmail,
        ]);
    }

    #[Route('/{id}/role', name: 'app_team_change_role', methods: ['POST'])]
    public function changeRole(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();

        if (!$account) {
            throw $this->createAccessDeniedException('No account found');
        }

        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            throw $this->createAccessDeniedException('team.access_denied');
        }

        // Only account owner can change roles
        if ($account->getUser() !== $user) {
            throw $this->createAccessDeniedException('team.role.change.denied');
        }

        $teamMember = $this->teamMemberRepository->find($id);
        if (!$teamMember || $teamMember->getAccount() !== $account) {
            throw $this->createNotFoundException('Team member not found');
        }

        $form = $this->createForm(TeamMemberRoleFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isCsrfTokenValid('team_role_change', $request->request->get('_token'))) {
                try {
                    $data = $form->getData();
                    $newRole = TeamRole::from($data['role'] ?? 'member');
                    $this->teamService->changeRole($teamMember, $newRole, $user);
                    $this->addFlash('success', 'team.role.change.success');
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }
        }

        return $this->redirectToRoute('app_team_index');
    }

    #[Route('/{id}/remove', name: 'app_team_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $account = $user->getAccount();

        if (!$account) {
            throw $this->createAccessDeniedException('No account found');
        }

        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            throw $this->createAccessDeniedException('team.access_denied');
        }

        // Only account owner can remove team members
        if ($account->getUser() !== $user) {
            throw $this->createAccessDeniedException('team.remove.denied');
        }

        $teamMember = $this->teamMemberRepository->find($id);
        if (!$teamMember || $teamMember->getAccount() !== $account) {
            throw $this->createNotFoundException('Team member not found');
        }

        if ($this->isCsrfTokenValid('team_remove' . $teamMember->getId(), $request->request->get('_token'))) {
            try {
                $this->teamService->removeTeamMember($teamMember, $user);
                $this->addFlash('success', 'team.remove.success');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_team_index');
    }

    #[Route('/{id}/resend', name: 'app_team_resend_invitation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resendInvitation(int $id, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $teamMember = $this->entityManager->getRepository(TeamMember::class)->find($id);

        if (!$teamMember || $teamMember->getAccount()->getUser() !== $user) {
            throw $this->createAccessDeniedException('team.invite.resend.denied');
        }

        if ($this->isCsrfTokenValid('resend_invitation' . $teamMember->getId(), $request->request->get('_token'))) {
            try {
                $acceptUrl = $this->generateUrl(
                    'app_team_accept',
                    ['token' => $teamMember->getInvitationToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                
                $this->invitationService->resendInvitation($teamMember, $acceptUrl);
                $this->addFlash('success', 'team.invite.resend.success');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_team_index');
    }
}

