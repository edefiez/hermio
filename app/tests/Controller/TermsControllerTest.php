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

    public function testTermsPageRendersInFrench(): void
    {
        $client = static::createClient();
        
        // Request the terms page with French locale
        $client->request('GET', '/fr/terms');
        
        $response = $client->getResponse();
        
        // Assert successful response
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTermsPageRendersInEnglish(): void
    {
        $client = static::createClient();
        
        // Request the terms page with English locale
        $client->request('GET', '/en/terms');
        
        $response = $client->getResponse();
        
        // Assert successful response
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
