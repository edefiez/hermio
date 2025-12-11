<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validator for social profile URLs
 * Validates URLs against platform-specific regex patterns
 */
class SocialProfileValidator extends ConstraintValidator
{
    /**
     * Platform-specific URL patterns
     * Each pattern validates the expected domain and URL structure
     */
    private const PATTERNS = [
        'linkedin' => '#^https?://(www\.)?linkedin\.com/(in|company)/[a-zA-Z0-9_-]+/?#i',
        'twitter' => '#^https?://(www\.)?twitter\.com/[a-zA-Z0-9_]{1,15}/?#i',
        'x' => '#^https?://(www\.)?(x\.com|twitter\.com)/[a-zA-Z0-9_]{1,15}/?#i',
        'instagram' => '#^https?://(www\.)?instagram\.com/[a-zA-Z0-9._]{1,30}/?#i',
        'tiktok' => '#^https?://(www\.)?tiktok\.com/@[a-zA-Z0-9_.]{1,24}/?#i',
        'facebook' => '#^https?://(www\.)?(facebook|fb)\.com/[a-zA-Z0-9.]{1,50}/?#i',
        'snapchat' => '#^https?://(www\.)?snapchat\.com/(add/)?[a-zA-Z0-9._-]{3,15}/?#i',
        'planity' => '#^https?://(www\.)?planity\.(com|fr)/.+#i',
        'bluebirds' => '#^https?://.+#i', // Generic URL validation for bluebirds
        'other' => '#^https?://.+#i', // Generic URL validation for other platforms
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SocialProfile) {
            throw new UnexpectedTypeException($constraint, SocialProfile::class);
        }

        // Null or empty values are valid (use NotBlank constraint to require a value)
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $platform = strtolower($constraint->platform);
        
        // If no platform specified or platform not recognized, skip validation
        if (empty($platform) || !isset(self::PATTERNS[$platform])) {
            return;
        }

        $pattern = self::PATTERNS[$platform];

        if (!preg_match($pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ platform }}', ucfirst($platform))
                ->addViolation();
        }
    }
}
