<?php

namespace App\Tests\Unit\Service;

use App\Service\SecureKeyGenerator;
use PHPUnit\Framework\TestCase;

class SecureKeyGeneratorTest extends TestCase
{
    private SecureKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SecureKeyGenerator();
    }

    public function testGenerateRandomKeyDefaultLength(): void
    {
        $key = $this->generator->generateRandomKey();
        
        $this->assertEquals(48, strlen($key));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key);
    }

    public function testGenerateRandomKeyCustomLength(): void
    {
        $lengths = [32, 48, 64, 96];
        
        foreach ($lengths as $length) {
            $key = $this->generator->generateRandomKey($length);
            $this->assertEquals($length, strlen($key));
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key);
        }
    }

    public function testGenerateRandomKeyUniqueness(): void
    {
        $keys = [];
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $keys[] = $this->generator->generateRandomKey();
        }
        
        // All keys should be unique
        $uniqueKeys = array_unique($keys);
        $this->assertCount($iterations, $uniqueKeys);
    }

    public function testGenerateRandomKeyIsUrlSafe(): void
    {
        $key = $this->generator->generateRandomKey();
        
        // Should not contain characters that need URL encoding
        $this->assertStringNotContainsString('+', $key);
        $this->assertStringNotContainsString('/', $key);
        $this->assertStringNotContainsString('=', $key);
    }

    public function testGenerateDerivedKey(): void
    {
        $secret = 'test-secret-key';
        $data = 'user_1:card_42:2024-01-01';
        
        $key1 = $this->generator->generateDerivedKey($secret, $data);
        $key2 = $this->generator->generateDerivedKey($secret, $data);
        
        // Same input should produce same key (deterministic)
        $this->assertEquals($key1, $key2);
        $this->assertEquals(43, strlen($key1)); // SHA-256 base64url is 43 chars
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $key1);
    }

    public function testGenerateDerivedKeyDifferentInputs(): void
    {
        $secret = 'test-secret-key';
        
        $key1 = $this->generator->generateDerivedKey($secret, 'user_1:card_1:2024-01-01');
        $key2 = $this->generator->generateDerivedKey($secret, 'user_1:card_2:2024-01-01');
        $key3 = $this->generator->generateDerivedKey($secret, 'user_2:card_1:2024-01-01');
        
        // Different inputs should produce different keys
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }

    public function testGenerateDerivedKeyDifferentSecrets(): void
    {
        $data = 'user_1:card_1:2024-01-01';
        
        $key1 = $this->generator->generateDerivedKey('secret1', $data);
        $key2 = $this->generator->generateDerivedKey('secret2', $data);
        
        // Different secrets should produce different keys
        $this->assertNotEquals($key1, $key2);
    }
}
