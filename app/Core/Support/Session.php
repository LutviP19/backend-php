<?php

namespace App\Core\Support;

use App\Core\Support\Config;

/**
 * Handle all the stuff related to session.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */
class Session
{
    /**
     * Get all session values (excluding metadata/system variables).
     *
     * @return array
     */
    public static function all()
    {
        $sessions = [];
        $csrfTokenKey = (string) Config::get("session.csrf_token", 'csrf_token');
        
        $escaped = [
            $csrfTokenKey,
            "OBSOLETE",
            "EXPIRES",
            "nonce",
            "new_session_id",
            "destroyed",
            "userAgent",
            "IPaddress",
            "password",
            "pin",
            "errors",
            "secret",
            "jwtId",
            "tokenJwt",
            "gnr",
            "_previous_uri",
            "_old_input",
        ];

        $isEncrypted = (bool) config("session.encrypt");

        // Pastikan $_SESSION ada dan tipenya array sebelum dilooping
        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (in_array($key, $escaped)) {
                    continue;
                }

                $data = $isEncrypted ? decryptData($value) : $value;
                if (is_null($data) || $data === '') {
                    continue;
                }

                // OPTIMASI: Parsing JSON yang seragam, aman, dan bersih
                if (is_string($data)) {
                    $decoded = json_decode($data, true);
                    $sessions[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $data;
                } else {
                    $sessions[$key] = $data;
                }
            }
        }

        return $sessions;
    }

    /**
     * Get a session value by key.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        if (!self::has($key)) {
            return "";
        }

        $value = $_SESSION[$key];

        if (config("session.encrypt")) {
            $decrypted = decryptData($value);
            if (is_string($decrypted)) {
                $decoded = json_decode($decrypted, true);
                return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $decrypted;
            }
            return $decrypted;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $value;
        }

        return $value;
    }

    /**
     * Set a value (Compatible with string, array, and object).
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set($key, $value)
    {
        // Jika value berupa array atau objek, ubah ke JSON murni
        $processedValue = (is_array($value) || is_object($value)) 
            ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) 
            : $value;

        if (config("session.encrypt")) {
            $processedValue = encryptData($processedValue);
        }

        $_SESSION[$key] = $processedValue;

        return true;
    }

    /**
     * Determine if a value exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Unset/Remove a value.
     *
     * @param string $key
     * @param bool $recursive_unset
     * @return void
     */
    public static function unset($key, $recursive_unset = false)
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return;
        }

        if ($recursive_unset && function_exists('recursive_unset')) {
            recursive_unset($_SESSION, $key);
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Completely destroy the session but preserve CSRF Token.
     *
     * @return void
     */
    public static function destroy()
    {
        $key = (string) Config::get("session.csrf_token", 'csrf_token');
        $csrfToken = self::get($key);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            $_SESSION = [];
            session_destroy();
        }

        // Jalankan bp_session_start() atau session_start() jika fungsi ini dipanggil 
        // agar data token baru bisa disimpan ke media penyimpanan (storage)
        if (function_exists('bp_session_start')) {
            bp_session_start();
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        self::set($key, $csrfToken);
    }

    /**
     * Destroy the user session data only (soft wipe).
     *
     * @return void
     */
    public static function softDestroy()
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return;
        }

        $csrfTokenKey = (string) Config::get("session.csrf_token", 'csrf_token');
        $ignoreKeys = [$csrfTokenKey, "_previous_uri", "IPaddress", "userAgent"];
        
        foreach ($_SESSION as $key => $value) {
            if (!in_array($key, $ignoreKeys)) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Make the value available for the next request (Flash message style).
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public static function flash($key, $value = null)
    {
        if (self::has($key)) {
            $flash = self::get($key);
            self::unset($key);
            return $flash;
        }
        
        self::set($key, $value);
        return null;
    }

    /**
     * Get the previous uri stored in the session.
     *
     * @return string
     */
    public static function getPreviousUri()
    {
        return (string) self::get("_previous_uri");
    }

    /**
     * Set the previous uri in the session.
     *
     * @param string $uri
     * @return void
     */
    public static function setPreviousUri($uri)
    {
        self::set("_previous_uri", (string)$uri);
    }

    /**
     * Get the input value from the previous request.
     *
     * @param string $key
     * @return mixed
     */
    public static function getOldInput($key)
    {
        $oldInputs = self::get("_old_input");
        
        // PENTING: Cegah error "offset null / type error array" di PHP 8.1+
        if (is_array($oldInputs) && isset($oldInputs[$key])) {
            return $oldInputs[$key];
        }
        
        return "";
    }

    /**
     * Set the input (POST) values from the previous request.
     *
     * @return void
     */
    public static function setOldInput()
    {
        $inputs = [];
        
        if (isset($_POST) && is_array($_POST)) {
            foreach ($_POST as $input => $value) {
                // Gunakan helper e() yang aman (menggunakan htmlspecialchars bawaan revisi sebelumnya)
                if (function_exists('e')) {
                    $inputs[e($input)] = is_array($value) ? $value : e($value);
                } else {
                    $inputs[htmlspecialchars($input, ENT_QUOTES, 'UTF-8')] = is_array($value) ? $value : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        self::set("_old_input", $inputs);
    }
}