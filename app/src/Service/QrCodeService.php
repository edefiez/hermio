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

    public function generateQrCode(string $data, int $size = 300, ?string $logoPath = null): string
    {
        $logoResizeToWidth = 0;
        $logoResizeToHeight = 0;
        $logoPunchoutBackground = false;
        $processedLogoPath = null;

        // If logo is provided, process it (convert SVG to PNG if needed and add white background)
        if ($logoPath && file_exists($logoPath)) {
            $processedLogoPath = $this->processLogoForQrCode($logoPath, $size);
            
            if ($processedLogoPath) {
                $logoSize = (int) ($size * 0.25); // 25% of QR code size (increased from 20% for better visibility)
                $logoResizeToWidth = $logoSize;
                $logoResizeToHeight = $logoSize;
                $logoPunchoutBackground = false; // Keep white background for better visibility
            }
        }

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High, // Higher error correction allows larger logo
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
            logoPath: $processedLogoPath ?? '',
            logoResizeToWidth: $logoResizeToWidth,
            logoResizeToHeight: $logoResizeToHeight,
            logoPunchoutBackground: $logoPunchoutBackground
        );

        $result = $builder->build();

        // Clean up temporary logo file if it was converted
        if ($processedLogoPath && $processedLogoPath !== $logoPath && file_exists($processedLogoPath)) {
            @unlink($processedLogoPath);
        }

        return $result->getString();
    }

    public function generateQrCodeBase64(string $data, int $size = 300, ?string $logoPath = null): string
    {
        $qrCodeData = $this->generateQrCode($data, $size, $logoPath);
        return 'data:image/png;base64,' . base64_encode($qrCodeData);
    }

    /**
     * Generate QR code file in specified format for download
     *
     * @param string $url The URL to encode in the QR code
     * @param int $identifier Unique identifier for temp file naming (e.g., card ID)
     * @param string $format Output format (png, jpeg, svg, pdf, eps)
     * @param int $size Size of the QR code in pixels
     * @param string|null $logoPath Path to logo file to embed in QR code center
     */
    public function generateFromUrl(string $url, int $identifier, string $format = 'png', int $size = 300, ?string $logoPath = null): File
    {
        $tmpDir = sys_get_temp_dir();

        return match ($format) {
            'png' => $this->build($url, new PngWriter(), "$tmpDir/qr-{$identifier}.png", $size, $logoPath),
            'jpeg' => $this->buildJpeg($url, $identifier, $size, $logoPath),
            'svg' => $this->build($url, new SvgWriter(), "$tmpDir/qr-{$identifier}.svg", $size, $logoPath),
            'pdf' => $this->build($url, new PdfWriter(), "$tmpDir/qr-{$identifier}.pdf", $size, $logoPath),
            'eps' => $this->buildEps($url, $identifier, $size, $logoPath),
            default => throw new \InvalidArgumentException("Unsupported format: $format"),
        };
    }

    /**
     * Build QR code with specified writer and save to file
     */
    private function build(string $data, PngWriter|SvgWriter|PdfWriter $writer, string $path, int $size = 300, ?string $logoPath = null): File
    {
        $logoResizeToWidth = 0;
        $logoResizeToHeight = 0;
        $logoPunchoutBackground = false;
        $processedLogoPath = null;

        // If logo is provided, process it (convert SVG to PNG if needed and add white background)
        if ($logoPath && file_exists($logoPath)) {
            $processedLogoPath = $this->processLogoForQrCode($logoPath, $size);
            
            if ($processedLogoPath) {
                $logoSize = (int) ($size * 0.25); // 25% of QR code size (increased from 20% for better visibility)
                $logoResizeToWidth = $logoSize;
                $logoResizeToHeight = $logoSize;
                $logoPunchoutBackground = false; // Keep white background for better visibility
            }
        }

        $builder = new Builder(
            writer: $writer,
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High, // Higher error correction allows larger logo
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
            logoPath: $processedLogoPath ?? '',
            logoResizeToWidth: $logoResizeToWidth,
            logoResizeToHeight: $logoResizeToHeight,
            logoPunchoutBackground: $logoPunchoutBackground
        );

        $result = $builder->build();
        $result->saveToFile($path);

        // Clean up temporary logo file if it was converted
        if ($processedLogoPath && $processedLogoPath !== $logoPath && file_exists($processedLogoPath)) {
            @unlink($processedLogoPath);
        }

        return new File($path, false);
    }

    /**
     * Generate JPEG format by converting PNG
     */
    private function buildJpeg(string $url, int $identifier, int $size = 300, ?string $logoPath = null): File
    {
        $pngPath = sys_get_temp_dir() . "/qr-{$identifier}.png";
        $jpegPath = sys_get_temp_dir() . "/qr-{$identifier}.jpeg";

        try {
            // Generate PNG first
            $this->build($url, new PngWriter(), $pngPath, $size, $logoPath);

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
    private function buildEps(string $url, int $identifier, int $size = 300, ?string $logoPath = null): File
    {
        $svgPath = sys_get_temp_dir() . "/qr-{$identifier}.svg";
        $epsPath = sys_get_temp_dir() . "/qr-{$identifier}.eps";

        try {
            // Generate SVG first
            $this->build($url, new SvgWriter(), $svgPath, $size, $logoPath);

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
     * Process logo for QR code: convert SVG to PNG if needed and add white background with border
     * PNG Writer does not support SVG logos, so we need to convert them
     * Adds white background and border for better visibility in QR code
     */
    private function processLogoForQrCode(string $logoPath, int $qrCodeSize = 300): ?string
    {
        if (!file_exists($logoPath)) {
            return null;
        }

        // Check if file is SVG
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $tmpPngPath = sys_get_temp_dir() . '/qr-logo-' . uniqid() . '.png';
        
        if ($extension === 'svg') {
            // Convert SVG to PNG first
            if (!$this->convertSvgToPng($logoPath, $tmpPngPath)) {
                return null;
            }
            $logoPath = $tmpPngPath;
        }

        // Add white background and border to logo for better visibility
        return $this->addWhiteBackgroundToLogo($logoPath, $qrCodeSize, $extension === 'svg');
    }

    /**
     * Convert SVG to PNG using GD or Imagick
     */
    private function convertSvgToPng(string $svgPath, string $pngPath): bool
    {
        // Try Imagick first (better quality)
        if (extension_loaded('imagick')) {
            try {
                $imagickClass = 'Imagick';
                if (class_exists($imagickClass)) {
                    $imagick = new $imagickClass();
                    $pixelClass = 'ImagickPixel';
                    if (class_exists($pixelClass)) {
                        $imagick->setBackgroundColor(new $pixelClass('transparent'));
                    }
                    $imagick->readImage($svgPath);
                    $imagick->setImageFormat('png');
                    $imagick->writeImage($pngPath);
                    $imagick->clear();
                    
                    if (file_exists($pngPath)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                error_log('Imagick SVG to PNG conversion failed: ' . $e->getMessage());
            }
        }

        // Fallback: Try using Inkscape CLI
        $command = sprintf(
            'inkscape %s --export-type=png --export-filename=%s --export-background-opacity=0 2>&1',
            escapeshellarg($svgPath),
            escapeshellarg($pngPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($pngPath)) {
            return true;
        }

        // Last resort: Try using GD with a simple approach (limited SVG support)
        if (extension_loaded('gd')) {
            // GD has very limited SVG support, so this might not work for all SVGs
            // But we try it as a last resort
            try {
                $image = imagecreatefromstring(file_get_contents($svgPath));
                if ($image !== false) {
                    imagepng($image, $pngPath);
                    imagedestroy($image);
                    if (file_exists($pngPath)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                error_log('GD SVG to PNG conversion failed: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Add white background and border to logo for better visibility in QR code
     */
    private function addWhiteBackgroundToLogo(string $logoPath, int $qrCodeSize, bool $isTemporary = false): ?string
    {
        if (!file_exists($logoPath)) {
            return null;
        }

        // Calculate logo size (25% of QR code size)
        $logoSize = (int) ($qrCodeSize * 0.25);
        // Padding around logo (10% of logo size)
        $padding = (int) ($logoSize * 0.1);
        // Border width (2% of logo size, minimum 2px)
        $borderWidth = max(2, (int) ($logoSize * 0.02));
        
        // Final size with padding and border
        $finalSize = $logoSize + ($padding * 2) + ($borderWidth * 2);

        $outputPath = sys_get_temp_dir() . '/qr-logo-bg-' . uniqid() . '.png';

        // Try Imagick first (better quality)
        if (extension_loaded('imagick')) {
            try {
                $imagickClass = 'Imagick';
                $pixelClass = 'ImagickPixel';
                if (class_exists($imagickClass) && class_exists($pixelClass)) {
                    // Load original logo
                    $logo = new $imagickClass();
                    $logo->readImage($logoPath);
                    $logo->setImageFormat('png');
                    
                    // Resize logo to fit in the center
                    // Use constant value directly to avoid type errors
                    $filterConstant = 1; // FILTER_LANCZOS
                    if (defined('Imagick::FILTER_LANCZOS')) {
                        $filterConstant = constant('Imagick::FILTER_LANCZOS');
                    }
                    $logo->resizeImage($logoSize, $logoSize, $filterConstant, 1, true);
                    
                    // Create white background image
                    $background = new $imagickClass();
                    $whitePixel = new $pixelClass('white');
                    $background->newImage($finalSize, $finalSize, $whitePixel);
                    $background->setImageFormat('png');
                    
                    // Add border (black border around white background)
                    $border = new $imagickClass();
                    $blackPixel = new $pixelClass('black');
                    $border->newImage($finalSize, $finalSize, $blackPixel);
                    $border->setImageFormat('png');
                    // Use constant value directly to avoid type errors
                    $compositeConstant = 1; // COMPOSITE_OVER
                    if (defined('Imagick::COMPOSITE_OVER')) {
                        $compositeConstant = constant('Imagick::COMPOSITE_OVER');
                    }
                    $background->compositeImage($border, $compositeConstant, 0, 0);
                    
                    // Add white background on top of border (creating border effect)
                    $whiteBg = new $imagickClass();
                    $whiteBg->newImage($finalSize - ($borderWidth * 2), $finalSize - ($borderWidth * 2), $whitePixel);
                    $whiteBg->setImageFormat('png');
                    $background->compositeImage($whiteBg, $compositeConstant, $borderWidth, $borderWidth);
                    
                    // Center logo on white background
                    $x = $borderWidth + $padding;
                    $y = $borderWidth + $padding;
                    $background->compositeImage($logo, $compositeConstant, $x, $y);
                    
                    $background->writeImage($outputPath);
                    $logo->clear();
                    $background->clear();
                    $border->clear();
                    $whiteBg->clear();
                    
                    // Clean up temporary file if it was created from SVG
                    if ($isTemporary && file_exists($logoPath)) {
                        @unlink($logoPath);
                    }
                    
                    if (file_exists($outputPath)) {
                        return $outputPath;
                    }
                }
            } catch (\Exception $e) {
                error_log('Imagick logo background addition failed: ' . $e->getMessage());
            }
        }

        // Fallback: Use GD (simpler but works)
        if (extension_loaded('gd')) {
            try {
                // Load original logo
                $logoImage = null;
                $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                
                if ($extension === 'png') {
                    $logoImage = imagecreatefrompng($logoPath);
                } elseif ($extension === 'jpeg' || $extension === 'jpg') {
                    $logoImage = imagecreatefromjpeg($logoPath);
                } elseif ($extension === 'gif') {
                    $logoImage = imagecreatefromgif($logoPath);
                }
                
                if ($logoImage === false) {
                    // Clean up temporary file if it was created from SVG
                    if ($isTemporary && file_exists($logoPath)) {
                        @unlink($logoPath);
                    }
                    return null;
                }
                
                // Get original dimensions
                $origWidth = imagesx($logoImage);
                $origHeight = imagesy($logoImage);
                
                // Calculate scaling to fit logo in center area
                $scale = min($logoSize / $origWidth, $logoSize / $origHeight);
                $newWidth = (int) ($origWidth * $scale);
                $newHeight = (int) ($origHeight * $scale);
                
                // Create resized logo
                $resizedLogo = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resizedLogo, false);
                imagesavealpha($resizedLogo, true);
                $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
                imagefill($resizedLogo, 0, 0, $transparent);
                imagealphablending($resizedLogo, true);
                
                // Resize logo
                imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                
                // Create final image with white background
                $finalImage = imagecreatetruecolor($finalSize, $finalSize);
                $white = imagecolorallocate($finalImage, 255, 255, 255);
                $black = imagecolorallocate($finalImage, 0, 0, 0);
                
                // Fill with white
                imagefill($finalImage, 0, 0, $white);
                
                // Draw black border
                imagerectangle($finalImage, 0, 0, $finalSize - 1, $finalSize - 1, $black);
                for ($i = 1; $i < $borderWidth; $i++) {
                    imagerectangle($finalImage, $i, $i, $finalSize - 1 - $i, $finalSize - 1 - $i, $black);
                }
                
                // Center logo on white background
                $x = $borderWidth + $padding + (int) (($logoSize - $newWidth) / 2);
                $y = $borderWidth + $padding + (int) (($logoSize - $newHeight) / 2);
                imagecopy($finalImage, $resizedLogo, $x, $y, 0, 0, $newWidth, $newHeight);
                
                // Save output
                imagepng($finalImage, $outputPath);
                
                // Clean up
                imagedestroy($logoImage);
                imagedestroy($resizedLogo);
                imagedestroy($finalImage);
                
                // Clean up temporary file if it was created from SVG
                if ($isTemporary && file_exists($logoPath)) {
                    @unlink($logoPath);
                }
                
                if (file_exists($outputPath)) {
                    return $outputPath;
                }
            } catch (\Exception $e) {
                error_log('GD logo background addition failed: ' . $e->getMessage());
            }
        }

        // If all methods fail, return original logo path (or temporary PNG if SVG was converted)
        if ($isTemporary && file_exists($logoPath)) {
            return $logoPath;
        }
        
        return $logoPath;
    }

    /**
     * Convert SVG to EPS using Imagick or Inkscape as fallback
     */
    private function convertSvgToEps(string $svgPath, string $epsPath): void
    {
        // Try Imagick first (recommended)
        if (extension_loaded('imagick')) {
            try {
                // Use dynamic class instantiation to avoid type errors when extension is not available
                $imagickClass = 'Imagick';
                if (class_exists($imagickClass)) {
                    $imagick = new $imagickClass();
                    $imagick->readImage($svgPath);
                    $imagick->setImageFormat('eps');
                    $imagick->writeImage($epsPath);
                    $imagick->clear();
                    return;
                }
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

