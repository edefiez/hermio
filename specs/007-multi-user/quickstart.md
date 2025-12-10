# Quickstart Guide: Multi-user (Enterprise)

**Feature**: 007-multi-user  
**Date**: December 10, 2025  
**Target Audience**: Developers implementing this feature

## Overview

This quickstart guide provides step-by-step instructions for implementing the multi-user team collaboration system for Enterprise accounts. Follow these steps in order to build the feature incrementally.

## Prerequisites

- Symfony 8.0+ installed and configured
- Feature 002 (User Account & Authentication) completed
- Feature 003 (Account Subscription) completed
- Feature 005 (Digital Card Management) completed
- Database connection configured (PostgreSQL or MySQL)
- Doctrine ORM 3.x installed
- User, Account, and Card entities exist
- Symfony Mailer configured for email sending

## Implementation Steps

### Step 1: Create TeamRole Enum

**File**: `app/src/Enum/TeamRole.php`

Create the TeamRole enum:

```php
namespace App\Enum;

enum TeamRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MEMBER => 'Member',
        };
    }

    public function canAssignCards(): bool
    {
        return $this === self::ADMIN;
    }

    public function canManageMembers(): bool
    {
        return $this === self::ADMIN;
    }

    public function canViewAllCards(): bool
    {
        return $this === self::ADMIN;
    }
}
```

**Verification**: Enum compiles without errors, can be used in type hints.

---

### Step 2: Create TeamMember Entity

**File**: `app/src/Entity/TeamMember.php`

Create the TeamMember entity:

```php
namespace App\Entity;

use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamMemberRepository::class)]
#[ORM\Table(name: 'team_members')]
#[ORM\UniqueConstraint(name: 'account_email_unique', columns: ['account_id', 'email'])]
#[ORM\HasLifecycleCallbacks]
class TeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(type: Types::STRING, length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TeamRole::class)]
    #[Assert\NotBlank]
    private TeamRole $role = TeamRole::MEMBER;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $invitationStatus = 'pending';

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, unique: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $invitationExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $joinedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastActivityAt = null;

    #[ORM\OneToMany(targetEntity: CardAssignment::class, mappedBy: 'teamMember', cascade: ['remove'])]
    private Collection $cardAssignments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->cardAssignments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and setters...
}
```

**Verification**: Entity validates, can be persisted to database.

---

### Step 3: Create CardAssignment Entity

**File**: `app/src/Entity/CardAssignment.php`

Create the CardAssignment entity:

```php
namespace App\Entity;

use App\Repository\CardAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardAssignmentRepository::class)]
#[ORM\Table(name: 'card_assignments')]
#[ORM\UniqueConstraint(name: 'card_team_member_unique', columns: ['card_id', 'team_member_id'])]
#[ORM\HasLifecycleCallbacks]
class CardAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Card $card;

    #[ORM\ManyToOne(targetEntity: TeamMember::class, inversedBy: 'cardAssignments')]
    #[ORM\JoinColumn(name: 'team_member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TeamMember $teamMember;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $assignedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_by_id', referencedColumnName: 'id', nullable: false)]
    private User $assignedBy;

    #[ORM\PrePersist]
    public function setAssignedAtValue(): void
    {
        $this->assignedAt = new \DateTime();
    }

    // Getters and setters...
}
```

**Verification**: Entity validates, can be persisted to database.

---

### Step 4: Update Account Entity

**File**: `app/src/Entity/Account.php`

Add OneToMany relationship to TeamMember:

```php
#[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'account', cascade: ['remove'])]
private Collection $teamMembers;

public function __construct()
{
    $this->teamMembers = new ArrayCollection();
}

public function getTeamMembers(): Collection
{
    return $this->teamMembers;
}

public function addTeamMember(TeamMember $teamMember): static
{
    if (!$this->teamMembers->contains($teamMember)) {
        $this->teamMembers->add($teamMember);
        $teamMember->setAccount($this);
    }
    return $this;
}

public function removeTeamMember(TeamMember $teamMember): static
{
    if ($this->teamMembers->removeElement($teamMember)) {
        if ($teamMember->getAccount() === $this) {
            $teamMember->setAccount(null);
        }
    }
    return $this;
}
```

