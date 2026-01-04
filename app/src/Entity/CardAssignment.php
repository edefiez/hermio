<?php

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function getTeamMember(): TeamMember
    {
        return $this->teamMember;
    }

    public function setTeamMember(?TeamMember $teamMember): static
    {
        $this->teamMember = $teamMember;
        return $this;
    }

    public function getAssignedAt(): \DateTimeInterface
    {
        return $this->assignedAt;
    }

    public function setAssignedAt(\DateTimeInterface $assignedAt): static
    {
        $this->assignedAt = $assignedAt;
        return $this;
    }

    public function getAssignedBy(): User
    {
        return $this->assignedBy;
    }

    public function setAssignedBy(User $assignedBy): static
    {
        $this->assignedBy = $assignedBy;
        return $this;
    }
}

