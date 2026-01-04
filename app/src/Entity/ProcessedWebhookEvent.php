<?php

namespace App\Entity;

use App\Repository\ProcessedWebhookEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcessedWebhookEventRepository::class)]
#[ORM\Table(name: 'processed_webhook_events')]
#[ORM\UniqueConstraint(name: 'stripe_event_id_unique', columns: ['stripe_event_id'])]
class ProcessedWebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $stripeEventId;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $eventType;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $processedAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $success;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripeEventId(): string
    {
        return $this->stripeEventId;
    }

    public function setStripeEventId(string $stripeEventId): static
    {
        $this->stripeEventId = $stripeEventId;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getProcessedAt(): \DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(\DateTimeInterface $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }
}

