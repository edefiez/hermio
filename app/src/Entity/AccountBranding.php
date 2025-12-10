<?php

namespace App\Entity;

use App\Repository\AccountBrandingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccountBrandingRepository::class)]
#[ORM\Table(name: 'account_branding')]
#[ORM\UniqueConstraint(name: 'account_branding_unique', columns: ['account_id'])]
#[ORM\HasLifecycleCallbacks]
class AccountBranding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Account::class, inversedBy: 'branding')]
    #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $primaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'branding.color.invalid_format'
    )]
    private ?string $secondaryColor = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right'],
        message: 'branding.logo.position.invalid'
    )]
    private ?string $logoPosition = 'top-left';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['small', 'medium', 'large'],
        message: 'branding.logo.size.invalid'
    )]
    private ?string $logoSize = 'medium';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customTemplate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): static
    {
        $this->account = $account;
        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(?string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getSecondaryColor(): ?string
    {
        return $this->secondaryColor;
    }

    public function setSecondaryColor(?string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;
        return $this;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    public function setLogoFilename(?string $logoFilename): static
    {
        $this->logoFilename = $logoFilename;
        return $this;
    }

    public function getLogoPosition(): ?string
    {
        return $this->logoPosition;
    }

    public function setLogoPosition(?string $logoPosition): static
    {
        $this->logoPosition = $logoPosition;
        return $this;
    }

    public function getLogoSize(): ?string
    {
        return $this->logoSize;
    }

    public function setLogoSize(?string $logoSize): static
    {
        $this->logoSize = $logoSize;
        return $this;
    }

    public function getCustomTemplate(): ?string
    {
        return $this->customTemplate;
    }

    public function setCustomTemplate(?string $customTemplate): static
    {
        $this->customTemplate = $customTemplate;
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

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}

