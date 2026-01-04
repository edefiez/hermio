<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates social profile URLs based on platform-specific patterns
 * 
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SocialProfile extends Constraint
{
    public string $message = 'The URL "{{ value }}" is not a valid {{ platform }} profile URL.';
    public string $platform = '';

    public function __construct(
        string $platform = '',
        string $message = null,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
        
        $this->platform = $platform;
        if ($message !== null) {
            $this->message = $message;
        }
    }
}
