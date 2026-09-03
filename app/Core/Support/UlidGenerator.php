<?php

/**
 * ULID generator Class to Create a ULID .
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */

namespace App\Core\Support;

class UlidGenerator
{
    private const ENCODING_ULID_CHARS = "0123456789ABCDEFGHJKMNPQRSTVWXYZ";

    /**
     * Generate new ULID
     */
    public static function generate(): string
    {
        // 1. Take the time in milliseconds (48 bits)
        $time = (int) (microtime(true) * 1000);

        // 2. Encode Timestamp (10 characters)
        $timePart = self::encodeTime($time, 10);

        // 3. Generate Random Part (80 bits = 16 characters)
        $randomPart = self::encodeRandom(16);

        return $timePart . $randomPart;
    }

    private static function encodeTime(int $time, int $length): string
    {
        $chars = self::ENCODING_ULID_CHARS;
        $output = "";

        for ($i = $length - 1; $i >= 0; $i--) {
            $output = $chars[$time % 32] . $output;
            $time = (int) ($time / 32);
        }

        return $output;
    }

    private static function encodeRandom(int $length): string
    {
        $chars = self::ENCODING_ULID_CHARS;
        $output = "";

        // Using random_bytes to be secure cryptographically
        $bytes = random_bytes($length);

        for ($i = 0; $i < $length; $i++) {
            $output .= $chars[ord($bytes[$i]) % 32];
        }

        return $output;
    }

    /**
     * ULID format validation
     */
    public static function isValid(string $ulid): bool
    {
        return strlen($ulid) === 26 && strspn(strtoupper($ulid), self::ENCODING_ULID_CHARS) === 26;
    }
}
