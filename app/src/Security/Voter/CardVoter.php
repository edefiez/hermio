<?php

namespace App\Security\Voter;

use App\Entity\Card;
use App\Entity\User;
use App\Service\CardService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CardVoter extends Voter
{
    public const VIEW = 'VIEW';

    public function __construct(
        private CardService $cardService
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Card;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Card $card */
        $card = $subject;

        return $this->cardService->canAccessCard($card, $user);
    }
}
