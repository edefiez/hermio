<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Card;
use App\Entity\User;
use App\Enum\PlanType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for QR Code download functionality
 */
class CardQrCodeControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testDownloadPngFormatAsOwner(): void
    {
        $client = static::createClient();
        
        // Create test user with card
        $user = $this->createTestUserWithCard(PlanType::FREE);
        $card = $user->getCards()->first();
        
        // Login as user
        $client->loginUser($user);
        
        // Request PNG download
        $client->request('GET', sprintf('/cards/%d/qr-code?format=png', $card->getId()));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
        $this->assertResponseHasHeader('Content-Disposition');
    }

    public function testDownloadSvgFormatAsOwner(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::PRO);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=svg', $card->getId()));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/svg+xml');
    }

    public function testDownloadPdfFormatAsOwner(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::PRO);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=pdf', $card->getId()));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
    }

    public function testDownloadEpsFormatRequiresEnterprisePlan(): void
    {
        $client = static::createClient();
        
        // Test with FREE plan
        $user = $this->createTestUserWithCard(PlanType::FREE);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=eps', $card->getId()));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDownloadEpsFormatWithEnterprisePlan(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::ENTERPRISE);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=eps', $card->getId()));
        
        // EPS conversion might fail if Imagick/Inkscape not available, but access should be granted
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR,
            'Enterprise user should be allowed to access EPS format'
        );
    }

    public function testDownloadInvalidFormatReturnsNotFound(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::FREE);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=invalid', $card->getId()));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadDefaultsToPlainPngFormat(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::FREE);
        $card = $user->getCards()->first();
        
        $client->loginUser($user);
        
        // Request without format parameter
        $client->request('GET', sprintf('/cards/%d/qr-code', $card->getId()));
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');
    }

    public function testDownloadRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $user = $this->createTestUserWithCard(PlanType::FREE);
        $card = $user->getCards()->first();
        
        // Don't login - request should be redirected or forbidden
        $client->request('GET', sprintf('/cards/%d/qr-code?format=png', $card->getId()));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    public function testDownloadDeniedForNonOwner(): void
    {
        $client = static::createClient();
        
        // Create owner and their card
        $owner = $this->createTestUserWithCard(PlanType::FREE);
        $card = $owner->getCards()->first();
        
        // Create another user
        $otherUser = $this->createTestUser('other@example.com', PlanType::FREE);
        
        // Login as other user
        $client->loginUser($otherUser);
        
        $client->request('GET', sprintf('/cards/%d/qr-code?format=png', $card->getId()));
        
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createTestUserWithCard(PlanType $planType): User
    {
        $user = new User();
        $user->setEmail('test-' . uniqid() . '@example.com');
        $user->setPassword('password');
        $user->setIsEmailVerified(true);
        $user->setStatus('active');
        
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType($planType);
        $user->setAccount($account);
        
        $card = new Card();
        $card->setUser($user);
        $card->setSlug('test-card-' . uniqid());
        $card->setContent(['name' => 'Test Card']);
        $card->setStatus('active');
        $card->setPublicAccessKey(bin2hex(random_bytes(16)));
        
        $this->entityManager->persist($user);
        $this->entityManager->persist($account);
        $this->entityManager->persist($card);
        $this->entityManager->flush();
        
        return $user;
    }

    private function createTestUser(string $email, PlanType $planType): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setIsEmailVerified(true);
        $user->setStatus('active');
        
        $account = new Account();
        $account->setUser($user);
        $account->setPlanType($planType);
        $user->setAccount($account);
        
        $this->entityManager->persist($user);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        
        return $user;
    }
}
