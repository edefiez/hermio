<?php

namespace App\Entity;

use App\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): TeamRole
    {
        return $this->role;
    }

    public function setRole(TeamRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getInvitationStatus(): string
    {
        return $this->invitationStatus;
    }

    public function setInvitationStatus(string $invitationStatus): static
    {
        $this->invitationStatus = $invitationStatus;
        return $this;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;
        return $this;
    }

    public function getInvitationExpiresAt(): ?\DateTimeInterface
    {
        return $this->invitationExpiresAt;
    }

    public function setInvitationExpiresAt(?\DateTimeInterface $invitationExpiresAt): static
    {
        $this->invitationExpiresAt = $invitationExpiresAt;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(?\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLastActivityAt(): ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): static
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    /**
     * @return Collection<int, CardAssignment>
     */
    public function getCardAssignments(): Collection
    {
        return $this->cardAssignments;
    }

    public function addCardAssignment(CardAssignment $cardAssignment): static
    {
        if (!$this->cardAssignments->contains($cardAssignment)) {
            $this->cardAssignments->add($cardAssignment);
            $cardAssignment->setTeamMember($this);
        }
        return $this;
    }

    public function removeCardAssignment(CardAssignment $cardAssignment): static
    {
        if ($this->cardAssignments->removeElement($cardAssignment)) {
            // set the owning side to null (unless already changed)
            if ($cardAssignment->getTeamMember() === $this) {
                $cardAssignment->setTeamMember(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

