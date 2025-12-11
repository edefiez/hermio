<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\SocialProfile;
use App\Validator\Constraints\SocialProfileValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * Test suite for SocialProfileValidator
 * Tests platform-specific URL validation
 */
class SocialProfileValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SocialProfileValidator
    {
        return new SocialProfileValidator();
    }

    public function testValidLinkedInUrls(): void
    {
        $constraint = new SocialProfile(platform: 'linkedin');
        
        $validUrls = [
            'https://linkedin.com/in/johndoe',
            'https://www.linkedin.com/in/jane-doe',
            'http://linkedin.com/company/acme',
            'https://linkedin.com/company/acme-corp',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testInvalidLinkedInUrl(): void
    {
        $constraint = new SocialProfile(platform: 'linkedin');
        
        $this->validator->validate('https://facebook.com/johndoe', $constraint);
        
        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'https://facebook.com/johndoe')
            ->setParameter('{{ platform }}', 'Linkedin')
            ->assertRaised();
    }

    public function testValidInstagramUrls(): void
    {
        $constraint = new SocialProfile(platform: 'instagram');
        
        $validUrls = [
            'https://instagram.com/johndoe',
            'https://www.instagram.com/jane_doe',
            'http://instagram.com/user.name123',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testInvalidInstagramUrl(): void
    {
        $constraint = new SocialProfile(platform: 'instagram');
        
        $this->validator->validate('https://tiktok.com/@johndoe', $constraint);
        
        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'https://tiktok.com/@johndoe')
            ->setParameter('{{ platform }}', 'Instagram')
            ->assertRaised();
    }

    public function testValidTikTokUrls(): void
    {
        $constraint = new SocialProfile(platform: 'tiktok');
        
        $validUrls = [
            'https://tiktok.com/@johndoe',
            'https://www.tiktok.com/@jane_doe',
            'http://tiktok.com/@user.name',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testInvalidTikTokUrl(): void
    {
        $constraint = new SocialProfile(platform: 'tiktok');
        
        $this->validator->validate('https://instagram.com/johndoe', $constraint);
        
        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'https://instagram.com/johndoe')
            ->setParameter('{{ platform }}', 'Tiktok')
            ->assertRaised();
    }

    public function testValidFacebookUrls(): void
    {
        $constraint = new SocialProfile(platform: 'facebook');
        
        $validUrls = [
            'https://facebook.com/johndoe',
            'https://www.facebook.com/jane.doe',
            'http://fb.com/username',
            'https://www.fb.com/page.name',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testValidXUrls(): void
    {
        $constraint = new SocialProfile(platform: 'x');
        
        $validUrls = [
            'https://x.com/johndoe',
            'https://www.x.com/janedoe',
            'https://twitter.com/username', // Twitter still valid for X
            'http://www.twitter.com/handle',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testValidSnapchatUrls(): void
    {
        $constraint = new SocialProfile(platform: 'snapchat');
        
        $validUrls = [
            'https://snapchat.com/johndoe',
            'https://www.snapchat.com/add/janedoe',
            'http://snapchat.com/add/user-name',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testValidPlanityUrls(): void
    {
        $constraint = new SocialProfile(platform: 'planity');
        
        $validUrls = [
            'https://planity.com/johndoe',
            'https://www.planity.com/salon-name',
            'http://planity.fr/salon',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testValidGenericUrls(): void
    {
        $constraint = new SocialProfile(platform: 'other');
        
        $validUrls = [
            'https://example.com/profile',
            'http://mysite.org/user',
            'https://custom-platform.io/johndoe',
        ];

        foreach ($validUrls as $url) {
            $this->validator->validate($url, $constraint);
            $this->assertNoViolation();
        }
    }

    public function testNullValueIsValid(): void
    {
        $constraint = new SocialProfile(platform: 'instagram');
        
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $constraint = new SocialProfile(platform: 'instagram');
        
        $this->validator->validate('', $constraint);
        $this->assertNoViolation();
    }

    public function testUnknownPlatformSkipsValidation(): void
    {
        $constraint = new SocialProfile(platform: 'unknown-platform');
        
        // Should not validate if platform is not recognized
        $this->validator->validate('https://any-url.com', $constraint);
        $this->assertNoViolation();
    }
}
