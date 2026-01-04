<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\User;
use App\Enum\PlanType;
use App\Security\Voter\CardVoter;
use App\Service\CardService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CardVoterTest extends TestCase
{
    private CardService $cardService;
    private CardVoter $voter;

    protected function setUp(): void
    {
        $this->cardService = $this->createMock(CardService::class);
        $this->voter = new CardVoter($this->cardService);
    }

    public function testVoteGrantsAccessWhenUserCanAccessCard(): void
    {
        $user = $this->createMockUser();
        $card = $this->createMockCard();
        $token = $this->createMockToken($user);

        $this->cardService
            ->expects($this->once())
            ->method('canAccessCard')
            ->with($card, $user)
            ->willReturn(true);

        $result = $this->voter->vote($token, $card, [CardVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteDeniesAccessWhenUserCannotAccessCard(): void
    {
        $user = $this->createMockUser();
        $card = $this->createMockCard();
        $token = $this->createMockToken($user);

        $this->cardService
            ->expects($this->once())
            ->method('canAccessCard')
            ->with($card, $user)
            ->willReturn(false);

        $result = $this->voter->vote($token, $card, [CardVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteAbstainsWhenSubjectIsNotCard(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $notACard = new \stdClass();

        $result = $this->voter->vote($token, $notACard, [CardVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteAbstainsWhenAttributeIsNotView(): void
    {
        $user = $this->createMockUser();
        $card = $this->createMockCard();
        $token = $this->createMockToken($user);

        $result = $this->voter->vote($token, $card, ['EDIT']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteDeniesAccessWhenUserIsNotAuthenticated(): void
    {
        $card = $this->createMockCard();
        $token = $this->createMockToken(null);

        $result = $this->voter->vote($token, $card, [CardVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createMockUser(): User
    {
        $user = $this->createMock(User::class);
        $account = $this->createMock(Account::class);
        $account->method('getPlanType')->willReturn(PlanType::FREE);
        $user->method('getAccount')->willReturn($account);
        
        return $user;
    }

    private function createMockCard(): Card
    {
        return $this->createMock(Card::class);
    }

    private function createMockToken(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        return $token;
    }
}
