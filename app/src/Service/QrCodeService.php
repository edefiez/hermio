<?php

namespace App\Service;

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
use App\Enum\PlanType;

class QrCodeService
{
    /**
     * Get available resolutions for a plan type
     *
     * @return array<string, int> Array of resolution names to sizes
     */
    public function getAvailableResolutions(PlanType $planType): array
    {
        return match($planType) {
            PlanType::FREE => [
                'low' => 300,
            ],
            PlanType::PRO => [
                'low' => 300,
                'medium' => 600,
                'high' => 1000,
            ],
            PlanType::ENTERPRISE => [
                'low' => 300,
                'medium' => 600,
                'high' => 1000,
                'ultra' => 2000,
                'print' => 4000,
            ],
        };
    }

    /**
     * Get available formats for a plan type
     *
     * @return array<string> Array of format extensions
     */
    public function getAvailableFormats(PlanType $planType): array
    {
        $formats = match($planType) {
            PlanType::FREE => ['png', 'jpeg'],
            PlanType::PRO => ['png', 'jpeg', 'svg'],
            PlanType::ENTERPRISE => ['png', 'jpeg', 'svg', 'eps'],
        };

        // Remove EPS if Imagick is not available
        if (in_array('eps', $formats) && !$this->isImagickAvailable()) {
            $formats = array_filter($formats, fn($format) => $format !== 'eps');
        }

        return $formats;
    }

    /**
     * Check if Imagick extension is available
     */
    private function isImagickAvailable(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * Get display info for format
     *
     * @return array{label: string, icon: string, description: string}
     */
    public function getFormatInfo(string $format): array
    {
        return match($format) {
            'png' => [
                'label' => 'PNG',
                'icon' => 'fa-file-image',
                'description' => 'Format standard, qualité optimale'
            ],
            'jpeg' => [
                'label' => 'JPEG',
                'icon' => 'fa-file-image',
                'description' => 'Format compressé, fichier plus léger'
            ],
            'svg' => [
                'label' => 'SVG',
                'icon' => 'fa-vector-square',
                'description' => 'Format vectoriel, redimensionnable sans perte'
            ],
            'eps' => [
                'label' => 'EPS',
                'icon' => 'fa-print',
                'description' => 'Format professionnel pour impression'
            ],
            default => [
                'label' => strtoupper($format),
                'icon' => 'fa-file',
                'description' => ''
            ],
        };
    }

    /**
     * Get display name for resolution
     */
    public function getResolutionLabel(string $resolution): string
    {
        return match($resolution) {
            'low' => 'Basse (300px)',
            'medium' => 'Moyenne (600px)',
            'high' => 'Haute (1000px)',
            'ultra' => 'Ultra (2000px)',
            'print' => 'Impression (4000px)',
            default => ucfirst($resolution),
        };
    }

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
     *
     * @param string $url The URL to encode in the QR code
     * @param int $identifier Unique identifier for temp file naming (e.g., card ID)
     * @param string $format Output format (png, jpeg, svg, pdf, eps)
     * @param int $size Size of the QR code in pixels
     */
    public function generateFromUrl(string $url, int $identifier, string $format = 'png', int $size = 300): File
    {
        $tmpDir = sys_get_temp_dir();

        return match ($format) {
            'png' => $this->build($url, new PngWriter(), "$tmpDir/qr-{$identifier}.png", $size),
            'jpeg' => $this->buildJpeg($url, $identifier, $size),
            'svg' => $this->build($url, new SvgWriter(), "$tmpDir/qr-{$identifier}.svg", $size),
            'pdf' => $this->build($url, new PdfWriter(), "$tmpDir/qr-{$identifier}.pdf", $size),
            'eps' => $this->buildEps($url, $identifier, $size),
            default => throw new \InvalidArgumentException("Unsupported format: $format"),
        };
    }

    /**
     * Build QR code with specified writer and save to file
     */
    private function build(string $data, PngWriter|SvgWriter|PdfWriter $writer, string $path, int $size = 300): File
    {
        $builder = new Builder(
            writer: $writer,
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $result = $builder->build();
        $result->saveToFile($path);

        return new File($path, false);
    }

    /**
     * Generate JPEG format by converting PNG
     */
    private function buildJpeg(string $url, int $identifier, int $size = 300): File
    {
        $pngPath = sys_get_temp_dir() . "/qr-{$identifier}.png";
        $jpegPath = sys_get_temp_dir() . "/qr-{$identifier}.jpeg";

        try {
            // Generate PNG first
            $this->build($url, new PngWriter(), $pngPath, $size);

            // Convert PNG to JPEG
            $this->convertPngToJpeg($pngPath, $jpegPath);

            return new File($jpegPath, false);
        } finally {
            // Clean up temporary PNG file
            if (file_exists($pngPath)) {
                @unlink($pngPath);
            }
        }
    }

    /**
     * Convert PNG to JPEG using GD
     */
    private function convertPngToJpeg(string $pngPath, string $jpegPath): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required for JPEG conversion');
        }

        $png = imagecreatefrompng($pngPath);
        if ($png === false) {
            throw new \RuntimeException('Failed to load PNG image');
        }

        // Create a white background (JPEG doesn't support transparency)
        $width = imagesx($png);
        $height = imagesy($png);
        $jpeg = imagecreatetruecolor($width, $height);

        if ($jpeg === false) {
            imagedestroy($png);
            throw new \RuntimeException('Failed to create JPEG image');
        }

        $white = imagecolorallocate($jpeg, 255, 255, 255);
        if ($white === false) {
            imagedestroy($png);
            imagedestroy($jpeg);
            throw new \RuntimeException('Failed to allocate white color');
        }

        imagefill($jpeg, 0, 0, $white);
        imagecopy($jpeg, $png, 0, 0, 0, 0, $width, $height);

        // Save as JPEG with high quality (90)
        $result = imagejpeg($jpeg, $jpegPath, 90);

        imagedestroy($png);
        imagedestroy($jpeg);

        if (!$result || !file_exists($jpegPath)) {
            throw new \RuntimeException('Failed to save JPEG image');
        }
    }

    /**
     * Generate EPS format by converting SVG
     */
    private function buildEps(string $url, int $identifier, int $size = 300): File
    {
        $svgPath = sys_get_temp_dir() . "/qr-{$identifier}.svg";
        $epsPath = sys_get_temp_dir() . "/qr-{$identifier}.eps";

        try {
            // Generate SVG first
            $this->build($url, new SvgWriter(), $svgPath, $size);

            // Convert SVG to EPS
            $this->convertSvgToEps($svgPath, $epsPath);

            return new File($epsPath, false);
        } finally {
            // Clean up temporary SVG file
            if (file_exists($svgPath)) {
                @unlink($svgPath);
            }
        }
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
                error_log('Imagick EPS conversion failed: ' . $e->getMessage());
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
                'Failed to convert SVG to EPS. Neither Imagick nor Inkscape are available or working properly. ' .
                'Please install Imagick PHP extension or Inkscape to enable EPS format export.'
            );
        }
    }
}

