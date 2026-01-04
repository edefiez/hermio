<?php

namespace App\Entity;

use App\Repository\CardScanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CardScanRepository::class)]
#[ORM\Table(name: 'card_scans')]
#[ORM\Index(name: 'idx_card_scanned_at', columns: ['card_id', 'scanned_at'])]
#[ORM\Index(name: 'idx_scanned_at', columns: ['scanned_at'])]
#[ORM\HasLifecycleCallbacks]
class CardScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Card $card;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $scannedAt;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $country = null;

    public function __construct()
    {
        $this->scannedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): static
    {
        $this->card = $card;
        return $this;
    }

    public function getScannedAt(): \DateTimeInterface
    {
        return $this->scannedAt;
    }

    public function setScannedAt(\DateTimeInterface $scannedAt): static
    {
        $this->scannedAt = $scannedAt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }
}
