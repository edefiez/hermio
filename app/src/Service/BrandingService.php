<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\AccountBranding;
use App\Enum\PlanType;
use App\Repository\AccountBrandingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BrandingService
{
    private string $logoStoragePath;
    private string $fontStoragePath;

    // Top 50 Google Fonts (popular choices for business cards)
    public const GOOGLE_FONTS = [
        'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald',
        'Source Sans Pro', 'Raleway', 'PT Sans', 'Merriweather', 'Ubuntu',
        'Playfair Display', 'Poppins', 'Nunito', 'Roboto Condensed', 'Noto Sans',
        'Roboto Slab', 'Mukta', 'Work Sans', 'PT Serif', 'Rubik',
        'Titillium Web', 'Hind', 'Quicksand', 'Karla', 'Arimo',
        'Barlow', 'Heebo', 'Fira Sans', 'Libre Franklin', 'Libre Baskerville',
        'Crimson Text', 'Cabin', 'Oxygen', 'Dosis', 'Josefin Sans',
        'Bitter', 'Noto Serif', 'Anton', 'Bebas Neue', 'Lobster',
        'Dancing Script', 'Pacifico', 'Indie Flower', 'Righteous', 'Comfortaa',
        'Abril Fatface', 'Archivo', 'Caveat', 'Fredoka', 'Permanent Marker'
    ];

    public const CARD_TEMPLATES = [
        'default' => 'Default',
        'modern' => 'Modern',
        'elegant' => 'Elegant',
        'minimal' => 'Minimal'
    ];

    public function __construct(
        private AccountBrandingRepository $accountBrandingRepository,
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem,
        string $projectDir
    ) {
        $this->logoStoragePath = $projectDir . '/public/uploads/branding/logos';
        $this->fontStoragePath = $projectDir . '/public/uploads/branding/fonts';
    }

    public function canConfigureBranding(Account $account): bool
    {
        $planType = $account->getPlanType();
        return $planType === PlanType::PRO || $planType === PlanType::ENTERPRISE;
    }

    public function canConfigureTemplate(Account $account): bool
    {
        return $account->getPlanType() === PlanType::ENTERPRISE;
    }

    /**
     * Get branding configuration for an account.
     * 
     * Note: This method fetches data directly from the database on each request,
     * ensuring branding changes are reflected immediately without cache clearing.
     * Plan restrictions are applied: Free accounts return null, Pro accounts have
     * template customization disabled.
     */
    public function getBrandingForAccount(Account $account): ?AccountBranding
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            return null;
        }
        
        // Apply plan restrictions
        $planType = $account->getPlanType();
        if ($planType === PlanType::FREE) {
            return null;
        }
        
        if ($planType === PlanType::PRO) {
            // Pro plan: disable template feature (will be null anyway, but explicit)
            $branding->setCustomTemplate(null);
        }
        
        return $branding;
    }

    public function configureBranding(Account $account, array $data, ?UploadedFile $logoFile = null): AccountBranding
    {
        if (!$this->canConfigureBranding($account)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Branding is only available for Pro and Enterprise plans');
        }

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            $branding = new AccountBranding();
            $branding->setAccount($account);
        }

        // Update colors
        if (isset($data['primaryColor'])) {
            $branding->setPrimaryColor($data['primaryColor'] ?: null);
        }
        if (isset($data['secondaryColor'])) {
            $branding->setSecondaryColor($data['secondaryColor'] ?: null);
        }

        // Handle logo upload
        if ($logoFile) {
            $this->validateLogoFile($logoFile);
            $oldLogo = $branding->getLogoFilename();
            $newLogo = $this->uploadLogo($logoFile);
            $branding->setLogoFilename($newLogo);
            
            // Delete old logo
            if ($oldLogo) {
                $this->deleteLogoFile($oldLogo);
            }
        }

        // Update logo position and size
        if (isset($data['logoPosition'])) {
            $branding->setLogoPosition($data['logoPosition'] ?: null);
        }
        if (isset($data['logoSize'])) {
            $branding->setLogoSize($data['logoSize'] ?: null);
        }

        // Handle second logo upload
        if (isset($data['secondLogoFile']) && $data['secondLogoFile'] instanceof UploadedFile) {
            $this->validateLogoFile($data['secondLogoFile']);
            $oldSecondLogo = $branding->getSecondLogoFilename();
            $newSecondLogo = $this->uploadLogo($data['secondLogoFile']);
            $branding->setSecondLogoFilename($newSecondLogo);
            
            // Delete old second logo
            if ($oldSecondLogo) {
                $this->deleteLogoFile($oldSecondLogo);
            }
        }

        // Update second logo position and size
        if (isset($data['secondLogoPosition'])) {
            $branding->setSecondLogoPosition($data['secondLogoPosition'] ?: null);
        }
        if (isset($data['secondLogoSize'])) {
            $branding->setSecondLogoSize($data['secondLogoSize'] ?: null);
        }

        // Handle font selection
        if (isset($data['fontFamily'])) {
            $branding->setFontFamily($data['fontFamily'] ?: null);
        }

        // Handle custom font upload
        if (isset($data['customFontFile']) && $data['customFontFile'] instanceof UploadedFile) {
            $this->validateFontFile($data['customFontFile']);
            $oldFont = $branding->getCustomFontFilename();
            $newFont = $this->uploadFont($data['customFontFile']);
            $branding->setCustomFontFilename($newFont);
            
            // Delete old font
            if ($oldFont) {
                $this->deleteFontFile($oldFont);
            }
        }

        // Handle card template selection
        if (isset($data['cardTemplate'])) {
            $branding->setCardTemplate($data['cardTemplate'] ?: 'default');
        }

        $this->entityManager->persist($branding);
        $this->entityManager->flush();

        return $branding;
    }

    public function removeLogo(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding && $branding->getLogoFilename()) {
            $this->deleteLogoFile($branding->getLogoFilename());
            $branding->setLogoFilename(null);
            $this->entityManager->flush();
        }
    }

    public function removeSecondLogo(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding && $branding->getSecondLogoFilename()) {
            $this->deleteLogoFile($branding->getSecondLogoFilename());
            $branding->setSecondLogoFilename(null);
            $this->entityManager->flush();
        }
    }

    public function removeCustomFont(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding && $branding->getCustomFontFilename()) {
            $this->deleteFontFile($branding->getCustomFontFilename());
            $branding->setCustomFontFilename(null);
            $this->entityManager->flush();
        }
    }

    private function uploadLogo(UploadedFile $file): string
    {
        // Generate secure filename: random hash + sanitized extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg'];
        
        // Ensure extension is safe
        if (!in_array($extension, $allowedExtensions)) {
            $extension = $file->guessExtension() ?? 'png';
        }
        
        // Generate unique filename with random hash
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $filePath = $this->logoStoragePath . '/' . $filename;
        
        // Move file to storage directory
        $file->move($this->logoStoragePath, $filename);
        
        // Additional security: verify file was moved successfully
        if (!$this->filesystem->exists($filePath)) {
            throw new \RuntimeException('Failed to upload logo file');
        }
        
        return $filename;
    }

    private function deleteLogoFile(string $filename): void
    {
        $filePath = $this->logoStoragePath . '/' . $filename;
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    private function uploadFont(UploadedFile $file): string
    {
        // Generate secure filename: random hash + sanitized extension
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Ensure extension is safe
        if ($extension !== 'ttf') {
            $extension = 'ttf';
        }
        
        // Generate unique filename with random hash
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $filePath = $this->fontStoragePath . '/' . $filename;
        
        // Ensure font storage directory exists
        if (!$this->filesystem->exists($this->fontStoragePath)) {
            $this->filesystem->mkdir($this->fontStoragePath);
        }
        
        // Move file to storage directory
        $file->move($this->fontStoragePath, $filename);
        
        // Additional security: verify file was moved successfully
        if (!$this->filesystem->exists($filePath)) {
            throw new \RuntimeException('Failed to upload font file');
        }
        
        return $filename;
    }

    private function deleteFontFile(string $filename): void
    {
        $filePath = $this->fontStoragePath . '/' . $filename;
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
    }

    private function validateFontFile(UploadedFile $file): void
    {
        $allowedTypes = ['font/ttf', 'application/x-font-ttf', 'application/x-font-truetype', 'font/sfnt'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Validate MIME type (TTF can have different MIME types)
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if ($extension !== 'ttf' && !in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('branding.font.invalid_type');
        }

        // Validate file size
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('branding.font.too_large');
        }

        // Additional validation: check if it's a valid TTF file by reading the header
        $content = file_get_contents($file->getPathname());
        if (strlen($content) < 4) {
            throw new \InvalidArgumentException('branding.font.invalid_type');
        }
        
        // TTF files start with specific magic bytes (0x00010000 or 'true' or 'OTTO')
        // Note: 'OTTO' is for OpenType fonts with CFF data, which are accepted here
        // as they provide additional flexibility and are commonly used alongside TTF
        $header = substr($content, 0, 4);
        $validHeaders = [
            "\x00\x01\x00\x00", // TrueType 1.0
            "true",              // TrueType (Mac)
            "OTTO",              // OpenType with CFF (accepted for flexibility)
        ];
        
        $isValid = false;
        foreach ($validHeaders as $validHeader) {
            if (substr($header, 0, strlen($validHeader)) === $validHeader) {
                $isValid = true;
                break;
            }
        }
        
        if (!$isValid) {
            throw new \InvalidArgumentException('branding.font.invalid_type');
        }
    }

    private function validateLogoFile(UploadedFile $file): void
    {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('branding.logo.invalid_type');
        }

        // Validate file size
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('branding.logo.too_large');
        }

        // Additional security: validate file extension matches MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'svg'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('branding.logo.invalid_type');
        }

        // For SVG files, additional validation could be added here
        // (e.g., check for malicious scripts in SVG content)
        if ($extension === 'svg') {
            // Basic SVG validation: check if it's a valid XML/SVG file
            $content = file_get_contents($file->getPathname());
            if (!preg_match('/<\?xml|<svg/i', $content)) {
                throw new \InvalidArgumentException('branding.logo.invalid_type');
            }
            
            // Check for potentially dangerous SVG elements
            $dangerousSvgPatterns = [
                '/<script/i',
                '/on\w+\s*=/i', // Event handlers like onclick=
                '/javascript:/i',
            ];
            
            foreach ($dangerousSvgPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new \InvalidArgumentException('branding.logo.invalid_type');
                }
            }
        }
    }

    /**
     * Validate custom template syntax and security.
     * 
     * @throws \InvalidArgumentException If template is invalid or contains dangerous code
     */
    public function validateTemplateSyntax(string $templateContent): void
    {
        if (empty(trim($templateContent))) {
            return; // Empty template is valid (will use default)
        }

        // Check that template extends the base template
        if (!preg_match('/{%\s*extends\s+[\'"]public\/card\.html\.twig[\'"]\s*%}/i', $templateContent)) {
            throw new \InvalidArgumentException('branding.template.must_extend_base');
        }

        // Check for dangerous Twig functions/features
        $dangerousPatterns = [
            '/\bexec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bshell_exec\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bproc_open\s*\(/i',
            '/\bpopen\s*\(/i',
            '/\b`.*`/', // Backticks for shell execution
            '/\beval\s*\(/i',
            '/\binclude\s*\(/i',
            '/\brequire\s*\(/i',
            '/\brequire_once\s*\(/i',
            '/\binclude_once\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $templateContent)) {
                throw new \InvalidArgumentException('branding.template.dangerous_function');
            }
        }

        // Check for potentially dangerous Twig filters/functions
        $dangerousTwigPatterns = [
            '/\braw\s*\|/i', // raw filter can be dangerous if misused
        ];

        // Note: We allow raw filter but warn about it in documentation
        // Additional validation can be added here if needed
    }

    /**
     * Configure custom template for Enterprise accounts.
     */
    public function configureTemplate(Account $account, string $templateContent): AccountBranding
    {
        if (!$this->canConfigureTemplate($account)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Template customization is only available for Enterprise plans');
        }

        // Validate template syntax
        $this->validateTemplateSyntax($templateContent);

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            $branding = new AccountBranding();
            $branding->setAccount($account);
        }

        $branding->setCustomTemplate(empty(trim($templateContent)) ? null : $templateContent);

        $this->entityManager->persist($branding);
        $this->entityManager->flush();

        return $branding;
    }

    /**
     * Reset all branding configuration for an account.
     * Deletes logo file and removes all branding settings.
     */
    public function resetBranding(Account $account): void
    {
        if (!$this->canConfigureBranding($account)) {
            throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Branding is only available for Pro and Enterprise plans');
        }

        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding) {
            // Delete logo file if exists
            if ($branding->getLogoFilename()) {
                $this->deleteLogoFile($branding->getLogoFilename());
            }
            
            // Delete second logo file if exists
            if ($branding->getSecondLogoFilename()) {
                $this->deleteLogoFile($branding->getSecondLogoFilename());
            }
            
            // Delete custom font file if exists
            if ($branding->getCustomFontFilename()) {
                $this->deleteFontFile($branding->getCustomFontFilename());
            }
            
            // Remove branding entity
            $this->entityManager->remove($branding);
            $this->entityManager->flush();
        }
    }

    /**
     * Handle plan downgrade by preserving branding data but disabling features.
     * Called when account plan changes from higher tier to lower tier.
     */
    public function handlePlanDowngrade(Account $account, PlanType $oldPlan, PlanType $newPlan): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if (!$branding) {
            return; // No branding to handle
        }

        // If downgrading from Enterprise to Pro: disable template customization
        if ($oldPlan === PlanType::ENTERPRISE && $newPlan === PlanType::PRO) {
            $branding->setCustomTemplate(null);
            $this->entityManager->flush();
        }
        
        // If downgrading to Free: branding data is preserved but won't be applied
        // (getBrandingForAccount already returns null for Free accounts)
        // Logo files are preserved in case user upgrades again
    }

    /**
     * Clean up branding files when account is deleted.
     * This should be called before account deletion.
     */
    public function cleanupBrandingFiles(Account $account): void
    {
        $branding = $this->accountBrandingRepository->findOneByAccount($account);
        
        if ($branding) {
            if ($branding->getLogoFilename()) {
                $this->deleteLogoFile($branding->getLogoFilename());
            }
            if ($branding->getSecondLogoFilename()) {
                $this->deleteLogoFile($branding->getSecondLogoFilename());
            }
            if ($branding->getCustomFontFilename()) {
                $this->deleteFontFile($branding->getCustomFontFilename());
            }
        }
    }

    /**
     * Check color contrast ratio for accessibility compliance (WCAG).
     * 
     * @param string $foregroundColor Hex color (e.g., '#000000')
     * @param string $backgroundColor Hex color (e.g., '#FFFFFF')
     * @return array{ratio: float, level: string, passes: bool} Contrast information
     */
    public function checkColorContrast(string $foregroundColor, string $backgroundColor): array
    {
        $foregroundRgb = $this->hexToRgb($foregroundColor);
        $backgroundRgb = $this->hexToRgb($backgroundColor);
        
        $foregroundLuminance = $this->calculateLuminance($foregroundRgb);
        $backgroundLuminance = $this->calculateLuminance($backgroundRgb);
        
        // Calculate contrast ratio
        $lighter = max($foregroundLuminance, $backgroundLuminance);
        $darker = min($foregroundLuminance, $backgroundLuminance);
        $ratio = ($lighter + 0.05) / ($darker + 0.05);
        
        // WCAG standards:
        // AA Normal Text: 4.5:1
        // AA Large Text: 3:1
        // AAA Normal Text: 7:1
        // AAA Large Text: 4.5:1
        
        $passesAA = $ratio >= 4.5;
        $passesAAA = $ratio >= 7.0;
        
        $level = 'fail';
        if ($passesAAA) {
            $level = 'AAA';
        } elseif ($passesAA) {
            $level = 'AA';
        }
        
        return [
            'ratio' => round($ratio, 2),
            'level' => $level,
            'passes' => $passesAA,
            'passesAAA' => $passesAAA,
        ];
    }

    /**
     * Validate color contrast for common use cases (text on background).
     * Returns warnings if contrast is insufficient.
     * 
     * @param string|null $primaryColor Primary brand color
     * @param string|null $secondaryColor Secondary brand color
     * @return array Array of warning messages (empty if no issues)
     */
    public function validateColorAccessibility(?string $primaryColor, ?string $secondaryColor): array
    {
        $warnings = [];
        
        if (!$primaryColor && !$secondaryColor) {
            return $warnings; // No colors configured
        }
        
        // Default background color (white for public card pages)
        $defaultBackground = '#FFFFFF';
        
        if ($primaryColor) {
            $contrast = $this->checkColorContrast($primaryColor, $defaultBackground);
            if (!$contrast['passes']) {
                $warnings[] = [
                    'type' => 'primary',
                    'ratio' => $contrast['ratio'],
                    'level' => $contrast['level'],
                    'message_key' => 'branding.color.accessibility.warning.primary',
                ];
            }
        }
        
        if ($secondaryColor) {
            $contrast = $this->checkColorContrast($secondaryColor, $defaultBackground);
            if (!$contrast['passes']) {
                $warnings[] = [
                    'type' => 'secondary',
                    'ratio' => $contrast['ratio'],
                    'level' => $contrast['level'],
                    'message_key' => 'branding.color.accessibility.warning.secondary',
                ];
            }
        }
        
        return $warnings;
    }

    /**
     * Convert hex color to RGB array.
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Calculate relative luminance according to WCAG 2.1.
     * Formula: https://www.w3.org/WAI/GL/wiki/Relative_luminance
     */
    private function calculateLuminance(array $rgb): float
    {
        [$r, $g, $b] = $rgb;
        
        // Normalize RGB values to 0-1
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;
        
        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}

