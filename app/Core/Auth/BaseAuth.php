<?php

declare(strict_types=1);

/**
 * BaseAuth class
 * Create Authorization header (JWT, Sodium or Sodium-v4)
 * with access_token and refresh_token
 * to validate the logged in user to match a valid sessions and certain permissions.
 * @author LutviP19 <lutvip19@gmail.com>
 */

namespace App\Core\Auth;

use App\Core\Support\Session;
use App\Core\Auth\Gate;

class BaseAuth
{
    protected static ?string $globalDefaultToken = null;
    protected ?string $driver = null;

    protected int $expToken;
    protected int $refreshTokenTimeout;
    protected int $leeway = 60; // Toleransi perbedaan waktu antar server (60 detik)

    /**
     * Custom Keys
     */
    protected ?string $privateKey = null;
    protected ?string $publicKey = null;

    /**
     * Driver instances (Lazy Loaded)
     */
    protected mixed $jwt = null;
    protected mixed $sodium = null;
    protected mixed $sodiumv4 = null;

    public function __construct(
        ?string $driver = null,
        int $expToken = 0,
        int $refreshTokenTimeout = 7
    ) {
        // Fallback config jika nilai <= 0
        $this->expToken = $expToken > 0 ? $expToken : (int) config("session.exptime", 7200);
        $this->refreshTokenTimeout = $refreshTokenTimeout > 0 ? $refreshTokenTimeout : (int) config("auth.refresh_timeout", 7);

        // Hirarki Prioritas Driver:
        // 1. Parameter Constructor (Explicit Instance)
        // 2. Static Property Class Turunan (Overridden by Child Class)
        // 3. Static Global Driver
        // 4. Config Default
        $this->driver = $driver
             ?? static::$globalDefaultToken
             ?? config('auth.default');
        // dd($this->driver);

        $this->validateDriver($this->driver);
    }

    /**
     * Validasi driver yang diperbolehkan
     */
    protected function validateDriver(string $driver): void
    {
        if (!in_array($driver, ['JWT', 'Sodium', 'Sodium-v4'], true)) {
            throw new \InvalidArgumentException("Only allow JWT, Sodium or Sodium-v4 as auth token driver.");
        }
    }

