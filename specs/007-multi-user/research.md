# Research: Multi-user (Enterprise)

**Feature**: 007-multi-user  
**Date**: December 10, 2025  
**Phase**: 0 - Research & Technology Selection

## Overview

This research document consolidates technology decisions for implementing multi-user team collaboration for Enterprise accounts. Since this feature builds on the existing Symfony 8 infrastructure and follows constitutional requirements, most technology choices are pre-determined by the project's architecture standards. Key research areas include invitation token management, role-based authorization patterns, and team member access control strategies.

## Technology Decisions

### Decision 1: Team Invitation Token Management

**Decision**: Use secure token-based invitation system similar to EmailVerificationService pattern, with tokens stored in TeamMember entity

**Rationale**:
- Follows existing pattern (EmailVerificationToken, PasswordResetToken)
- Secure token generation using random_bytes()
- Token expiration (7 days) handled via expiration date field
- Tokens can be invalidated when invitation is accepted or declined
- No additional dependencies required
- Database-backed tokens allow tracking invitation status

**Alternatives Considered**:
- Separate TeamInvitation entity: Considered but rejected - storing invitation data in TeamMember is simpler and reduces entities
- JWT tokens: Considered but rejected - overkill for invitations, database tokens are sufficient
- Email-only invitations (no tokens): Rejected because no way to track invitation status or expiration

**Implementation Pattern**:
```php
// In TeamMember entity
#[ORM\Column(type: Types::STRING, length: 64, nullable: true, unique: true)]
private ?string $invitationToken = null;

#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $invitationExpiresAt = null;

#[ORM\Column(type: Types::STRING, length: 20)]
private string $invitationStatus = 'pending'; // pending, accepted, declined, expired

// In TeamInvitationService
public function createInvitation(Account $account, string $email, TeamRole $role): TeamMember
{
    $token = bin2hex(random_bytes(32)); // 64-character token
    
    $teamMember = new TeamMember();
    $teamMember->setAccount($account);
    $teamMember->setEmail($email);
    $teamMember->setRole($role);
    $teamMember->setInvitationToken($token);
    $teamMember->setInvitationStatus('pending');
    $teamMember->setInvitationExpiresAt(new \DateTime('+7 days'));
    
    // Save and send email...
}
```

**Token Security**:
- 64-character hexadecimal token (32 bytes)
- Unique constraint prevents collisions
- Expiration enforced at service layer
- Tokens invalidated after acceptance/decline

**Constitutional Reference**: Follows Symfony service pattern (Constitution Section I)

---

### Decision 2: Role-Based Authorization Strategy

**Decision**: Use Symfony Security Voters for team member role-based authorization

**Rationale**:
- Symfony Security best practice for complex authorization logic
- Voters allow fine-grained permission checks
- Can check both user roles (ROLE_USER) and team member roles (ADMIN/MEMBER)
- Reusable across controllers and services
- Easy to test and maintain
- Follows Symfony Security patterns

**Alternatives Considered**:
- Service layer checks only: Considered but rejected - Voters provide better separation and reusability
- Custom security expressions: Considered but rejected - Voters are more maintainable for complex logic
- Role hierarchy only: Rejected because doesn't handle team-specific permissions

**Implementation Pattern**:
```php
// In TeamMemberVoter
class TeamMemberVoter extends Voter
{
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
        $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);

        if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
            return false;
        }

        return match($attribute) {
            'TEAM_ASSIGN_CARD' => $teamMember->getRole() === TeamRole::ADMIN || $account->getUser() === $user,
            'TEAM_MANAGE_MEMBERS' => $teamMember->getRole() === TeamRole::ADMIN || $account->getUser() === $user,
            'TEAM_VIEW_ALL' => $teamMember->getRole() === TeamRole::ADMIN || $account->getUser() === $user,
            default => false,
        };
    }
}
```

**Permission Matrix**:
- Account Owner: Full access (all permissions)
- Team ADMIN: Can assign cards, view all cards, view team members (cannot remove account owner or change plan)
- Team MEMBER: Can only access assigned cards

**Constitutional Reference**: Follows Symfony Security patterns (Constitution Section IV)

---

### Decision 3: Card Assignment Relationship Model

**Decision**: Use separate CardAssignment entity with ManyToMany relationship pattern (Card ↔ TeamMember)

**Rationale**:
- Allows multiple team members per card (spec requirement FR-005)
- Allows tracking assignment metadata (assigned date, assigned by)
- Clean separation of concerns (Card entity not directly coupled to TeamMember)
- Easy to query assignments in both directions
- Supports assignment history if needed later

**Alternatives Considered**:
- Direct ManyToMany on Card: Considered but rejected - loses assignment metadata (date, assigned by)
- Single assignment per card: Rejected because spec allows multiple members per card
- Assignment stored in Card entity: Rejected because violates single responsibility

