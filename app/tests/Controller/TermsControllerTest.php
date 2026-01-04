<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test suite for TermsController
 * Tests CGU/Terms and Conditions page accessibility
 */
class TermsControllerTest extends WebTestCase
{
    public function testTermsPageIsAccessible(): void
    {
        $client = static::createClient();
        
        // Request the terms page
        $client->request('GET', '/terms');
        
        $response = $client->getResponse();
        
        // Assert successful response
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Verify the page contains expected content
        $content = $response->getContent();
        $this->assertNotEmpty($content);
    }

    public function testTermsPageContainsTitle(): void
    {
        $client = static::createClient();
        
        // Request the terms page
        $crawler = $client->request('GET', '/terms');
        
        $response = $client->getResponse();
        
        // Assert successful response
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Verify the page contains an h1 title
        $this->assertGreaterThan(0, $crawler->filter('h1')->count());
    }
}