    /**
     * Method Chaining Setter: Set custom keys secara dinamis per-instance di Controller ataupun Class Turunan
     */
    public function setCustomKeys(?string $privateKey = null, ?string $publicKey = null): static
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        return $this; // Mendukung method chaining
    }

    /**
     * Method Chaining Setter: Set driver secara dinamis per-instance di Controller
     */
    public function setDriver(string $driver): static
    {
        $this->validateDriver($driver);
        $this->driver = $driver;
        return $this; // Mendukung method chaining
    }

    /**
     * Set driver default secara global (Statik)
     */
    public static function setGlobalDefaultToken(?string $driver = null): void
    {
        static::$globalDefaultToken = $driver;
    }


    /**
     * Set durasi token kadaluarsa (dalam detik)
     */
    public function setExpToken(int $seconds): static
    {
        $this->expToken = $seconds;
        return $this;
    }

    /**
     * Set timeout refresh token (dalam hari)
     */
    public function setRefreshTokenTimeout(int $days): static
    {
        $this->refreshTokenTimeout = $days;
        return $this;
    }

    /**
     * Set timeout refresh token (dalam hari)
     */
    public function getRefreshTokenTimeout(): int
    {
        return $this->refreshTokenTimeout;
    }

    /**
     * Shortcut setter untuk durasi token & refresh token sekaligus
     */
    public function setExpiration(int $expTokenInSeconds, int $refreshTokenTimeoutInDays = 7): static
    {
        $this->expToken = $expTokenInSeconds;
        $this->refreshTokenTimeout = $refreshTokenTimeoutInDays;
        return $this;
    }

    protected function getJwtDriver(): mixed
    {
        return $this->jwt ??= new JWT($this->privateKey);
    }

    protected function getSodiumDriver(): mixed
    {
        return $this->sodium ??= new SodiumAuth($this->privateKey);
    }

    protected function getSodiumV4Driver(): mixed
    {
        return $this->sodiumv4 ??= new SodiumAuthV4($this->privateKey, $this->publicKey);
    }

    public function generateToken(?array $payload = []): array
    {
        return match ($this->driver) {
            'JWT'       => $this->generateTokenJWT($payload),
            'Sodium'    => $this->generateTokenSodium($payload),
            'Sodium-v4' => $this->generateTokenSodiumV4($payload),
            default     => throw new \InvalidArgumentException("Unsupported auth driver: {$this->driver}")
        };
    }

    public function validateToken(string $token, ?string $permission = null): mixed
    {
        return match ($this->driver) {
            'JWT'       => $this->validateJWT($token, $permission),
            'Sodium'    => $this->authorizeSodium($token, $permission),
            'Sodium-v4' => $this->authorizeSodiumV4($token, $permission),
            default     => throw new \InvalidArgumentException("Unsupported auth driver: {$this->driver}")
        };
    }

    /**
     * Refreshes access token & refresh token berdasarkan driver yang aktif
     *
     * @param string $refreshToken Raw token dari request
     * @return array Contains 'access_token' and 'refresh_token'
     */
    public function refreshToken(?string $refreshToken, ?array $payload = []): array
    {
        return match ($this->driver) {
            'JWT'       => $this->refreshJwtToken($refreshToken, $payload),
            'Sodium'    => $this->refreshSodiumToken($refreshToken, $payload),
            'Sodium-v4' => $this->refreshSodiumV4Token($refreshToken, $payload),
            default     => throw new \InvalidArgumentException("Unsupported auth driver: {$this->driver}")
        };
    }

    protected function generateTokenJWT(?array $payload = []): array
    {
        // JWT
        $driver = $this->getJwtDriver();

        return [
            "access_token" => $driver->encode($this->setDefaultPayload($payload, "access")),
            "refresh_token" => $driver->encode($this->setDefaultPayload($payload, "refresh")),
        ];
    }

    protected function validateJWT(?string $token = null, ?string $permission = null): mixed
    {
        // JWT
        $token = $token ?? \getBearerToken();

        if (empty($token)) {
            return null;
        }

        $decoded = $this->getJwtDriver()->decode($token);

        if (!$decoded) {
            return null;
        }

        if (time() > (int) $decoded['exp'] + $this->leeway) {
            Gate::unauthorized_response('Session has expired please re-login.');
        }

        if ($permission !== null) {
            if (!Gate::authorize($permission)) {
                Gate::forbidden_response($permission);
            }

            return $decoded['user_permissions'] ?? [];
        }

        return $decoded;
    }

    protected function refreshJwtToken(string $refreshToken, ?array $payload = []): array
    {
        $payloadRefresh = $this->getJwtDriver()->decode($refreshToken);
        $payload = array_merge($payloadRefresh, $payload);

        $this->validateRefreshTokenPayload($payloadRefresh);
        
        return $this->reissueTokens($payload, $this->getJwtDriver());
    }

    protected function generateTokenSodium(?array $payload = []): array
    {
        // Sodium
        $driver = $this->getSodiumDriver();

        // Generate Token Sodium
        return [
            "access_token" => $driver->encode($this->setDefaultPayload($payload, "access")),
            "refresh_token" => $driver->encode($this->setDefaultPayload($payload, "refresh")),
        ];
    }

    protected function authorizeSodium(?string $token = null, ?string $permission = null): mixed
    {
        $token = $token ?? \getBearerToken();

        if (empty($token)) {
            return null;
        }

        // Cara Mengecek Permission di Sisi Server
        $decoded = $this->getSodiumDriver()->decode($token);

        if (!$decoded || !isset($decoded["exp"])) {
            // Middleware - Rate limiter
            $identifier = "auth:authorizeSodium:".\get_device_fingerprint(true, Session::get("uid"));
            $this->setRatelimiter($identifier, 60, 5);

            return null;
        }

        if (time() > (int) $decoded['exp'] + $this->leeway) {
            // TODO: validate Refresh Token

            // else:
            Gate::unauthorized_response('Session has expired please re-login.');
        }

        if ($permission !== null) {
            if (!Gate::authorize($permission)) {
                Gate::forbidden_response($permission);
            }

            $uid = $decoded['uid'] ?? Session::get("uid");
            return cacheContent('get', "user_abilities:{$uid}") ?? $decoded['user_permissions'] ?? [];
        }

        return $decoded;
    }

    protected function refreshSodiumToken(string $refreshToken, array $payload = []): array
    {
        $payloadRefresh = $this->getSodiumDriver()->decode($refreshToken);
        $payload = array_merge($payloadRefresh, $payload);

        $this->validateRefreshTokenPayload($payload);
        
        return $this->reissueTokens($payload, $this->getSodiumDriver());
    }

    protected function generateTokenSodiumV4(array $payload = []): array
    {
        // Sodium v4
        $driver = $this->getSodiumV4Driver();

        // Generate Token Sodium
        return [
            "access_token" => $driver->encode($this->setDefaultPayload($payload, "access")),
            "refresh_token" => $driver->encode($this->setDefaultPayload($payload, "refresh")),
        ];
    }

    protected function authorizeSodiumV4(?string $token = null, ?string $permission = null): mixed
    {
        // Sodium v4
        $token = $token ?? \getBearerToken();

        if (empty($token)) {
            return null;
        }

        $decoded = $this->getSodiumV4Driver()->decode($token);

        if (!$decoded || !isset($decoded["exp"])) {
            // Middleware - Rate limiter
            $identifier = "auth:authorizeSodiumV4:".\get_device_fingerprint(true, Session::get("uid"));
            $this->setRatelimiter($identifier, 60, 5);

            return null;
        }

        if (time() > (int) $decoded['exp'] + $this->leeway) {
            Gate::unauthorized_response('Session has expired please re-login.');
        }

        if ($permission !== null) {
            if (!Gate::authorize($permission)) {
                Gate::forbidden_response($permission);
            }

            return $decoded['user_permissions'] ?? [];
        }

        return $decoded;
    }

    protected function refreshSodiumV4Token(string $refreshToken, ?array $payload): array
    {
        $payloadRefresh = $this->getSodiumV4Driver()->decode($refreshToken);
        $payload = array_merge($payloadRefresh, $payload);

        $this->validateRefreshTokenPayload($payload);
        
        return $this->reissueTokens($payload, $this->getSodiumV4Driver());
    }

    /**
     * Validasi integritas dan tipe payload Refresh Token
     */
    protected function validateRefreshTokenPayload(?array $payload): void
    {
        $isValid = $payload
            && isset($payload['type'], $payload['uid'], $payload['fingerprint'])
            && $payload['type'] === 'refresh';

        // DEBUG
        write_log('debug', $payload, 'BaseAuth.validateRefreshTokenPayload');

        // Fingerprint
        if (isset($payload["uid"]) && isset($payload["fingerprint"])) {
            $fingerprint = get_device_fingerprint(false, $payload["uid"]);
            if (!matchHashData($fingerprint, $payload["fingerprint"])) {
                $isValid = false;
            }
        }

        // Cek juga expired time jika ada
        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            $isValid = false;
        }

        if (!$isValid) {
            Gate::unauthorized_response('Invalid or expired Refresh Token.');
        }
    }

    /**
     * Menghasilkan pasang Access & Refresh Token baru (Token Rotation)
     */
    protected function reissueTokens(array $payload, mixed $driver): array
    {
        // Regenerate sessions
        $uid = $payload['uid'] ?? Session::get("uid");
        $user = (new \App\Models\User())->getUserByUlid($uid);

        if (!isset($user->ulid, $user->email)) {
            // Middleware - Rate limiter
            $identifier = "auth:reissueTokens:".\get_device_fingerprint(extra: $uid);
            $this->setRatelimiter($identifier, 60, 5);

            Gate::unauthorized_response('Failed reissue token, please re-login.');
        }

        // Generate new Token via Driver
        return [
            "access_token" => $driver->encode($this->setDefaultPayload($payload, "access")),
            "refresh_token" => $driver->encode($this->setDefaultPayload($payload, "refresh")),
        ];
    }

    protected function setDefaultPayload(?array $payload = [], string $type = 'access'): array
    {
        $now = time();
        $authId = Session::get("authId") ?? generateUlid();
        $uid = $payload['uid'] ?? Session::get("uid");
        $user_permissions = cacheContent('get', "user_abilities:{$uid}");
        // dd($authId);

        // // loadAbilities
        // if(Session::has('user_id') && Session::has('current_team_id')) {
        //     $user_id = (int) Session::get("authId");
        //     $current_team_id = (int) Session::get("current_team_id");


        // }

        // // Simpan ke DB jika belum ada ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

        // Default payload
        // $expToken = time() + 60 * $this->expToken;
        $payloadDefault = [
            "uid" => $uid,
            "jti" => $authId,
            'role' => $payload['role'] ?? 'access_backend',
            "fingerprint" => get_device_fingerprint(true, $uid),
            "iat" => $now,
            "iss" => "backend-php",
            "aud" => config("app.url"),
            // "user_permissions" => $user_permissions,
        ];
        $fullPayload = array_merge($payloadDefault, $payload);
        // dd($fullPayload, true);

        $tokenPayload = [];
        if ($type === 'access') {
            $tokenPayload = array_merge($fullPayload, [
                "type" => "access",
                "exp" => $now + $this->expToken, // default: 2 Jam
            ]);
        } else {
            $tokenPayload = array_merge($fullPayload, [
                "type" => "refresh",
                "exp" => $now + (86400 * $this->refreshTokenTimeout), // 7 Hari
            ]);
        }

        return $this->cleanPayload($tokenPayload, $type);
    }

    private function setRatelimiter(?string $identifier, int $perSeconds = 120, int $limit = 10): void
    {
        $identifier = str_replace(" ", "_", $identifier);
        $identifier = $identifier . "-" . \clientIP();
        if (false === checkRateLimit($identifier, $limit, $perSeconds)) {
            $after = $perSeconds / 60;
            $afteText = $after > 1 ? "{$after} minutes" : "{$after} minute";
            $errors = [
                "busy" => ["Please try again after {$afteText}."],
            ];

            json_response(null, 429, "Too many requests", $errors);
        }
    }

    /**
     * Memfilter payload berdasarkan tipe token untuk menjaga efisiensi dan keamanan.
     * Refresh token hanya butuh identitas dasar untuk regenerasi access token.
     */
    private function cleanPayload(array $payload, string $type): array
    {
        if ($type === "access") {
            return $payload; // Access token tetap lengkap
        }

        // Daftar key yang diperbolehkan ada di Refresh Token
        $allowedForRefresh = ["uid", "jti", "type", "exp", "iat", "iss", "fingerprint"];

        return array_intersect_key($payload, array_flip($allowedForRefresh));
    }
}
