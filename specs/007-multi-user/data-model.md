# Data Model: Multi-user (Enterprise)

**Date**: December 10, 2025  
**Feature**: Multi-user (Enterprise)  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The multi-user system introduces two new entities (`TeamMember` and `CardAssignment`) that enable team collaboration for Enterprise accounts. TeamMember represents a user's membership in an Enterprise account team, while CardAssignment links cards to team members for collaborative management. The system extends existing `Account` and `Card` entities with team relationships.

## Entities

### 1. TeamMember Entity

**Purpose**: Represents a user's membership in an Enterprise account team, including invitation status and role

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
    private string $invitationStatus = 'pending'; // pending, accepted, declined, expired, revoked

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

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and setters...
}
```

**Properties**:
- `id`: Primary key, auto-increment
- `account`: ManyToOne relationship to Account entity (which Enterprise account this membership belongs to)
- `email`: Email address used for invitation (180 characters, validated)
- `user`: ManyToOne relationship to User entity (nullable until invitation accepted, SET NULL on user deletion)
- `role`: TeamRole enum (ADMIN or MEMBER) - defaults to MEMBER
- `invitationStatus`: Status of invitation ('pending', 'accepted', 'declined', 'expired', 'revoked') - defaults to 'pending'
- `invitationToken`: Secure token for invitation acceptance (64 characters, unique, nullable after acceptance)
- `invitationExpiresAt`: Expiration date for invitation (7 days from creation, nullable)
- `joinedAt`: Timestamp when invitation was accepted (nullable)
- `lastActivityAt`: Timestamp of last activity (nullable, updated on card access)
- `cardAssignments`: OneToMany relationship to CardAssignment entities
- `createdAt`: Timestamp when team member record was created

**Validation Rules**:
- `email`: Required, valid email format, max 180 characters
- `role`: Required, must be valid TeamRole enum value
- `invitationStatus`: Required, must be one of: pending, accepted, declined, expired, revoked
- `account`: Required, must reference valid Account entity
- Unique constraint: `(account_id, email)` - prevents duplicate invitations to same email for same account

**Business Rules**:
- Team members can only belong to Enterprise accounts (enforced at service layer)
- Invitation tokens expire after 7 days (enforced at service layer)
- User link is set when invitation is accepted
- Account owner is NOT stored as TeamMember (implicit ownership via Account.user)
- Team members can belong to multiple Enterprise accounts (multiple TeamMember records)
- When user is deleted, TeamMember.user is set to NULL (SET NULL cascade)
- When account is deleted, TeamMember records are deleted (CASCADE)

**State Transitions**:
- `pending` → `accepted`: When user accepts invitation
- `pending` → `declined`: When user declines invitation
- `pending` → `expired`: When invitation expires (7 days)
- `accepted` → `revoked`: When account is downgraded from Enterprise
- `accepted`: Can access account resources (if account is Enterprise)

---

### 2. CardAssignment Entity

**Purpose**: Represents the assignment of a Card to a TeamMember for collaborative management

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

**Properties**:
- `id`: Primary key, auto-increment
- `card`: ManyToOne relationship to Card entity (which card is assigned)
- `teamMember`: ManyToOne relationship to TeamMember entity (who the card is assigned to)
- `assignedAt`: Timestamp when assignment was created
- `assignedBy`: ManyToOne relationship to User entity (who made the assignment - account owner or ADMIN)

**Validation Rules**:
- `card`: Required, must reference valid Card entity
- `teamMember`: Required, must reference valid TeamMember entity
- `assignedBy`: Required, must reference valid User entity
- Unique constraint: `(card_id, team_member_id)` - prevents duplicate assignments

**Business Rules**:
- Cards can be assigned to multiple team members (multiple CardAssignment records per card)
- Team members can have multiple card assignments (multiple CardAssignment records per team member)
- When card is deleted, assignments are deleted (CASCADE)
- When team member is removed, assignments are deleted (CASCADE)
- Only account owners and ADMINs can create assignments (enforced at service layer)
- MEMBERs can only access cards assigned to them (enforced at service layer)

**Assignment Metadata**:
- `assignedAt`: Tracks when assignment was created (useful for audit/history)
- `assignedBy`: Tracks who made the assignment (useful for audit/history)

---

### 3. TeamRole Enum

**Purpose**: Type-safe representation of team member roles with permission methods

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

**Enum Values**:
- `ADMIN`: Administrator role - can assign cards, view all cards, manage team members
- `MEMBER`: Member role - can only access assigned cards

**Permission Methods**:
- `canAssignCards()`: Returns true for ADMIN, false for MEMBER
- `canManageMembers()`: Returns true for ADMIN, false for MEMBER
- `canViewAllCards()`: Returns true for ADMIN, false for MEMBER

---

### 4. Account Entity (Modified)

**Purpose**: Extended to support team relationships

**New Relationship**:
```php
#[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'account', cascade: ['remove'])]
private Collection $teamMembers;

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

