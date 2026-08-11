<?php

namespace App\Core\Security;

class IdempotencyManager
{
    protected $cachePrefix = 'idempotent';

    /**
     * Periksa status key idempotency dari Cache.
     */
    public static function check(string $key): array
    {
        $cacheKey = $key;
        // $getCache = cacheContent('get', $cacheKey, 'idempotent');
        $getCache = cacheContent('get', $cacheKey);

        if (empty($getCache)) {
            return ['status' => 'NOT_FOUND'];
        }

        $data = is_array($getCache) ? $getCache : json_decode((string) $getCache, true);

        // Jika data tidak valid atau sudah kadaluarsa
        if (!$data || time() > ($data['expired_at'] ?? 0)) {
            // delCache($key, 'idempotent');
            delCache($key);
            return ['status' => 'NOT_FOUND'];
        }

        return [
            'status'    => $data['status'] ?? 'NOT_FOUND',
            'response'  => $data['response'] ?? '',
            'http_code' => $data['http_code'] ?? 200
        ];
    }

    /**
     * Kunci request key dengan status 'PROCESSING'.
     * Mengembalikan false jika request serupa sedang diproses atau sudah sukses.
     */
    public static function lock(string $key, int $ttl = 60): bool
    {
        $cacheKey = $key;
        // $getCache = cacheContent('get', $cacheKey, 'idempotent');
        $getCache = cacheContent('get', $cacheKey);

        if (!empty($getCache)) {
            $existing = is_array($getCache) ? $getCache : json_decode((string) $getCache, true);
            
            // Jika key terdeteksi dan belum kadaluarsa, kunci gagal didapatkan
            if ($existing && time() <= ($existing['expired_at'] ?? 0)) {
                return false;
            }
        }

        $payload = [
            'status'     => 'PROCESSING',
            'response'   => '',
            'http_code'  => 0,
            'expired_at' => time() + $ttl
        ];

        // return cacheContent('set', $cacheKey, 'idempotent', json_encode($payload), $ttl) !== false;
        return cacheContent('set', $cacheKey, json_encode($payload), $ttl) !== false;
    }

    /**
     * Simpan hasil respons akhir ke Cache.
     */
    public static function saveResponse(string $key, string $responseBody, int $httpCode = 200, int $ttl = 300): void
    {
        $payload = [
            'status'     => 'COMPLETED',
            'response'   => (string) $responseBody,
            'http_code'  => $httpCode,
            'expired_at' => time() + $ttl
        ];

        // cacheContent('set', $key, 'idempotent', json_encode($payload), $ttl);
        cacheContent('set', $key, json_encode($payload), $ttl);
    }

    /**
     * Hapus lock jika terjadi Exception / Error pada alur aplikasi.
     */
    public static function unlock(string $key): void
    {
        // delCache($key, 'idempotent');
        delCache($key);
    }

    /**
     * Debug status penyimpanan Idempotency berbasis Driver Cache Native.
     */
    public static function debugGetAll(): array
    {
        return [
            'status'  => 'info',
            'driver'  => 'Native Cache (cacheContent)',
            'message' => 'Idempotency menggunakan driver cache native framework. Pengelolaan TTL dan pengosongan memori ditangani secara otomatis oleh backend cache (Redis/File/Memcached).'
        ];
    }
}