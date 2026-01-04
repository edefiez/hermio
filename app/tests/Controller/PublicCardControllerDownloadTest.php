<?php

namespace App\Tests\Controller;

use App\Entity\Card;
use App\Repository\CardRepository;
use App\Service\VCardService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test suite for PublicCardController download functionality
 * Tests vCard download endpoint
 */
class PublicCardControllerDownloadTest extends WebTestCase
{
    public function testSuccessfulDownload(): void
    {
        $client = static::createClient();
        
        // Mock the CardRepository to return a test card
        $cardRepository = $this->createMock(CardRepository::class);
        $card = $this->createTestCard();
        $cardRepository->method('findOneBySlug')->willReturn($card);
        
        // Replace the service in the container
        $container = $client->getContainer();
        $container->set(CardRepository::class, $cardRepository);
        
        // Make request to download endpoint
        $client->request('GET', '/c/test-card/download');
        
        $response = $client->getResponse();
        
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('text/vcard; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.vcf', $response->headers->get('Content-Disposition'));
        
        // Verify vCard content
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCARD', $content);
        $this->assertStringContainsString('VERSION:4.0', $content);
        $this->assertStringContainsString('END:VCARD', $content);
    }

    public function testDownloadNonExistentCard(): void
    {
        $client = static::createClient();
        
        // Mock the CardRepository to return null (card not found)
        $cardRepository = $this->createMock(CardRepository::class);
        $cardRepository->method('findOneBySlug')->willReturn(null);
        
        // Replace the service in the container
        $container = $client->getContainer();
        $container->set(CardRepository::class, $cardRepository);
        
        // Make request to download endpoint
        $client->request('GET', '/c/non-existent-card/download');
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testCorrectContentTypeHeader(): void
    {
        $client = static::createClient();
        
        $cardRepository = $this->createMock(CardRepository::class);
        $card = $this->createTestCard();
        $cardRepository->method('findOneBySlug')->willReturn($card);
        
        $container = $client->getContainer();
        $container->set(CardRepository::class, $cardRepository);
        
        $client->request('GET', '/c/test-card/download');
        
        $response = $client->getResponse();
        $this->assertEquals('text/vcard; charset=utf-8', $response->headers->get('Content-Type'));
    }

    public function testCorrectContentDispositionHeader(): void
    {
        $client = static::createClient();
        
        $cardRepository = $this->createMock(CardRepository::class);
        $card = $this->createTestCard();
        $cardRepository->method('findOneBySlug')->willReturn($card);
        
        $container = $client->getContainer();
        $container->set(CardRepository::class, $cardRepository);
        
        $client->request('GET', '/c/test-card/download');
        
        $response = $client->getResponse();
        $contentDisposition = $response->headers->get('Content-Disposition');
        
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('filename=', $contentDisposition);
        $this->assertStringContainsString('.vcf', $contentDisposition);
    }

    public function testVCardContentIsValid(): void
    {
        $client = static::createClient();
        
        $cardRepository = $this->createMock(CardRepository::class);
        $card = $this->createTestCard();
        $cardRepository->method('findOneBySlug')->willReturn($card);
        
        $container = $client->getContainer();
        $container->set(CardRepository::class, $cardRepository);
        
        $client->request('GET', '/c/test-card/download');
        
        $content = $client->getResponse()->getContent();
        
        // Verify required vCard 4.0 fields
        $this->assertStringContainsString('BEGIN:VCARD', $content);
        $this->assertStringContainsString('VERSION:4.0', $content);
        $this->assertStringContainsString('FN:', $content); // Full Name is required
        $this->assertStringContainsString('END:VCARD', $content);
        
        // Verify test card data
        $this->assertStringContainsString('Test User', $content);
        $this->assertStringContainsString('test@example.com', $content);
    }

    /**
     * Helper method to create a test Card entity
     */
    private function createTestCard(): Card
    {
        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getContent', 'getSlug'])
            ->getMock();
        
        $card->method('getId')->willReturn(1);
        $card->method('getSlug')->willReturn('test-card');
        $card->method('getContent')->willReturn([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+1-555-0100',
            'company' => 'Test Company',
            'title' => 'Test Title',
        ]);
        
        return $card;
    }
}