**Changes**:
- Add `OneToMany` relationship to TeamMember entities
- Add getter/setter methods for team members collection
- Relationship is bidirectional (TeamMember owns the foreign key)
- Cascade remove: When account is deleted, team members are deleted

**Business Rules**:
- Only Enterprise accounts can have team members (enforced at service layer)
- Account owner is implicit (Account.user), not stored as TeamMember
- Team members are deleted when account is deleted (CASCADE)

---

### 5. Card Entity (Modified)

**Purpose**: Extended to support team assignments

**New Relationship**:
```php
#[ORM\OneToMany(targetEntity: CardAssignment::class, mappedBy: 'card', cascade: ['remove'])]
private Collection $assignments;

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

**Changes**:
- Add `OneToMany` relationship to CardAssignment entities
- Add getter/setter methods for assignments collection
- Relationship is bidirectional (CardAssignment owns the foreign key)
- Cascade remove: When card is deleted, assignments are deleted

**Business Rules**:
- Cards can be assigned to multiple team members
- Card ownership remains with User (Card.user)
- Card access extended to include team member assignments
- Assignments are deleted when card is deleted (CASCADE)

---

### 6. User Entity (Modified)

**Purpose**: Extended to support team memberships (optional, for convenience queries)

**New Relationship** (optional, for convenience):
```php
#[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'user')]
private Collection $teamMemberships;

