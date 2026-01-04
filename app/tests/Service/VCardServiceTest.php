<?php

namespace App\Tests\Service;

use App\Entity\Card;
use App\Entity\User;
use App\Service\VCardService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Test suite for VCardService
 * Tests vCard 4.0 generation with social profiles
 */
class VCardServiceTest extends TestCase
{
    private VCardService $service;
    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->service = new VCardService($this->cache);
    }

    public function testGenerateBasicCard(): void
    {
        $card = $this->createCard([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1-555-0100',
            'company' => 'Acme Corp',
            'title' => 'Software Engineer',
            'website' => 'https://johndoe.com',
            'bio' => 'Passionate developer',
        ]);

        $vcard = $this->service->generate($card);

        $this->assertStringContainsString('BEGIN:VCARD', $vcard);
        $this->assertStringContainsString('VERSION:4.0', $vcard);
        $this->assertStringContainsString('FN:John Doe', $vcard);
        $this->assertStringContainsString('EMAIL', $vcard);
        $this->assertStringContainsString('john.doe@example.com', $vcard);
        $this->assertStringContainsString('TEL', $vcard);
        $this->assertStringContainsString('+1-555-0100', $vcard);
        $this->assertStringContainsString('ORG:Acme Corp', $vcard);
        $this->assertStringContainsString('TITLE:Software Engineer', $vcard);
        $this->assertStringContainsString('URL', $vcard);
        $this->assertStringContainsString('https://johndoe.com', $vcard);
        $this->assertStringContainsString('NOTE:Passionate developer', $vcard);
        $this->assertStringContainsString('END:VCARD', $vcard);
    }

    public function testGenerateCardWithSocialProfiles(): void
    {
        $card = $this->createCard([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'linkedin' => 'https://linkedin.com/in/janesmith',
            'twitter' => 'https://twitter.com/janesmith',
            'social' => [
                'instagram' => 'https://instagram.com/janesmith',
                'tiktok' => 'https://tiktok.com/@janesmith',
                'facebook' => 'https://facebook.com/janesmith',
                'x' => 'https://x.com/janesmith',
                'snapchat' => 'https://snapchat.com/add/janesmith',
                'planity' => 'https://planity.com/janesmith',
                'bluebirds' => 'https://bluebirds.app/janesmith',
                'other' => 'https://example.com/janesmith',
            ],
        ]);

        $vcard = $this->service->generate($card);

        // Check legacy social fields
        $this->assertStringContainsString('X-SOCIALPROFILE', $vcard);
        $this->assertStringContainsString('linkedin.com/in/janesmith', $vcard);
        $this->assertStringContainsString('twitter.com/janesmith', $vcard);

        // Check new social fields (known platforms)
        $this->assertStringContainsString('instagram.com/janesmith', $vcard);
        $this->assertStringContainsString('tiktok.com/@janesmith', $vcard);
        $this->assertStringContainsString('facebook.com/janesmith', $vcard);
        $this->assertStringContainsString('x.com/janesmith', $vcard);
        $this->assertStringContainsString('snapchat.com/add/janesmith', $vcard);

        // Check other platforms (should use URL with TYPE=social)
        $this->assertStringContainsString('planity.com/janesmith', $vcard);
        $this->assertStringContainsString('bluebirds.app/janesmith', $vcard);
        $this->assertStringContainsString('example.com/janesmith', $vcard);
    }

    public function testGenerateFilename(): void
    {
        $card = $this->createCard(['name' => 'John Doe']);
        $filename = $this->service->generateFilename($card);
        
        $this->assertEquals('contact-john-doe.vcf', $filename);
    }

    public function testGenerateFilenameNormalizeSpecialCharacters(): void
    {
        $card = $this->createCard(['name' => 'Jean-François O\'Reilly']);
        $filename = $this->service->generateFilename($card);
        
        $this->assertEquals('contact-jean-francois-o-reilly.vcf', $filename);
    }

    public function testGenerateFilenameDefaultsToContact(): void
    {
        $card = $this->createCard(['name' => '']);
        $filename = $this->service->generateFilename($card);
        
        $this->assertEquals('contact-contact.vcf', $filename);
    }

    public function testCacheBehavior(): void
    {
        $card = $this->createCard(['name' => 'Test User', 'email' => 'test@example.com']);
        
        // First call - should generate and cache
        $vcard1 = $this->service->generate($card);
        
        // Second call - should return cached version
        $vcard2 = $this->service->generate($card);
        
        $this->assertEquals($vcard1, $vcard2);
        
        // Verify cache was used
        $cacheKey = 'vcard_' . $card->getId();
        $cachedItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cachedItem->isHit());
    }

    /**
     * Helper method to create a Card entity for testing
     */
    private function createCard(array $content): Card
    {
        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getContent'])
            ->getMock();
        
        $card->method('getId')->willReturn(1);
        $card->method('getContent')->willReturn($content);
        
        return $card;
    }
}
