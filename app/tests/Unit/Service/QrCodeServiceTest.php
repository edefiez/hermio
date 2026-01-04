<?php

namespace App\Tests\Unit\Service;

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
        $url = 'https://example.com/c/test-card';
        $identifier = 1;

        $file = $this->qrCodeService->generateFromUrl($url, $identifier, 'png');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.png', $file->getPathname());
        
        // Clean up
        if (file_exists($file->getPathname())) {
            unlink($file->getPathname());
        }
    }

    public function testGenerateSvgFormat(): void
    {
        $url = 'https://example.com/c/test-card';
        $identifier = 2;

        $file = $this->qrCodeService->generateFromUrl($url, $identifier, 'svg');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.svg', $file->getPathname());
        
        // Clean up
        if (file_exists($file->getPathname())) {
            unlink($file->getPathname());
        }
    }

    public function testGeneratePdfFormat(): void
    {
        $url = 'https://example.com/c/test-card';
        $identifier = 3;

        $file = $this->qrCodeService->generateFromUrl($url, $identifier, 'pdf');

        $this->assertInstanceOf(File::class, $file);
        $this->assertFileExists($file->getPathname());
        $this->assertStringEndsWith('.pdf', $file->getPathname());
        
        // Clean up
        if (file_exists($file->getPathname())) {
            unlink($file->getPathname());
        }
    }

    public function testGenerateInvalidFormatThrowsException(): void
    {
        $url = 'https://example.com/c/test-card';
        $identifier = 4;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format');

        $this->qrCodeService->generateFromUrl($url, $identifier, 'invalid');
    }

    public function testGenerateQrCodeBase64(): void
    {
        $data = 'https://example.com/test';

        $result = $this->qrCodeService->generateQrCodeBase64($data);

        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $this->assertNotEmpty($result);
    }
}
