<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'An account with this email already exists')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isEmailVerified = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\OneToMany(targetEntity: EmailVerificationToken::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $emailVerificationTokens;

    #[ORM\OneToMany(targetEntity: PasswordResetToken::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $passwordResetTokens;

    #[ORM\OneToMany(targetEntity: AuthenticationLog::class, mappedBy: 'user')]
    private Collection $authenticationLogs;

    #[ORM\OneToOne(targetEntity: Account::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Account $account = null;

    #[ORM\OneToOne(targetEntity: StripeCustomer::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?StripeCustomer $stripeCustomer = null;

    #[ORM\OneToOne(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Subscription $subscription = null;

    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $payments;

    #[ORM\OneToMany(targetEntity: Card::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $cards;

    public function __construct()
    {
        $this->emailVerificationTokens = new ArrayCollection();
        $this->passwordResetTokens = new ArrayCollection();
        $this->authenticationLogs = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->cards = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): static
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, EmailVerificationToken>
     */
    public function getEmailVerificationTokens(): Collection
    {
        return $this->emailVerificationTokens;
    }

    public function addEmailVerificationToken(EmailVerificationToken $emailVerificationToken): static
    {
        if (!$this->emailVerificationTokens->contains($emailVerificationToken)) {
            $this->emailVerificationTokens->add($emailVerificationToken);
            $emailVerificationToken->setUser($this);
        }

        return $this;
    }

    public function removeEmailVerificationToken(EmailVerificationToken $emailVerificationToken): static
    {
        if ($this->emailVerificationTokens->removeElement($emailVerificationToken)) {
            // set the owning side to null (unless already changed)
            if ($emailVerificationToken->getUser() === $this) {
                $emailVerificationToken->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PasswordResetToken>
     */
    public function getPasswordResetTokens(): Collection
    {
        return $this->passwordResetTokens;
    }

    public function addPasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if (!$this->passwordResetTokens->contains($passwordResetToken)) {
            $this->passwordResetTokens->add($passwordResetToken);
            $passwordResetToken->setUser($this);
        }

        return $this;
    }

    public function removePasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if ($this->passwordResetTokens->removeElement($passwordResetToken)) {
            // set the owning side to null (unless already changed)
            if ($passwordResetToken->getUser() === $this) {
                $passwordResetToken->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AuthenticationLog>
     */
    public function getAuthenticationLogs(): Collection
    {
        return $this->authenticationLogs;
    }

    public function addAuthenticationLog(AuthenticationLog $authenticationLog): static
    {
        if (!$this->authenticationLogs->contains($authenticationLog)) {
            $this->authenticationLogs->add($authenticationLog);
            $authenticationLog->setUser($this);
        }

        return $this;
    }

    public function removeAuthenticationLog(AuthenticationLog $authenticationLog): static
    {
        if ($this->authenticationLogs->removeElement($authenticationLog)) {
            // set the owning side to null (unless already changed)
            if ($authenticationLog->getUser() === $this) {
                $authenticationLog->setUser(null);
            }
        }

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): static
    {
        $this->account = $account;
        $account->setUser($this);
        return $this;
    }

    public function getStripeCustomer(): ?StripeCustomer
    {
        return $this->stripeCustomer;
    }

    public function setStripeCustomer(?StripeCustomer $stripeCustomer): static
    {
        $this->stripeCustomer = $stripeCustomer;
        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): static
    {
        if (!$this->cards->contains($card)) {
            $this->cards->add($card);
            $card->setUser($this);
        }

        return $this;
    }

    public function removeCard(Card $card): static
    {
        if ($this->cards->removeElement($card)) {
            if ($card->getUser() === $this) {
                $card->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getActiveCards(): Collection
    {
        return $this->cards->filter(fn(Card $card) => $card->getStatus() === 'active');
    }

    /**
     * Check if user is on Enterprise plan
     */
    public function isEnterprise(): bool
    {
        return $this->account?->getPlanType() === \App\Enum\PlanType::ENTERPRISE;
    }
}