**Verification**: Account entity compiles, relationship works bidirectionally.

---

### Step 5: Update Card Entity

**File**: `app/src/Entity/Card.php`

Add OneToMany relationship to CardAssignment:

```php
#[ORM\OneToMany(targetEntity: CardAssignment::class, mappedBy: 'card', cascade: ['remove'])]
private Collection $assignments;

public function __construct()
{
    $this->assignments = new ArrayCollection();
}

public function getAssignments(): Collection
{
    return $this->assignments;
}

public function addAssignment(CardAssignment $assignment): static
{
    if (!$this->assignments->contains($assignment)) {
        $this->assignments->add($assignment);
        $assignment->setCard($this);
    }
    return $this;
}

public function removeAssignment(CardAssignment $assignment): static
{
    if ($this->assignments->removeElement($assignment)) {
        if ($assignment->getCard() === $this) {
            $assignment->setCard(null);
        }
    }
    return $this;
}
```

**Verification**: Card entity compiles, relationship works bidirectionally.

---

### Step 6: Create Repositories

**File**: `app/src/Repository/TeamMemberRepository.php`

```php
namespace App\Repository;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    public function findByAccount(Account $account): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->setParameter('account', $account)
            ->orderBy('tm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAccountAndUser(Account $account, User $user): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.account = :account')
            ->andWhere('tm.user = :user')
            ->andWhere('tm.invitationStatus = :status')
            ->setParameter('account', $account)
            ->setParameter('user', $user)
            ->setParameter('status', 'accepted')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByToken(string $token): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.invitationToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredInvitations(): array
    {
        return $this->createQueryBuilder('tm')
            ->where('tm.invitationStatus = :status')
            ->andWhere('tm.invitationExpiresAt < :now')
            ->setParameter('status', 'pending')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
```

**File**: `app/src/Repository/CardAssignmentRepository.php`

```php
namespace App\Repository;

use App\Entity\Card;
use App\Entity\CardAssignment;
use App\Entity\TeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CardAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CardAssignment::class);
    }

    public function findByCard(Card $card): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.card = :card')
            ->setParameter('card', $card)
            ->getQuery()
            ->getResult();
    }

    public function findByTeamMember(TeamMember $teamMember): array
    {
        return $this->createQueryBuilder('ca')
            ->where('ca.teamMember = :teamMember')
            ->setParameter('teamMember', $teamMember)
            ->getQuery()
            ->getResult();
    }

    public function isAssignedTo(Card $card, TeamMember $teamMember): bool
    {
        return $this->createQueryBuilder('ca')
            ->select('COUNT(ca.id)')
            ->where('ca.card = :card')
            ->andWhere('ca.teamMember = :teamMember')
            ->setParameter('card', $card)
            ->setParameter('teamMember', $teamMember)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
```

**Verification**: Repositories compile, queries work correctly.

---

### Step 7: Create TeamInvitationService

**File**: `app/src/Service/TeamInvitationService.php`

```php
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
        private MailerInterface $mailer
    ) {
    }

    public function createInvitation(Account $account, string $email, TeamRole $role): TeamMember
    {
        // Validate Enterprise plan
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            throw new \InvalidArgumentException('Team features are only available for Enterprise plans');
        }

        // Check for duplicate invitation
        $existing = $this->teamMemberRepository->findOneBy([
            'account' => $account,
            'email' => $email,
        ]);

        if ($existing && $existing->getInvitationStatus() === 'pending') {
            throw new \InvalidArgumentException('This user has already been invited to this team');
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));

        $teamMember = new TeamMember();
        $teamMember->setAccount($account);
        $teamMember->setEmail($email);
        $teamMember->setRole($role);
        $teamMember->setInvitationToken($token);
        $teamMember->setInvitationStatus('pending');
        $teamMember->setInvitationExpiresAt(new \DateTime('+7 days'));

        $this->entityManager->persist($teamMember);
        $this->entityManager->flush();

        return $teamMember;
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
            throw new \InvalidArgumentException('Invalid invitation token');
        }

        if ($teamMember->getInvitationStatus() !== 'pending') {
            throw new \InvalidArgumentException('Invitation has already been processed');
        }

        if ($teamMember->getInvitationExpiresAt() < new \DateTime()) {
            $teamMember->setInvitationStatus('expired');
            $this->entityManager->flush();
            throw new \InvalidArgumentException('Invitation has expired');
        }

        if ($teamMember->getEmail() !== $user->getEmail()) {
            throw new \InvalidArgumentException('Email mismatch');
        }

        $teamMember->setUser($user);
        $teamMember->setInvitationStatus('accepted');
        $teamMember->setInvitationToken(null);
        $teamMember->setJoinedAt(new \DateTime());

        $this->entityManager->flush();

        return $teamMember;
    }
}
```

