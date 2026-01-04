<?php

namespace App\Tests\Unit\Service;

use App\Entity\Card;
use App\Service\QrCodeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class QrCodeServiceTest extends TestCase
{
    private QrCodeService $qrCodeService;

    protected function setUp(): void
    {
        $this->qrCodeService = new QrCodeService();
    }

    public function testGeneratePngFormat(): void
    {
        $card = $this->createMockCard(1, '/c/test-card');

        $file = $this->qrCodeService->generate($card, 'png');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.png', $file->getPathname());
        
        // Clean up
        @unlink($file->getPathname());
    }

    public function testGenerateSvgFormat(): void
    {
        $card = $this->createMockCard(2, '/c/test-card');

        $file = $this->qrCodeService->generate($card, 'svg');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.svg', $file->getPathname());
        
        // Clean up
        @unlink($file->getPathname());
    }

    public function testGeneratePdfFormat(): void
    {
        $card = $this->createMockCard(3, '/c/test-card');

        $file = $this->qrCodeService->generate($card, 'pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.pdf', $file->getPathname());
        
        // Clean up
        @unlink($file->getPathname());
    }

    public function testGenerateInvalidFormatThrowsException(): void
    {
        $card = $this->createMockCard(4, '/c/test-card');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format');

        $this->qrCodeService->generate($card, 'invalid');
    }

    public function testGenerateQrCodeBase64(): void
    {
        $data = 'https://example.com/test';

        $result = $this->qrCodeService->generateQrCodeBase64($data);

        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $this->assertNotEmpty($result);
    }

    private function createMockCard(int $id, string $publicUrl): Card
    {
        $card = $this->createMock(Card::class);
        $card->method('getId')->willReturn($id);
        $card->method('getPublicUrl')->willReturn($publicUrl);

        return $card;
    }
}
