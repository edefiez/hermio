<?php

namespace App\Service;

/**
 * Service for generating cryptographically secure random keys
 * Used for public card access keys
 */
class SecureKeyGenerator
{
    /**
     * Generate a cryptographically secure random key
     *
     * @param int $length Length of the generated key (default: 48 characters)
     * @return string Base64url-encoded random key
     * @throws \Exception If random_bytes fails
     */
    public function generateRandomKey(int $length = 48): string
    {
        // Calculate bytes needed (base64url encoding produces ~4/3 chars per byte)
        $bytesNeeded = (int) ceil(($length * 3) / 4);
        
        // Generate cryptographically secure random bytes
        $randomBytes = random_bytes($bytesNeeded);
        
        // Encode to base64url (URL-safe variant without padding)
        $base64 = base64_encode($randomBytes);
        $base64url = strtr($base64, '+/', '-_');
        $base64url = rtrim($base64url, '=');
        
        // Return exact length requested
        return substr($base64url, 0, $length);
    }

    /**
     * Generate a HMAC-based derived key (alternative approach)
     * This key is deterministic based on input data and doesn't need storage
     *
     * @param string $secret Application secret
     * @param string $data Data to hash (e.g., "user_id:card_id:created_at")
     * @return string HMAC-SHA256 hash in base64url format
     */
    public function generateDerivedKey(string $secret, string $data): string
    {
        $hmac = hash_hmac('sha256', $data, $secret, true);
        $base64url = strtr(base64_encode($hmac), '+/', '-_');
        return rtrim($base64url, '=');
    }
}
