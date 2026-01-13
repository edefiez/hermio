<?php

namespace App\Service;

use App\Entity\Card;
use Psr\Cache\CacheItemPoolInterface;
use Sabre\VObject\Component\VCard;

/**
 * Service for generating vCard 4.0 files from Card entities
 * Implements RFC 6350 standard with social profile extensions
 */
class VCardService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'vcard_';

    public function __construct(
        private CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Generate vCard 4.0 content for a Card entity
     *
     * @param Card $card The card entity to convert
     * @return string vCard 4.0 formatted string
     */
    public function generate(Card $card): string
    {
        $cacheKey = self::CACHE_PREFIX . $card->getId();
        $cachedItem = $this->cache->getItem($cacheKey);

        if ($cachedItem->isHit()) {
            return $cachedItem->get();
        }

        $vcard = new VCard();
        $content = $card->getContent();

        // FN (Full Name) - Required by vCard 4.0
        $vcard->add('FN', $content['name'] ?? 'Contact');

        // N (Name) - Structured name (Family;Given;Additional;Prefix;Suffix)
        $nameParts = $this->parseNameParts($content['name'] ?? '');
        $vcard->add('N', $nameParts);

        // EMAIL
        if (!empty($content['email'])) {
            $vcard->add('EMAIL', $content['email'], ['type' => 'WORK']);
        }

        // TEL (Telephone)
        if (!empty($content['phone'])) {
            $vcard->add('TEL', $content['phone'], ['type' => ['WORK', 'VOICE']]);
        }

        // ORG (Organization)
        if (!empty($content['company'])) {
            $vcard->add('ORG', $content['company']);
        }

        // TITLE (Job Title)
        if (!empty($content['title'])) {
            $vcard->add('TITLE', $content['title']);
        }

        // URL (Website)
        if (!empty($content['website'])) {
            $vcard->add('URL', $content['website'], ['type' => 'WORK']);
        }

        // NOTE (Bio/Description)
        if (!empty($content['bio'])) {
            $vcard->add('NOTE', $content['bio']);
        }

        // Add social profiles
        $this->addSocialProfiles($vcard, $content);

        $vcardString = $vcard->serialize();

        // Ensure proper line endings (CRLF) for iOS compatibility
        // Sabre VObject may use different line endings, normalize to CRLF
        $vcardString = str_replace(["\r\n", "\n", "\r"], "\r\n", $vcardString);
        
        // Ensure the vCard ends with CRLF
        if (!str_ends_with($vcardString, "\r\n")) {
            $vcardString .= "\r\n";
        }

        // Cache the result
        $cachedItem->set($vcardString);
        $cachedItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cachedItem);

        return $vcardString;
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
     * Add social profiles to vCard
     * Uses X-SOCIALPROFILE for known platforms and URL with TYPE=social for others
     *
     * @param VCard $vcard The vCard object to modify
     * @param array $content Card content array
     */
    private function addSocialProfiles(VCard $vcard, array $content): void
    {
        // Known platforms that support X-SOCIALPROFILE
        $knownPlatforms = [
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'facebook' => 'Facebook',
            'x' => 'X',
            'snapchat' => 'Snapchat',
        ];

        // Add social fields from content['social'] array
        if (!empty($content['social']) && is_array($content['social'])) {
            foreach ($content['social'] as $platform => $url) {
                if (empty($url)) {
                    continue;
                }

                if (isset($knownPlatforms[$platform])) {
                    // Known platform - use X-SOCIALPROFILE
                    $vcard->add('X-SOCIALPROFILE', $url, [
                        'type' => $knownPlatforms[$platform],
                    ]);
                } else {
                    // Other platforms (planity, bluebirds, other) - use URL with TYPE=social
                    $vcard->add('URL', $url, [
                        'type' => 'social',
                        'X-PLATFORM' => ucfirst($platform),
                    ]);
                }
            }
        }
    }

    /**
     * Parse name into structured parts for vCard N property
     * Simple implementation: assumes "FirstName LastName" format
     *
     * @param string $fullName Full name string
     * @return array [Family, Given, Additional, Prefix, Suffix]
     */
    private function parseNameParts(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);
        $family = $parts[1] ?? '';
        $given = $parts[0] ?? '';

        return [$family, $given, '', '', ''];
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
