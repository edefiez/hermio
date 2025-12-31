<?php

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Custom JSON type for Doctrine that preserves UTF-8 characters
 *
 * This type ensures that JSON fields are stored with unescaped Unicode characters
 * (e.g., "François" instead of "Fran\u00e7ois") making the database content more readable
 * and maintaining better compatibility with external tools.
 */
class JsonType extends Type
{
    public const NAME = 'json_utf8';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * @throws \JsonException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        // Encode with JSON_UNESCAPED_UNICODE to preserve UTF-8 characters
        // JSON_UNESCAPED_SLASHES to avoid escaping forward slashes in URLs
        $encoded = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Error encoding JSON: ' . json_last_error_msg());
        }

        return $encoded;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Error decoding JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

