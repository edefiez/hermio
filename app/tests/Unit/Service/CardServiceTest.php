<?php

namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Enum\PlanType;
use App\Exception\QuotaExceededException;
use App\Repository\CardAssignmentRepository;
use App\Repository\CardRepository;
use App\Repository\TeamMemberRepository;
use App\Service\CardService;
use App\Service\QuotaService;
use App\Service\SecureKeyGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CardServiceTest extends TestCase
{
    private CardRepository $cardRepository;
    private QuotaService $quotaService;
    private EntityManagerInterface $entityManager;
    private TeamMemberRepository $teamMemberRepository;
    private CardAssignmentRepository $cardAssignmentRepository;
    private SecureKeyGenerator $secureKeyGenerator;
    private CardService $cardService;

    protected function setUp(): void
    {
        $this->cardRepository = $this->createMock(CardRepository::class);
        $this->quotaService = $this->createMock(QuotaService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        $this->cardAssignmentRepository = $this->createMock(CardAssignmentRepository::class);
        $this->secureKeyGenerator = $this->createMock(SecureKeyGenerator::class);
        
        $this->cardService = new CardService(
            $this->cardRepository,
            $this->quotaService,
            $this->entityManager,
            $this->teamMemberRepository,
            $this->cardAssignmentRepository,
            $this->secureKeyGenerator
        );
    }

    public function testCreateCardThrowsExceptionWhenQuotaExceeded(): void
    {
        $card = $this->createMock(Card::class);
        $card->method('getContent')->willReturn(['name' => 'Test Card']);
        
        $user = $this->createMock(User::class);

        $this->quotaService
            ->expects($this->once())
            ->method('validateQuota')
            ->with($user)
            ->willThrowException(new QuotaExceededException('Quota exceeded'));

        $this->expectException(QuotaExceededException::class);
        
        $this->cardService->createCard($card, $user);
    }

    public function testCreateCardGeneratesUniqueSlug(): void
    {
        $card = $this->createMock(Card::class);
        $card->method('getContent')->willReturn(['name' => 'Test Card']);
        $card->expects($this->once())->method('setSlug')->with($this->isType('string'));
        $card->expects($this->once())->method('setUser')->with($this->isInstanceOf(User::class));
        $card->expects($this->once())->method('setStatus')->with('active');
        
        $user = $this->createMock(User::class);

        $this->quotaService->method('validateQuota')->willReturnCallback(function() {
            // validateQuota returns void, just don't throw
        });
        $this->cardRepository->method('slugExists')->willReturn(false);
        
        $this->entityManager->expects($this->once())->method('persist')->with($card);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->cardService->createCard($card, $user);
        
        $this->assertInstanceOf(Card::class, $result);
    }

    public function testCanAccessCardReturnsTrueForOwner(): void
    {
        $card = $this->createMock(Card::class);
        $user = $this->createMock(User::class);
        
        $card->method('getUser')->willReturn($user);

        $this->assertTrue($this->cardService->canAccessCard($card, $user));
    }

    public function testCanAccessCardReturnsFalseForNonEnterpriseUser(): void
    {
        $card = $this->createMock(Card::class);
        $owner = $this->createMock(User::class);
        $user = $this->createMock(User::class);
        
        $card->method('getUser')->willReturn($owner);
        
        $account = $this->createMock(\App\Entity\Account::class);
        $account->method('getPlanType')->willReturn(PlanType::FREE);
        
        $user->method('getAccount')->willReturn($account);

        $this->assertFalse($this->cardService->canAccessCard($card, $user));
    }

    public function testUpdateCardFlushesEntityManager(): void
    {
        $card = $this->createMock(Card::class);
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->cardService->updateCard($card);
    }

    public function testDeleteCardCallsDeleteMethodAndFlushes(): void
    {
        $card = $this->createMock(Card::class);
        // Card::delete() is a method that sets deletedAt and status
        $card->expects($this->once())->method('delete');
        
        $this->entityManager->expects($this->once())->method('flush');

        $this->cardService->deleteCard($card);
    }

    public function testValidateAccessKeyReturnsTrueWithValidKey(): void
    {
        $card = new Card();
        $validKey = 'test-secure-key-123456789';
        $card->setPublicAccessKey($validKey);

        $result = $this->cardService->validateAccessKey($card, $validKey);

        $this->assertTrue($result);
    }

    public function testValidateAccessKeyReturnsFalseWithInvalidKey(): void
    {
        $card = new Card();
        $card->setPublicAccessKey('correct-key-123456789');

        $result = $this->cardService->validateAccessKey($card, 'wrong-key');

        $this->assertFalse($result);
    }

    public function testValidateAccessKeyReturnsFalseWhenKeyIsNull(): void
    {
        $card = new Card();
        $card->setPublicAccessKey('some-key-123456789');

        $result = $this->cardService->validateAccessKey($card, null);

        $this->assertFalse($result);
    }

    public function testValidateAccessKeyReturnsFalseWhenCardHasNoKey(): void
    {
        $card = new Card();
        $card->setPublicAccessKey(null);

        $result = $this->cardService->validateAccessKey($card, 'any-key');

        $this->assertFalse($result);
    }

    public function testRegenerateCardAccessKeyGeneratesNewKey(): void
    {
        $card = new Card();
        $newKey = 'new-generated-key-123456789';
        
        $this->secureKeyGenerator
            ->expects($this->once())
            ->method('generateRandomKey')
            ->willReturn($newKey);

        $this->entityManager->expects($this->once())->method('flush');

        $this->cardService->regenerateCardAccessKey($card);

        $this->assertEquals($newKey, $card->getPublicAccessKey());
    }
}