public function getTeamMemberships(): Collection
{
    return $this->teamMemberships;
}
```

**Changes**:
- Add `OneToMany` relationship to TeamMember entities (optional, for convenience)
- Relationship is bidirectional (TeamMember owns the foreign key)
- No cascade operations (TeamMember.user is SET NULL on user deletion)

**Business Rules**:
- Users can have multiple team memberships (multiple Enterprise accounts)
- Team memberships are preserved when user is deleted (TeamMember.user set to NULL)
- User can access cards from all their team memberships

---

## Entity Relationships

### Relationship Map

```
Account (1) ←→ (Many) TeamMember
TeamMember (Many) ←→ (Many) CardAssignment ←→ (Many) Card
TeamMember (Many) ←→ (1) User
CardAssignment (Many) ←→ (1) User (assignedBy)
```

**Relationship Details**:

1. **Account ↔ TeamMember**:
   - **Type**: OneToMany bidirectional
   - **Owning Side**: TeamMember (contains `account_id` foreign key)
   - **Cascade**: REMOVE (account deletion deletes team members)
   - **Unique Constraint**: `(account_id, email)` - prevents duplicate invitations

2. **TeamMember ↔ CardAssignment**:
   - **Type**: OneToMany bidirectional
   - **Owning Side**: CardAssignment (contains `team_member_id` foreign key)
   - **Cascade**: REMOVE (team member deletion deletes assignments)

3. **Card ↔ CardAssignment**:
   - **Type**: OneToMany bidirectional
   - **Owning Side**: CardAssignment (contains `card_id` foreign key)
   - **Cascade**: REMOVE (card deletion deletes assignments)
   - **Unique Constraint**: `(card_id, team_member_id)` - prevents duplicate assignments

4. **TeamMember ↔ User**:
   - **Type**: ManyToOne (TeamMember → User)
   - **Owning Side**: TeamMember (contains `user_id` foreign key)
   - **Cascade**: SET NULL (user deletion sets TeamMember.user to NULL)
   - **Nullable**: Yes (user link set after invitation acceptance)

5. **CardAssignment ↔ User (assignedBy)**:
   - **Type**: ManyToOne (CardAssignment → User)
   - **Owning Side**: CardAssignment (contains `assigned_by_id` foreign key)
   - **Cascade**: None (preserve assignment history even if user deleted)

---

## Database Schema

### team_members Table

```sql
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    email VARCHAR(180) NOT NULL,
    user_id INT NULL,
    role VARCHAR(20) NOT NULL,
    invitation_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    invitation_token VARCHAR(64) NULL UNIQUE,
    invitation_expires_at DATETIME NULL,
    joined_at DATETIME NULL,
    last_activity_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY account_email_unique (account_id, email),
    INDEX idx_account_id (account_id),
    INDEX idx_user_id (user_id),
    INDEX idx_invitation_token (invitation_token),
    INDEX idx_invitation_status (invitation_status)
);
```

### card_assignments Table

```sql
CREATE TABLE card_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    team_member_id INT NOT NULL,
    assigned_by_id INT NOT NULL,
    assigned_at DATETIME NOT NULL,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (team_member_id) REFERENCES team_members(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_id) REFERENCES users(id),
    UNIQUE KEY card_team_member_unique (card_id, team_member_id),
    INDEX idx_card_id (card_id),
    INDEX idx_team_member_id (team_member_id),
    INDEX idx_assigned_by_id (assigned_by_id)
);
```

---

## Query Patterns

### Common Queries

1. **Find team members for an account**:
   ```php
   $teamMembers = $teamMemberRepository->findBy(['account' => $account, 'invitationStatus' => 'accepted']);
   ```

2. **Find team member by account and user**:
   ```php
   $teamMember = $teamMemberRepository->findOneBy(['account' => $account, 'user' => $user]);
   ```

3. **Find cards assigned to a team member**:
   ```php
   $assignments = $cardAssignmentRepository->findBy(['teamMember' => $teamMember]);
   $cards = array_map(fn($a) => $a->getCard(), $assignments);
   ```

4. **Find team members assigned to a card**:
   ```php
   $assignments = $cardAssignmentRepository->findBy(['card' => $card]);
   $teamMembers = array_map(fn($a) => $a->getTeamMember(), $assignments);
   ```

5. **Find expired invitations**:
   ```php
   $expired = $teamMemberRepository->findExpiredInvitations();
   ```

6. **Find user's team memberships**:
   ```php
   $memberships = $teamMemberRepository->findBy(['user' => $user, 'invitationStatus' => 'accepted']);
   ```

---

## Validation Rules Summary

### TeamMember Validation

- Email: Required, valid email format, max 180 characters
- Role: Required, must be TeamRole enum value (ADMIN or MEMBER)
- Invitation Status: Required, must be: pending, accepted, declined, expired, revoked
- Account: Required, must reference valid Account entity
- Unique: `(account_id, email)` - prevents duplicate invitations

### CardAssignment Validation

- Card: Required, must reference valid Card entity
- TeamMember: Required, must reference valid TeamMember entity
- AssignedBy: Required, must reference valid User entity
- Unique: `(card_id, team_member_id)` - prevents duplicate assignments

---

## Migration Strategy

1. Create `TeamRole` enum
2. Create `team_members` table with all columns and constraints
3. Create `card_assignments` table with all columns and constraints
4. Add `teamMembers` relationship to Account entity (no database changes, Doctrine relationship only)
5. Add `assignments` relationship to Card entity (no database changes, Doctrine relationship only)
6. Add `teamMemberships` relationship to User entity (optional, no database changes)

**Migration Order**:
1. TeamRole enum (no database changes)
2. team_members table
3. card_assignments table
4. Entity relationship updates (no database changes)

---

## Data Integrity Rules

1. **Team members can only belong to Enterprise accounts**: Enforced at service layer (TeamService)
2. **Invitation tokens expire after 7 days**: Enforced at service layer (TeamInvitationService)
3. **Duplicate invitations prevented**: Database unique constraint `(account_id, email)`
4. **Duplicate assignments prevented**: Database unique constraint `(card_id, team_member_id)`
5. **Account owner not stored as TeamMember**: Enforced at service layer (implicit ownership)
6. **Cascade deletions**: Account deletion → TeamMember deletion → CardAssignment deletion
7. **User deletion**: TeamMember.user set to NULL (preserves team membership record)
8. **Card deletion**: CardAssignment deletion (CASCADE)