**Implementation Pattern**:
```php
// CardAssignment entity
#[ORM\Entity]
class CardAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Card $card;

    #[ORM\ManyToOne(targetEntity: TeamMember::class, inversedBy: 'cardAssignments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private TeamMember $teamMember;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $assignedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedBy; // Who made the assignment
}
```

**Relationship Structure**:
- Card (OneToMany) → CardAssignment (ManyToOne) → TeamMember
- TeamMember (OneToMany) → CardAssignment (ManyToOne) → Card
- Allows querying: "Which cards are assigned to this team member?" and "Which team members are assigned to this card?"

**Constitutional Reference**: Follows Doctrine ORM patterns (Constitution Section III)

---

### Decision 4: Team Member Email Handling

**Decision**: Store invitation email in TeamMember entity, link to User entity after invitation acceptance

**Rationale**:
- Allows inviting users who don't have accounts yet
- Email stored separately from User allows tracking invitation status before user exists
- After acceptance, link TeamMember to User entity
- Supports inviting existing users (email matches User.email)
- Follows pattern similar to EmailVerificationToken

**Alternatives Considered**:
- Require User to exist before invitation: Rejected because limits flexibility, users may not have accounts
- Separate TeamInvitation entity: Considered but rejected - storing in TeamMember is simpler
- Email-only (no User link): Rejected because team members need User accounts for authentication

**Implementation Pattern**:
```php
// In TeamMember entity
#[ORM\Column(type: Types::STRING, length: 180)]
#[Assert\Email]
private string $email; // Invitation email

#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
private ?User $user = null; // Linked after acceptance

// In TeamInvitationService
public function acceptInvitation(string $token, User $user): TeamMember
{
    $teamMember = $this->findByToken($token);
    
    // Verify email matches
    if ($teamMember->getEmail() !== $user->getEmail()) {
        throw new \InvalidArgumentException('Email mismatch');
    }
    
    $teamMember->setUser($user);
    $teamMember->setInvitationStatus('accepted');
    $teamMember->setInvitationToken(null);
    $teamMember->setJoinedAt(new \DateTime());
    
    return $teamMember;
}
```

**Email Matching Strategy**:
- When user accepts invitation, verify email matches TeamMember.email
- If user doesn't exist, they must register first with matching email
- After registration, they can accept invitation

**Constitutional Reference**: Follows existing email verification pattern (Feature 002)

---

### Decision 5: Plan Downgrade Handling Strategy

**Decision**: Revoke team member access immediately on downgrade, preserve team member and assignment data

**Rationale**:
- Preserves data in case account upgrades again
- Immediate access revocation ensures plan restrictions enforced
- TeamMember records can be reactivated if account upgrades
- Card assignments preserved for data integrity
- Matches spec requirement: "preserve data, disable features"

**Alternatives Considered**:
- Delete team members on downgrade: Rejected because poor UX, data loss
- Keep access active: Rejected because violates plan restrictions
- Archive team data: Considered but rejected for MVP - preserving is simpler

**Implementation Pattern**:
```php
// In AccountService (extend existing changePlan method)
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

// In TeamService
public function revokeTeamAccess(Account $account): void
{
    $teamMembers = $this->teamMemberRepository->findByAccount($account);
    
    foreach ($teamMembers as $member) {
        // Mark as inactive but preserve data
        $member->setInvitationStatus('revoked');
        // Access checks will fail because account plan is not Enterprise
    }
    
    $this->entityManager->flush();
}
```

**Access Control Strategy**:
- Service layer checks both TeamMember status AND Account plan type
- Even if TeamMember exists, access denied if Account plan is not Enterprise
- Data preserved for potential reactivation

**Constitutional Reference**: Follows service layer pattern (Constitution Section I)

---

### Decision 6: Multiple Enterprise Account Membership

**Decision**: Allow users to be members of multiple Enterprise accounts simultaneously

**Rationale**:
- Spec requirement FR-015 explicitly allows this
- Users may work for multiple organizations
- Each TeamMember record is independent
- User can switch context between accounts
- No technical limitation preventing this

**Alternatives Considered**:
- Single account membership only: Rejected because violates spec requirement
- Account switching UI: Considered but deferred - can be added later, not required for MVP

**Implementation Pattern**:
```php
// User can have multiple TeamMember records
// Each TeamMember links to different Account

// In TeamService
public function getUserTeamMemberships(User $user): array
{
    return $this->teamMemberRepository->findByUser($user);
}

// In CardService (check access)
public function canAccessCard(User $user, Card $card): bool
{
    $account = $card->getUser()->getAccount();
    
    // Check if user is account owner
    if ($account->getUser() === $user) {
        return true;
    }
    
    // Check if user is team member of this account
    $teamMember = $this->teamMemberRepository->findByAccountAndUser($account, $user);
    
    if (!$teamMember || $teamMember->getInvitationStatus() !== 'accepted') {
        return false;
    }
    
    // Check card assignment for MEMBERs
    if ($teamMember->getRole() === TeamRole::MEMBER) {
        return $this->cardAssignmentRepository->isAssignedTo($card, $teamMember);
    }
    
    // ADMINs can access all cards in account
    return $teamMember->getRole() === TeamRole::ADMIN;
}
```

