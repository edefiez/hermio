<?php

namespace App\Tests\Form;

use App\Entity\Card;
use App\Form\CardFormType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Test suite for CardFormType
 * Tests form submission with new social network fields
 */
class CardFormTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1-555-0100',
            'company' => 'Acme Corp',
            'title' => 'Software Engineer',
            'bio' => 'Passionate developer',
            'website' => 'https://johndoe.com',
            'linkedin' => 'https://linkedin.com/in/johndoe',
            'twitter' => 'https://twitter.com/johndoe',
            'instagram' => 'https://instagram.com/johndoe',
            'tiktok' => 'https://tiktok.com/@johndoe',
            'facebook' => 'https://facebook.com/johndoe',
            'x' => 'https://x.com/johndoe',
            'bluebirds' => 'https://bluebirds.app/johndoe',
            'snapchat' => 'https://snapchat.com/add/johndoe',
            'planity' => 'https://planity.com/johndoe',
            'other' => 'https://example.com/johndoe',
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        // Verify that social fields are mapped to content['social']
        $content = $card->getContent();
        $this->assertIsArray($content);
        $this->assertArrayHasKey('social', $content);
        $this->assertEquals('https://instagram.com/johndoe', $content['social']['instagram']);
        $this->assertEquals('https://tiktok.com/@johndoe', $content['social']['tiktok']);
        $this->assertEquals('https://facebook.com/johndoe', $content['social']['facebook']);
        $this->assertEquals('https://x.com/johndoe', $content['social']['x']);
        $this->assertEquals('https://bluebirds.app/johndoe', $content['social']['bluebirds']);
        $this->assertEquals('https://snapchat.com/add/johndoe', $content['social']['snapchat']);
        $this->assertEquals('https://planity.com/johndoe', $content['social']['planity']);
        $this->assertEquals('https://example.com/johndoe', $content['social']['other']);
    }

    public function testEmptySocialFieldsAreRemoved(): void
    {
        $formData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'instagram' => 'https://instagram.com/janedoe',
            'tiktok' => '', // Empty field should be removed
            'facebook' => '',
            'x' => '',
            'bluebirds' => '',
            'snapchat' => '',
            'planity' => '',
            'other' => '',
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertTrue($form->isValid());

        $content = $card->getContent();
        $this->assertArrayHasKey('social', $content);
        $this->assertArrayHasKey('instagram', $content['social']);
        
        // Empty fields should not be in the array
        $this->assertArrayNotHasKey('tiktok', $content['social']);
        $this->assertArrayNotHasKey('facebook', $content['social']);
        $this->assertArrayNotHasKey('x', $content['social']);
    }

    public function testInvalidInstagramUrl(): void
    {
        $formData = [
            'name' => 'Test User',
            'instagram' => 'https://facebook.com/notinstagram', // Invalid Instagram URL
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('instagram')->getErrors()->count() > 0);
    }

    public function testInvalidTikTokUrl(): void
    {
        $formData = [
            'name' => 'Test User',
            'tiktok' => 'https://instagram.com/nottiktok', // Invalid TikTok URL
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('tiktok')->getErrors()->count() > 0);
    }

    public function testInvalidFacebookUrl(): void
    {
        $formData = [
            'name' => 'Test User',
            'facebook' => 'https://twitter.com/notfacebook', // Invalid Facebook URL
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('facebook')->getErrors()->count() > 0);
    }

    public function testInvalidXUrl(): void
    {
        $formData = [
            'name' => 'Test User',
            'x' => 'https://instagram.com/notx', // Invalid X URL
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('x')->getErrors()->count() > 0);
    }

    public function testAllSocialFieldsOptional(): void
    {
        $formData = [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            // No social fields provided
        ];

        $card = new Card();
        $form = $this->factory->create(CardFormType::class, $card);

        $form->submit($formData);

        $this->assertTrue($form->isValid());
    }
}
