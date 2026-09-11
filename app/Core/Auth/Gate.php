<?php

declare(strict_types=1);

/**
 * Gate class
 * This class functions as a wrapper to check whether the logged in user has certain permissions.
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Auth;

use App\Core\Support\Cache;
use App\Core\Support\CacheSwoole;
use App\Core\Support\Session;
// use App\Core\Database\BaseModel;
use App\Models\User;

class Gate
{
    protected static $abilities = [];

    /**
     * Inisialisasi izin user ke dalam static property
     */
    public static function loadAbilities(mixed $userId = null, mixed $teamId = null): void
    {
        $uid = Session::get('uid');
        if (!$uid) {
            return;
        }

        $expDefault = config('session.exptime', 7200);
        $cache = \isSwoole() ? new CacheSwoole($expDefault) : new Cache($expDefault);
        $userId = $userId ?? Session::get('user_id');
        $teamId = $teamId ?? Session::get('current_team_id');
        $cacheKey = "user_abilities:{$uid}";

        // cast decryptData
        $userId = !is_int($userId) ? decryptData($userId) : $userId;

        static::$abilities = $cache->remember(
            $cacheKey,
            function () use ($userId, $teamId) {
                // Session::unset('user_id');
                $rows = (array) User::rawQuery(
                    "SELECT permission_name AS slug FROM v_user_permissions 
                                        WHERE user_id = :user_id AND current_team_id = :current_team_id",
                    [
                                            ':user_id' => (int) $userId,
                                            ':current_team_id' => $teamId !== null ? (int) $teamId : null
                                        ]
                );
                return array_column($rows, "slug");
            },
            config('session.exptime', 7200)
        );
        // \App\Core\Support\Log::debug($userId, 'App\Core\Auth\Gate.loadAbilities.$userId');
        // \App\Core\Support\Log::debug(static::$abilities, 'App\Core\Auth\Gate.loadAbilities.$abilities');
    }

    /**
     * Cek apakah user punya izin spesifik
     */
    public static function allows($permission): bool
    {
        return in_array($permission, static::$abilities);
    }

    /**
     * Kebalikan dari allows
     */
    public static function denies($permission): bool
    {
        return !static::allows($permission);
    }

    /**
     * Proteksi di level Model (langsung stop jika tidak punya akses)
     */
    public static function authorize($permission): bool
    {
        if (static::denies($permission)) {
            // static::forbidden_response($permission);
            return false;
        }

        return true;
    }

    /**
     * Response Helper jika tidak login (401)
     */
    public static function unauthorized_response(?string $msg)
    {
        if (is_json_request()) {
            $message = "Unauthorized";
            $errors = [
                "auth" => "Unauthorized: " . $msg,
            ];

            json_response(null, 401, $message, $errors);
        } else {
            $statusCode = isHtmx() ? 200 : 401;

            $output = file_get_contents(BASEPATH . "views/error/401.php");
            htmx_response($output, $statusCode);
        }
    }

    /**
     * Response Helper jika tidak punya izin (403)
     */
    public static function forbidden_response(?string $permission)
    {
        if (is_json_request()) {
            $message = "You don't have access[$permission]";
            $errors = [
                "auth" => "Forbidden to access: " . $permission,
            ];

            json_response(null, 403, $message, $errors);
        } else {
            $statusCode = isHtmx() ? 200 : 401;

            $output = file_get_contents(BASEPATH . "views/error/403.php");
            htmx_response($output, $statusCode);
        }
    }

    //==================================================
    //========= End Class
    //==================================================


    /**
     * Middleware untuk memvalidasi akses user menggunakan JWT
     * @param string|null $requiredPermission Permission yang dibutuhkan (opsional)
     */
    public static function authorizeJwt(?string $requiredPermission = null, ?string $secret = null): ?array
    {
        // $headers = getallheaders();
        // $authHeader = $headers["Authorization"] ?? ($headers["authorization"] ?? "");

        // // Parse Bearer Token
        // $token = "";
        // if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        //     $token = $matches[1];
        // }

        // // Jika token kosong, cek dari Cookie (fallback umum)
        // if (!$token && isset($_COOKIE["auth_token"])) {
        //     $token = $_COOKIE["auth_token"];
        // }

        // if (!$token) {
        //     static::unauthorized_response("Invalid token.");
        // }

        // Parse Bearer Token
        $token = static::extractToken();

        $secret ??= config("auth.jwt_secret");
        $jwt = new JWT($secret);
        $userData = $jwt->decode($token);

        // Verifikasi Token
        if (!$userData) {
            static::unauthorized_response("Session has expired please re-login.");
        }

        // Verifikasi Permission (jika diminta)
        if ($requiredPermission) {
            $permissions = $userData["user_permissions"] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                static::forbidden_response($requiredPermission);
            }
        }

        return $userData;
    }

    /**
     * Middleware untuk memvalidasi akses user menggunakan Sodium
     * @param string|null $requiredPermission Permission yang dibutuhkan (opsional)
     */
    public static function authorizeSodium(?string $requiredPermission = null, ?string $hexKey = null): ?array
    {
        // $headers = getallheaders();
        // $authHeader = $headers["Authorization"] ?? ($headers["authorization"] ?? "");

        // // Parse Bearer Token
        // $token = "";
        // if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        //     $token = $matches[1];
        // }

        // // Jika token kosong, cek dari Cookie (fallback umum)
        // if (!$token && isset($_COOKIE["auth_token"])) {
        //     $token = $_COOKIE["auth_token"];
        // }

        // if (!$token) {
        //     static::unauthorized_response("Invalid token.");
        // }

        // Parse Bearer Token
        $token = static::extractToken();

        $hexKey ??= config("auth.sodium_key");
        $auth = new SodiumAuth($hexKey);
        $userData = $auth->decode($token);

        // Verifikasi Token
        if (!$userData || !isset($userData["exp"])) {
            static::unauthorized_response("Session has expired please re-login.");
        }

        // Cek exp
        if ($userData && isset($userData["exp"])) {
            $leeway = 60; // Toleransi perbedaan waktu antar server (60 detik)
            if (time() > $userData["exp"] + $leeway) {
                static::unauthorized_response("Session has expired please re-login.");
            }
        }

        // // Cek Type refresh
        // if ($userData && isset($userData['type'])) {
        //     if($userData['type'] === 'refresh')
        //         return static::refreshSodiumAuth($userData, $hexKey);
        // }

        // Verifikasi Permission (jika diminta)
        if ($requiredPermission) {
            $permissions = $userData["user_permissions"] ?? [];
            if (!in_array($requiredPermission, $permissions)) {
                static::forbidden_response($requiredPermission);
            }
        }

        return $userData;
    }

    public static function refreshSodiumAuth(string $refreshToken, ?string $hexKey = null): ?array
    {
        $hexKey ??= config("auth.sodium_key");
        $sodiumAuth = new SodiumAuth($hexKey);

        // 1. Decode & Verify Signature Refresh Token
        $payload = $sodiumAuth->decode($refreshToken);
        $isValid = isset($payload["type"]) && isset($payload["iis"]) && isset($payload["fingerprint"]);

        if (!$payload || !$isValid || $payload["type"] !== "refresh") {
            static::unauthorized_response("Invalid Refresh Token");
        }

        // // 2. Cek di Redis (Whitelist)
        // // Ini penting agar Refresh Token bisa di-revoke jika user logout/ban
        // $stored = $this->redis->get("sso_refresh_map:" . $payload['uid']);
        // if ($stored !== $refreshToken) {
        //     static::unauthorized_response("Refresh Token Revoked");
        // }

        // Default payload
        $expToken = time() + 60 * (config("session.lifetime") / 2);
        $payloadDefault = [
            "uid" => $payload["uid"],
            "role" => $payload["role"],
            "fingerprint" => get_device_fingerprint(),
            "iat" => time(),
            "iss" => "php-microdata",
            "aud" => "users",
        ];
        $fullPayload = array_merge($payloadDefault, $payload);

        // 3. Generate Access Token Baru (Pendek: 1 jam)
        $newAccessToken = array_merge($fullPayload, [
            "type" => "access",
            "exp" => $expToken,
        ]);

        // 4. Generate Refresh Token Baru (Rotasi - Opsional tapi disarankan)
        $newRefreshToken = array_merge($fullPayload, [
            "type" => "refresh",
            "exp" => time() + 3600 * 24 * 7, // 7 Hari
        ]);

        // // 5. Update Redis Whitelist
        // $this->redis->setex("sso_refresh_map:" . $payload['uid'], 86400 * 7, $newRefreshToken);

        $dataNewToken = [
            "access_token" => $sodiumAuth->encode(cleanSodiumPayload($newAccessToken, "access")),
            "refresh_token" => $sodiumAuth->encode(cleanSodiumPayload($newRefreshToken, "refresh")),
        ];

        // Generate New Token Sodium
        return $dataNewToken;
    }

    /**
     * Ekstraksi token dari Header (Authorization) atau Cookie
     */
    private static function extractToken()
    {
        $authHeader = null;

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        // C. Fallback getallheaders()
        elseif (function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders(), CASE_LOWER);
            $authHeader = $headers['authorization'] ?? null;
        }

        // Parse Bearer Token
        if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Fallback Cookie
        if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }

        return static::unauthorized_response("Invalid token.");
    }
}
