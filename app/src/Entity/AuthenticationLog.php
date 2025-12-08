<?php

namespace App\Entity;

use App\Repository\AuthenticationLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthenticationLogRepository::class)]
#[ORM\Table(name: 'authentication_logs')]
#[ORM\Index(columns: ['event_type', 'timestamp'], name: 'idx_event_timestamp')]
#[ORM\Index(columns: ['user_id', 'timestamp'], name: 'idx_user_timestamp')]
class AuthenticationLog
{
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_LOGIN_FAILURE = 'login_failure';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_REGISTRATION = 'registration';
    public const EVENT_EMAIL_VERIFIED = 'email_verified';
    public const EVENT_PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    public const EVENT_PASSWORD_RESET_COMPLETED = 'password_reset_completed';
    public const EVENT_PASSWORD_CHANGED = 'password_changed';
    public const EVENT_ACCOUNT_LOCKED = 'account_locked';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $eventType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 500)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $successful = false;

    #[ORM\ManyToOne(inversedBy: 'authenticationLogs')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function setSuccessful(bool $successful): static
    {
        $this->successful = $successful;

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
}