**Context Switching**:
- For MVP: User sees all their team memberships
- Future enhancement: Account switcher UI to filter by active account
- Current implementation: Show all assigned cards from all accounts

**Constitutional Reference**: Follows Doctrine ORM relationship patterns (Constitution Section III)

---

### Decision 7: Invitation Email Template Strategy

**Decision**: Use Symfony Mailer with Twig email templates, similar to EmailVerificationService

**Rationale**:
- Follows existing email pattern (registration, password reset)
- Twig templates allow HTML email formatting
- Symfony Mailer handles email delivery
- Can include invitation link with token
- Supports internationalization

**Alternatives Considered**:
- Plain text emails: Considered but rejected - HTML emails provide better UX
- Third-party email service: Considered but rejected - Symfony Mailer is sufficient for MVP
- Separate email service: Rejected - can use existing MailerInterface

**Implementation Pattern**:
```php
// In TeamInvitationService
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
```

**Email Template Structure**:
- HTML template with invitation details
- Clear call-to-action button for acceptance
- Expiration date displayed
- Account/organization name included
- Role information included

**Constitutional Reference**: Follows existing email service pattern (Feature 002)

---

### Decision 8: Team Role Enum Strategy

**Decision**: Create TeamRole enum similar to PlanType enum pattern

**Rationale**:
- Type-safe role representation
- Consistent with existing PlanType enum pattern
- Easy to extend with more roles later
- Can include role display names and permissions
- No database storage needed (string column with enum validation)

**Alternatives Considered**:
- String constants: Considered but rejected - enum provides better type safety
- Database lookup table: Rejected because over-engineered for two roles
- Symfony roles (ROLE_TEAM_ADMIN): Considered but rejected - conflicts with existing role hierarchy

**Implementation Pattern**:
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
}
```

**Role Permissions**:
- ADMIN: Can assign cards, view all cards, view team members, manage team members (except owner)
- MEMBER: Can only access assigned cards

**Constitutional Reference**: Follows existing enum pattern (PlanType in Feature 003)

---

## Integration Points

### With Existing Account Entity

- Add `OneToMany` relationship from Account to TeamMember
- Account owner is implicit (Account.user), not stored as TeamMember
- Account deletion cascades to TeamMember deletion (CASCADE)
- Plan type validation uses existing `PlanType` enum

### With Existing User Entity

- TeamMember links to User after invitation acceptance
- User can have multiple TeamMember records (multiple accounts)
- User authentication uses existing Symfony Security system
- No changes to User entity required

### With Existing Card Entity

- Add `OneToMany` relationship from Card to CardAssignment
- Card ownership remains with User (Card.user)
- Card access extended to include team member assignments
- CardService checks both ownership and assignments

### With Existing Email System

- Uses existing Symfony Mailer infrastructure
- Follows EmailVerificationService pattern for token management
- Email templates use Twig (consistent with existing emails)
- Supports internationalization (EN/FR)

### With Symfony Security

- Team management routes require ROLE_USER authentication
- Enterprise plan requirement enforced at service layer
- TeamMemberVoter provides role-based authorization
- Access control checks both plan type and team membership

## Unresolved Dependencies

- **Email configuration**: Must configure Symfony Mailer for production (SMTP settings)
- **Invitation expiration cleanup**: May need scheduled task to mark expired invitations (optional enhancement)
- **Account switching UI**: Deferred to future enhancement - MVP shows all memberships

## Best Practices Applied

1. **Doctrine Relationships**: Using proper ManyToOne and OneToMany bidirectional relationships
2. **Service Layer**: All business logic in services, not controllers
3. **Security Voters**: Role-based authorization using Symfony Security Voters
4. **Token Security**: Secure token generation with expiration
5. **Internationalization**: All user-facing messages use translation keys
6. **Validation**: Symfony Validator constraints on entities and forms
7. **Migrations**: Schema changes managed via Doctrine migrations
8. **Graceful Degradation**: Preserve data on plan downgrade, revoke access
9. **Email Patterns**: Follow existing email service patterns
10. **Enum Types**: Type-safe role representation

## References

- Symfony 8 Documentation: Security Voters, Mailer Component
- Doctrine ORM 3.x: ManyToOne, OneToMany Relationships, Cascade Operations
- Symfony Security: Voter Pattern, Role-Based Access Control
- Project Constitution: Sections I, II, III, IV
- Feature 002 (User Auth): EmailVerificationService pattern
- Feature 003 (Account/Subscription): PlanType enum pattern, plan validation
- Feature 005 (Digital Card): Card entity structure, access patterns

