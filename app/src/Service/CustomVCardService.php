<?php

namespace App\Service;

use App\Entity\Card;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Custom vCard Service for better mobile compatibility and debugging
 * Generates vCard 3.0 format (more compatible with iOS/Android than 4.0)
 */
class CustomVCardService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Generate vCard 3.0 content for a Card entity
     * Uses manual string building for better control and debugging
     *
     * @param Card $card The card entity to convert
     * @return string vCard 3.0 formatted string
     * @throws Exception
     */
    public function generate(Card $card): string
    {
        try {
            $content = $card->getContent();
            $lines = [];

            // BEGIN:VCARD - Required
            $lines[] = 'BEGIN:VCARD';

            // VERSION:3.0 - Use 3.0 for better mobile compatibility
            $lines[] = 'VERSION:3.0';

            // FN (Full Name) - Required (this is what most apps display as the main name)
            $fullName = $content['name'] ?? 'Contact';
            $this->addEscapedLine($lines, 'FN', $fullName);

            // N (Name) - Structured name (Family;Given;Additional;Prefix;Suffix)
            // Note: Don't escape semicolons in N value as they are field separators
            $nameParts = $this->parseNameParts($fullName);
            $nValue = sprintf('%s;%s;%s;%s;%s',
                $nameParts['family'],
                $nameParts['given'],
                $nameParts['additional'],
                $nameParts['prefix'],
                $nameParts['suffix']
            );
            $lines[] = 'N:' . $nValue;

            // EMAIL - Use INTERNET type for better mobile compatibility
            if (!empty($content['email'])) {
                $this->addEscapedLine($lines, 'EMAIL', $content['email'], ['TYPE' => 'INTERNET']);
            }

            // TEL (Telephone) - Use CELL type for better mobile compatibility
            if (!empty($content['phone'])) {
                // Normalize phone number (remove spaces, dashes, etc.)
                $phone = preg_replace('/[\s\-\(\)\.]/', '', $content['phone']);
                // Use CELL type which is more universally recognized than WORK
                $this->addEscapedLine($lines, 'TEL', $phone, ['TYPE' => 'CELL,VOICE']);
            }

            // ORG (Organization)
            if (!empty($content['company'])) {
                $this->addEscapedLine($lines, 'ORG', $content['company']);
            }

            // TITLE (Job Title)
            if (!empty($content['title'])) {
                $this->addEscapedLine($lines, 'TITLE', $content['title']);
            }

            // URL (Website) - Use HOME type or no type for better compatibility
            if (!empty($content['website'])) {
                // Don't use TYPE=WORK, use a more generic approach
                $this->addEscapedLine($lines, 'URL', $content['website']);
            }

            // NOTE (Bio/Description)
            if (!empty($content['bio'])) {
                $this->addEscapedLine($lines, 'NOTE', $content['bio']);
            }

            // Add social profiles
            $this->addSocialProfiles($lines, $content);

            // END:VCARD - Required
            $lines[] = 'END:VCARD';

            // Join lines with CRLF (required by vCard spec and iOS/Android)
            $vcardString = implode("\r\n", $lines);

            // Ensure the vCard ends with CRLF
            if (!str_ends_with($vcardString, "\r\n")) {
                $vcardString .= "\r\n";
            }

            // Log for debugging
            $this->logger->debug('Generated vCard', [
                'card_id' => $card->getId(),
                'card_slug' => $card->getSlug(),
                'vcard_length' => strlen($vcardString),
                'vcard_preview' => substr($vcardString, 0, 200) . '...',
            ]);

            return $vcardString;
        } catch (Exception $e) {
            $this->logger->error('Error generating vCard', [
                'card_id' => $card->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a filename for the vCard download
     *
     * @param Card $card The card entity
     * @return string Normalized filename (e.g., "contact-john-doe.vcf")
     */
    public function generateFilename(Card $card): string
    {
        $content = $card->getContent();
        $name = $content['name'] ?? 'contact';

        // Normalize and slugify the name
        $normalized = $this->slugify($name);

        return sprintf('contact-%s.vcf', $normalized);
    }

    /**
     * Add social profiles to vCard lines array
     * Uses URL with TYPE=social for better compatibility
     *
     * @param array $lines Reference to lines array
     * @param array $content Card content array
     */
    private function addSocialProfiles(array &$lines, array $content): void
    {
        if (empty($content['social']) || !is_array($content['social'])) {
            return;
        }

        // Known platforms mapping
        $platformNames = [
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'facebook' => 'Facebook',
            'x' => 'X (Twitter)',
            'snapchat' => 'Snapchat',
            'planity' => 'Planity',
            'bluebirds' => 'Bluebirds',
            'other' => 'Other',
        ];

        $itemIndex = 1;
        foreach ($content['social'] as $platform => $url) {
            if (empty($url)) {
                continue;
            }

            $platformName = $platformNames[$platform] ?? ucfirst($platform);

            // Use URL with X-ABLabel for better iPhone compatibility
            // X-SOCIALPROFILE is not well supported on iOS
            // Group URL and label using itemN notation
            $lines[] = 'item' . $itemIndex . '.URL:' . $this->escapeValueOnly($url);
            $lines[] = 'item' . $itemIndex . '.X-ABLabel:' . $this->escapeValueOnly($platformName);
            $itemIndex++;
        }
    }

    /**
     * Add an escaped vCard line to the lines array, handling line folding
     *
     * @param array $lines Reference to lines array
     * @param string $property Property name
     * @param string $value Value to escape
     * @param array $params Optional parameters (e.g., ['TYPE' => 'WORK'])
     */
    private function addEscapedLine(array &$lines, string $property, string $value, array $params = []): void
    {
        // Escape special characters: \, ; , \n
        $escaped = $this->escapeValueOnly($value);

        // Build parameter string
        $paramString = '';
        if (!empty($params)) {
            $paramParts = [];
            foreach ($params as $key => $val) {
                $paramParts[] = sprintf('%s=%s', $key, $val);
            }
            $paramString = ';' . implode(';', $paramParts);
        }

        // Build line
        $line = $property . $paramString . ':' . $escaped;

        // Fold long lines (vCard spec: max 75 chars per line)
        if (strlen($line) > 75) {
            $remaining = $line;
            $firstLine = true;

            while (strlen($remaining) > 75) {
                $chunk = substr($remaining, 0, 75);
                $remaining = substr($remaining, 75);

                if ($firstLine) {
                    $lines[] = $chunk;
                    $firstLine = false;
                } else {
                    // Folded lines start with a space
                    $lines[] = ' ' . $chunk;
                }
            }

            if (!empty($remaining)) {
                if ($firstLine) {
                    $lines[] = $remaining;
                } else {
                    $lines[] = ' ' . $remaining;
                }
            }
        } else {
            $lines[] = $line;
        }
    }

    /**
     * Escape only the value part (not the whole line)
     * Handles special characters according to RFC 2426
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    private function escapeValueOnly(string $value): string
    {
        // Escape special characters: \, ; , \n
        return str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $value);
    }

    /**
     * Parse name into structured parts for vCard N property
     * Improved parsing for French names and compound names
     *
     * @param string $fullName Full name string (e.g., "Marguerite Rodrigues" or "Jean-Pierre Dupont")
     * @return array ['family', 'given', 'additional', 'prefix', 'suffix']
     */
    private function parseNameParts(string $fullName): array
    {
        $fullName = trim($fullName);

        if (empty($fullName)) {
            return ['', '', '', '', ''];
        }

        // Split by spaces, preserving multiple spaces as single separator
        $parts = preg_split('/\s+/', $fullName);

        if (empty($parts)) {
            return ['', '', '', '', ''];
        }

        // If only one part, treat it as family name
        if (count($parts) === 1) {
            return [
                'family' => $parts[0],
                'given' => '',
                'additional' => '',
                'prefix' => '',
                'suffix' => '',
            ];
        }

        // For multiple parts, use improved logic:
        // - Last part is typically the family name
        // - All parts before are given names (handles compound first names like "Jean-Pierre")
        $family = array_pop($parts);
        $given = implode(' ', $parts);

        return [
            'family' => $family ?? '',
            'given' => $given ?? '',
            'additional' => '',
            'prefix' => '',
            'suffix' => '',
        ];
    }

    /**
     * Slugify a string for use in filenames
     *
     * @param string $text Text to slugify
     * @return string Slugified text
     */
    private function slugify(string $text): string
    {
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text));
        // Remove leading/trailing hyphens
        $text = trim($text, '-');
        // Collapse multiple hyphens
        $text = preg_replace('/-+/', '-', $text);

        return $text ?: 'contact';
    }
}