**Verification**: Service compiles, methods work correctly.

---

### Step 8: Create TeamService

**File**: `app/src/Service/TeamService.php`

```php
namespace App\Service;

use App\Entity\Account;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Enum\PlanType;
use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamService
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function canManageTeam(Account $account, User $user): bool
    {
        if ($account->getUser() === $user) {
            return true; // Account owner
        }

        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        return $teamMember && $teamMember->getRole() === TeamRole::ADMIN;
    }

    public function changeRole(TeamMember $teamMember, TeamRole $newRole, User $requester): void
    {
        $account = $teamMember->getAccount();

        // Only account owner can change roles
        if ($account->getUser() !== $requester) {
            throw new \InvalidArgumentException('Only account owners can change team member roles');
        }

        // Cannot change account owner's role
        if ($teamMember->getUser() === $account->getUser()) {
            throw new \InvalidArgumentException('Cannot change account owner role');
        }

        $teamMember->setRole($newRole);
        $this->entityManager->flush();
    }

    public function removeTeamMember(TeamMember $teamMember, User $requester): void
    {
        $account = $teamMember->getAccount();

        // Only account owner can remove team members
        if ($account->getUser() !== $requester) {
            throw new \InvalidArgumentException('Only account owners can remove team members');
        }

        // Cannot remove account owner
        if ($teamMember->getUser() === $account->getUser()) {
            throw new \InvalidArgumentException('Cannot remove account owner');
        }

        $this->entityManager->remove($teamMember);
        $this->entityManager->flush();
    }

    public function revokeTeamAccess(Account $account): void
    {
        $teamMembers = $this->teamMemberRepository->findByAccount($account);

        foreach ($teamMembers as $member) {
            $member->setInvitationStatus('revoked');
        }

        $this->entityManager->flush();
    }
}
```

**Verification**: Service compiles, methods work correctly.

---

### Step 9: Create TeamMemberVoter

**File**: `app/src/Security/Voter/TeamMemberVoter.php`

```php
namespace App\Security\Voter;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\TeamMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TeamMemberVoter extends Voter
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['TEAM_ASSIGN_CARD', 'TEAM_MANAGE_MEMBERS', 'TEAM_VIEW_ALL'])
            && $subject instanceof Card;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $account = $subject->getUser()->getAccount();
        if (!$account) {
            return false;
        }

        // Account owner has full access
        if ($account->getUser() === $user) {
            return true;
        }

        // Check team membership
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return false;
        }

        return match($attribute) {
            'TEAM_ASSIGN_CARD' => $teamMember->getRole()->canAssignCards(),
            'TEAM_MANAGE_MEMBERS' => $teamMember->getRole()->canManageMembers(),
            'TEAM_VIEW_ALL' => $teamMember->getRole()->canViewAllCards(),
            default => false,
        };
    }
}
```

**Verification**: Voter compiles, authorization works correctly.

---

### Step 10: Create Forms

**File**: `app/src/Form/TeamInvitationFormType.php`

```php
namespace App\Form;

use App\Enum\TeamRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamInvitationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'team.invite.email',
                'required' => true,
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'team.invite.role',
                'choices' => [
                    'team.role.admin' => TeamRole::ADMIN->value,
                    'team.role.member' => TeamRole::MEMBER->value,
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'team_invite',
        ]);
    }
}
```

