<?php

namespace App\Service;

use App\Entity\Card;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PdfWriter;
use Symfony\Component\HttpFoundation\File\File;

class QrCodeService
{
    public function generateQrCode(string $data, int $size = 300): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
            labelText: '',
            labelFont: new OpenSans(16),
            labelAlignment: LabelAlignment::Center,
            labelMargin: new Margin(0, 0, 0, 0),
            labelTextColor: new Color(0, 0, 0),
            logoPath: '',
            logoResizeToWidth: 0,
            logoResizeToHeight: 0,
            logoPunchoutBackground: false
        );

        $result = $builder->build();

        return $result->getString();
    }

    public function generateQrCodeBase64(string $data, int $size = 300): string
    {
        $qrCodeData = $this->generateQrCode($data, $size);
        return 'data:image/png;base64,' . base64_encode($qrCodeData);
    }

    /**
     * Generate QR code file in specified format for download
     */
    public function generate(Card $card, string $format): File
    {
        $data = $card->getPublicUrl();
        $tmpDir = sys_get_temp_dir();

        return match ($format) {
            'png' => $this->build($data, new PngWriter(), "$tmpDir/qr-{$card->getId()}.png"),
            'svg' => $this->build($data, new SvgWriter(), "$tmpDir/qr-{$card->getId()}.svg"),
            'pdf' => $this->build($data, new PdfWriter(), "$tmpDir/qr-{$card->getId()}.pdf"),
            'eps' => $this->buildEps($data, $card->getId()),
            default => throw new \InvalidArgumentException("Unsupported format: $format"),
        };
    }

    /**
     * Build QR code with specified writer and save to file
     */
    private function build(string $data, object $writer, string $path): File
    {
        Builder::create()
            ->writer($writer)
            ->data($data)
            ->size(300)
            ->margin(10)
            ->build()
            ->saveToFile($path);

        return new File($path, false);
    }

    /**
     * Generate EPS format by converting SVG
     */
    private function buildEps(string $data, int $cardId): File
    {
        $svgPath = sys_get_temp_dir() . "/qr-{$cardId}.svg";
        $epsPath = sys_get_temp_dir() . "/qr-{$cardId}.eps";

        // Generate SVG first
        $this->build($data, new SvgWriter(), $svgPath);

        // Convert SVG to EPS
        $this->convertSvgToEps($svgPath, $epsPath);

        return new File($epsPath, false);
    }

    /**
     * Convert SVG to EPS using Imagick or Inkscape as fallback
     */
    private function convertSvgToEps(string $svgPath, string $epsPath): void
    {
        // Try Imagick first (recommended)
        if (extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($svgPath);
                $imagick->setImageFormat('eps');
                $imagick->writeImage($epsPath);
                $imagick->clear();
                return;
            } catch (\Exception $e) {
                // Fall through to Inkscape
            }
        }

        // Fallback to Inkscape CLI
        $command = sprintf(
            'inkscape %s --export-type=eps --export-filename=%s 2>&1',
            escapeshellarg($svgPath),
            escapeshellarg($epsPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($epsPath)) {
            throw new \RuntimeException(
                'Failed to convert SVG to EPS. Neither Imagick nor Inkscape are available or working properly.'
            );
        }
    }
}

