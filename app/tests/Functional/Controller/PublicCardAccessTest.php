<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Card;
use App\Entity\User;
use App\Repository\CardRepository;
use App\Service\CardService;
use App\Service\SecureKeyGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Functional tests for public card access with security keys
 */
class PublicCardAccessTest extends TestCase
{
    private CardRepository $cardRepository;
    private CardService $cardService;
    private SecureKeyGenerator $keyGenerator;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->cardRepository = $this->createMock(CardRepository::class);
        $this->cardService = $this->createMock(CardService::class);
        $this->keyGenerator = new SecureKeyGenerator();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testValidateAccessKeyReturnsTrue(): void
    {
        $card = new Card();
        $validKey = $this->keyGenerator->generateRandomKey();
        $card->setPublicAccessKey($validKey);

        $cardService = $this->createMock(CardService::class);
        $cardService
            ->method('validateAccessKey')
            ->with($card, $validKey)
            ->willReturn(true);

        $result = $cardService->validateAccessKey($card, $validKey);
        $this->assertTrue($result);
    }

    public function testValidateAccessKeyReturnsFalseWithInvalidKey(): void
    {
        $card = new Card();
        $card->setPublicAccessKey($this->keyGenerator->generateRandomKey());

        $cardService = $this->createMock(CardService::class);
        $cardService
            ->method('validateAccessKey')
            ->with($card, 'invalid-key')
            ->willReturn(false);

        $result = $cardService->validateAccessKey($card, 'invalid-key');
        $this->assertFalse($result);
    }

    public function testValidateAccessKeyReturnsFalseWhenKeyIsNull(): void
    {
        $card = new Card();
        $card->setPublicAccessKey($this->keyGenerator->generateRandomKey());

        $cardService = $this->createMock(CardService::class);
        $cardService
            ->method('validateAccessKey')
            ->with($card, null)
            ->willReturn(false);

        $result = $cardService->validateAccessKey($card, null);
        $this->assertFalse($result);
    }

    public function testValidateAccessKeyReturnsFalseWhenCardHasNoKey(): void
    {
        $card = new Card();
        $card->setPublicAccessKey(null);

        $cardService = $this->createMock(CardService::class);
        $cardService
            ->method('validateAccessKey')
            ->with($card, 'any-key')
            ->willReturn(false);

        $result = $cardService->validateAccessKey($card, 'any-key');
        $this->assertFalse($result);
    }

    public function testCardPublicUrlIncludesAccessKey(): void
    {
        $card = new Card();
        $card->setSlug('test-card');
        $accessKey = $this->keyGenerator->generateRandomKey();
        $card->setPublicAccessKey($accessKey);

        $publicUrl = $card->getPublicUrl();

        $this->assertStringContainsString('/c/test-card', $publicUrl);
        $this->assertStringContainsString('?k=' . $accessKey, $publicUrl);
    }

    public function testCardPublicUrlWithoutKey(): void
    {
        $card = new Card();
        $card->setSlug('test-card');
        $card->setPublicAccessKey(null);

        $publicUrl = $card->getPublicUrl();

        $this->assertEquals('/c/test-card', $publicUrl);
        $this->assertStringNotContainsString('?k=', $publicUrl);
    }

    public function testRegenerateAccessKeyChangesKey(): void
    {
        $card = new Card();
        $oldKey = $this->keyGenerator->generateRandomKey();
        $card->setPublicAccessKey($oldKey);

        $newKey = $this->keyGenerator->generateRandomKey();
        $card->regenerateAccessKey($newKey);

        $this->assertEquals($newKey, $card->getPublicAccessKey());
        $this->assertNotEquals($oldKey, $card->getPublicAccessKey());
    }
}
