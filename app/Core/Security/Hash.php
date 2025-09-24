<?php

namespace App\Core\Security;

use App\Core\Support\Config;

class Hash
{
    /**
     * Create a MAC for the given value.
     *
     * @param  mixed  $value
     * @param  string  $key
     * @return string
     */
    public static function create(#[\SensitiveParameter] $value, #[\SensitiveParameter] $key = '')
    {
        $key = str_replace('base64:', '', (string) $key ?: Config::get('app.hash_key'));

        return base64_encode(hash_hmac('sha256', $value, $key));
    }

    /**
     * Verify a hash against a string.
     *
     * @param string $str
     * @param string $hash
     * @return bool
     */
    public static function matchHash(#[\SensitiveParameter] $str, $hash, #[\SensitiveParameter] $key = '')
    {
        return hash_equals(self::create($str, $key), $hash);
    }

    /**
     * Create a hash from string.
     *
     * @param string $str
     * @return string
     */
    public static function makePassword(#[\SensitiveParameter] $str)
    {
        return password_hash($str, PASSWORD_BCRYPT);
    }

    /**
     * Verify a hash against a string.
     *
     * @param string $str
     * @param string $hash
     * @return bool
     */
    public static function matchPassword(#[\SensitiveParameter] $str, $hash)
    {
        return password_verify($str, $hash);
    }

    /**
     * Create a unique hash.
     *
     * @param int $len
     * @return string
     */
    public static function unique($len = 32)
    {
        return bin2hex(random_bytes($len));
    }

    /**
     * Create a random string.
     *
     * @param int $len
     * @return string
     */
    public static function randomString($len = 64)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ*&!@%^#$';
        $strings = [];
        $max = mb_strlen($characters, '8bit') - 1;

        for ($i = 0; $i < $len; ++$i) {
            $strings[] = $characters[random_int(0, $max)];
        }

        return implode('', $strings);
    }
}