**File**: `app/src/Form/CardAssignmentFormType.php`

```php
namespace App\Form;

use App\Entity\TeamMember;
use App\Repository\TeamMemberRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardAssignmentFormType extends AbstractType
{
    public function __construct(
        private TeamMemberRepository $teamMemberRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = $options['account'];

        $builder
            ->add('teamMembers', EntityType::class, [
                'class' => TeamMember::class,
                'multiple' => true,
                'expanded' => false,
                'choice_label' => 'email',
                'query_builder' => function (TeamMemberRepository $er) use ($account) {
                    return $er->createQueryBuilder('tm')
                        ->where('tm.account = :account')
                        ->andWhere('tm.invitationStatus = :status')
                        ->setParameter('account', $account)
                        ->setParameter('status', 'accepted');
                },
                'label' => 'card.assignments.team_members',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'account' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'card_assign',
        ]);
    }
}
```

**Verification**: Forms compile, can be rendered in Twig.

---

### Step 11: Create TeamController

**File**: `app/src/Controller/TeamController.php`

```php
namespace App\Controller;

use App\Entity\TeamMember;
use App\Enum\PlanType;
use App\Form\TeamInvitationFormType;
use App\Service\TeamInvitationService;
use App\Service\TeamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team')]
#[IsGranted('ROLE_USER')]
class TeamController extends AbstractController
{
    public function __construct(
        private TeamService $teamService,
        private TeamInvitationService $invitationService
    ) {
    }

    #[Route('', name: 'app_team_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $account = $user->getAccount();

        // Check Enterprise plan
        if ($account->getPlanType() !== PlanType::ENTERPRISE) {
            $this->addFlash('error', 'team.access_denied');
            return $this->redirectToRoute('app_subscription_manage');
        }

        $canManageTeam = $this->teamService->canManageTeam($account, $user);
        $teamMembers = $this->teamService->getTeamMembers($account);

        $invitationForm = null;
        if ($canManageTeam) {
            $invitationForm = $this->createForm(TeamInvitationFormType::class);
            $invitationForm->handleRequest($request);

            if ($invitationForm->isSubmitted() && $invitationForm->isValid()) {
                $data = $invitationForm->getData();
                $teamMember = $this->invitationService->createInvitation(
                    $account,
                    $data['email'],
                    $data['role']
                );

                $acceptUrl = $this->generateUrl('app_team_accept', ['token' => $teamMember->getInvitationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                $this->invitationService->sendInvitationEmail($teamMember, $acceptUrl);

                $this->addFlash('success', 'team.invite.success');
                return $this->redirectToRoute('app_team_index');
            }
        }

        return $this->render('team/index.html.twig', [
            'account' => $account,
            'teamMembers' => $teamMembers,
            'invitationForm' => $invitationForm?->createView(),
            'canManageTeam' => $canManageTeam,
            'isAccountOwner' => $account->getUser() === $user,
        ]);
    }

    #[Route('/accept/{token}', name: 'app_team_accept')]
    public function acceptInvitation(string $token, Request $request): Response
    {
        $teamMember = $this->invitationService->getInvitationByToken($token);

        if (!$teamMember) {
            throw $this->createNotFoundException('Invalid invitation token');
        }

        if ($teamMember->getInvitationStatus() !== 'pending') {
            return $this->render('team/accept_invitation.html.twig', [
                'teamMember' => $teamMember,
                'account' => $teamMember->getAccount(),
                'isExpired' => false,
                'isLoggedIn' => false,
            ]);
        }

        if ($teamMember->getInvitationExpiresAt() < new \DateTime()) {
            return $this->render('team/accept_invitation.html.twig', [
                'teamMember' => $teamMember,
                'account' => $teamMember->getAccount(),
                'isExpired' => true,
                'isLoggedIn' => false,
            ]);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->render('team/accept_invitation.html.twig', [
                'teamMember' => $teamMember,
                'account' => $teamMember->getAccount(),
                'isExpired' => false,
                'isLoggedIn' => false,
            ]);
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            if ($action === 'accept') {
                try {
                    $this->invitationService->acceptInvitation($token, $user);
                    $this->addFlash('success', 'team.accept.success');
                    return $this->redirectToRoute('app_team_index');
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            } elseif ($action === 'decline') {
                $teamMember->setInvitationStatus('declined');
                $this->entityManager->flush();
                $this->addFlash('success', 'team.decline.success');
                return $this->redirectToRoute('app_dashboard');
            }
        }

        return $this->render('team/accept_invitation.html.twig', [
            'teamMember' => $teamMember,
            'account' => $teamMember->getAccount(),
            'isExpired' => false,
            'isLoggedIn' => true,
            'userEmail' => $user->getEmail(),
        ]);
    }

    // Additional methods: changeRole, remove...
}
```

