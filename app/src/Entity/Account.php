<?php

namespace App\Entity;

use App\Enum\PlanType;
use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'user_account_unique', columns: ['user_id'])]
#[ORM\HasLifecycleCallbacks]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'account')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PlanType::class)]
    #[Assert\NotBlank]
    private PlanType $planType = PlanType::FREE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $updatedBy = null;

    #[ORM\OneToOne(targetEntity: AccountBranding::class, mappedBy: 'account', cascade: ['persist', 'remove'])]
    private ?AccountBranding $branding = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPlanType(): PlanType
    {
        return $this->planType;
    }

    public function setPlanType(PlanType $planType): static
    {
        $this->planType = $planType;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getBranding(): ?AccountBranding
    {
        return $this->branding;
    }

    public function setBranding(?AccountBranding $branding): static
    {
        if ($branding === null) {
            if ($this->branding !== null) {
                $this->branding->setAccount(null);
            }
            $this->branding = null;
        } else {
            $branding->setAccount($this);
            $this->branding = $branding;
        }
        return $this;
    }
}