**Verification**: Controller compiles, routes work correctly.

---

### Step 12: Update CardService for Team Access

**File**: `app/src/Service/CardService.php` (modify existing)

Add methods to check team member access:

```php
public function canAccessCard(User $user, Card $card): bool
{
    // Card owner has full access
    if ($card->getUser() === $user) {
        return true;
    }

    $account = $card->getUser()->getAccount();
    if (!$account || $account->getPlanType() !== PlanType::ENTERPRISE) {
        return false;
    }

    $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
    if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
        return false;
    }

    // ADMINs can access all cards
    if ($teamMember->getRole() === TeamRole::ADMIN) {
        return true;
    }

    // MEMBERs can only access assigned cards
    return $this->cardAssignmentRepository->isAssignedTo($card, $teamMember);
}
```

**Verification**: Service methods work correctly.

---

### Step 13: Create Database Migration

**Command**:
```bash
cd app
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

**Verification**: Migration creates `team_members` and `card_assignments` tables.

---

### Step 14: Create Twig Templates

**File**: `app/templates/team/index.html.twig`

Create team management page template (see contracts/forms.md for structure).

**File**: `app/templates/team/accept_invitation.html.twig`

Create invitation acceptance page template (see contracts/forms.md for structure).

**File**: `app/templates/email/team_invitation.html.twig`

Create invitation email template.

**Verification**: Templates render correctly.

---

### Step 15: Add Translations

**File**: `app/translations/messages.en.yaml` and `app/translations/messages.fr.yaml`

Add all team-related translation keys (see contracts/routes.md for flash messages).

**Verification**: Translations work correctly.

---

### Step 16: Update AccountService for Plan Downgrades

**File**: `app/src/Service/AccountService.php` (modify existing)

Add team access revocation on plan downgrade:

```php
public function changePlan(Account $account, PlanType $newPlan, ...): void
{
    $oldPlan = $account->getPlanType();
    
    // Change plan
    $account->setPlanType($newPlan);
    $this->entityManager->flush();
    
    // Handle team access if downgrading from Enterprise
    if ($oldPlan === PlanType::ENTERPRISE && $newPlan !== PlanType::ENTERPRISE) {
        $this->teamService->revokeTeamAccess($account);
    }
}
```

**Verification**: Plan downgrades revoke team access correctly.

---

## Testing Checklist

- [ ] Team member invitation sent successfully
- [ ] Invitation email received with valid link
- [ ] Invitation acceptance works for logged-in users
- [ ] Invitation expiration enforced (7 days)
- [ ] Duplicate invitations prevented
- [ ] Role changes work correctly (ADMIN ↔ MEMBER)
- [ ] Team member removal works correctly
- [ ] Card assignment works correctly
- [ ] MEMBERs can only access assigned cards
- [ ] ADMINs can access all cards in account
- [ ] Plan downgrade revokes team access
- [ ] Account owner has full access
- [ ] Multiple Enterprise account membership works

---

## Notes

- All services follow Symfony architecture patterns
- Authorization enforced via TeamMemberVoter
- Plan-based access enforced at service layer
- Invitation tokens expire after 7 days
- Card assignments preserved when team members removed
- Account downgrade preserves data but revokes access

