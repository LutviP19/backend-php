<?php

/**
 * Idempotency class
 * @author Lutvi <lutvip19@gmail.com>
 * @package Backend PHP
 */

namespace App\Core\Security;

use Exception;

class Idempotency
{
    private ?\Predis\Client $redis;
    private string $keyPrefix = 'idempotency:';

    public function __construct()
    {
        if (function_exists('setupRedisConnection')) {
            $this->redis = setupRedisConnection();
        } else {
            throw new Exception("Redis connection helper 'setupRedisConnection' is missing.");
        }
    }

    /**
     * Memeriksa dan mengunci request berdasarkan Idempotency Key.
     *
     * @param string $key    Key unik dari client (misal: UUID atau Token Transaksi)
     * @param int    $ttl    Batas waktu kunci dalam hitungan detik (default 5 detik)
     * @return bool          Mengembalikan TRUE jika aman dijalankan, FALSE jika terdeteksi duplikat
     */
    public function lock(string $key, int $ttl = 5): bool
    {
        if (empty($key) || !$this->redis) {
            return true; // Jika key kosong atau Redis mati, bypass aman agar aplikasi tidak mogok
        }

        $redisKey = $this->keyPrefix . $key;

        try {
            /**
             * Menggunakan perintah NX (Not Exists) dan EX (Expire) secara atomik.
             * Perintah ini HANYA akan berhasil (return true/1) jika key tersebut BELUM ADA di Redis.
             * Jika key SUDAH ADA, ia akan mengembalikan false/0 (artinya request duplikat).
             */
            $isLocked = $this->redis->set($redisKey, 'processing', 'NX', 'EX', $ttl);

            return (bool) $isLocked;
        } catch (\Throwable $e) {
            // Log error jika Redis bermasalah, namun biarkan request tetap lolos (fail-safe)
            if (class_exists('\App\Core\Support\Log')) {
                \App\Core\Support\Log::error("Idempotency lock error: " . $e->getMessage());
            }
            return true;
        }
    }

    /**
     * Menyimpan hasil response sukses ke Redis (Opsional, jika ingin mengembalikan response yang sama).
     *
     * @param string $key
     * @param mixed  $responseBody Data response (array/string) yang ingin di-cache
     * @param int    $ttl          Lama simpan dalam detik
     */
    public function saveResponse(string $key, $responseBody, int $ttl = 60): void
    {
        if (empty($key) || !$this->redis) {
            return;
        }

        $redisKey = $this->keyPrefix . 'res:' . $key;
        $data = is_array($responseBody) || is_object($responseBody) ? json_encode($responseBody) : $responseBody;

        try {
            $this->redis->setex($redisKey, $ttl, $data);
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }

    /**
     * Mengambil response yang sudah pernah sukses diproses sebelumnya.
     *
     * @param string $key
     * @return mixed
     */
    public function getSavedResponse(string $key)
    {
        if (empty($key) || !$this->redis) {
            return null;
        }

        $redisKey = $this->keyPrefix . 'res:' . $key;

        try {
            $data = $this->redis->get($redisKey);
            if ($data) {
                $decoded = json_decode($data, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $data;
            }
        } catch (\Throwable $e) {
            // Fail-safe
        }

        return null;
    }

    /**
     * Menghapus kunci idempotency (digunakan jika proses di tengah jalan ternyata gagal/error,
     * sehingga client diizinkan untuk langsung mencoba klik submit lagi).
     *
     * @param string $key
     */
    public function unlock(string $key): void
    {
        if (empty($key) || !$this->redis) {
            return;
        }

        try {
            $this->redis->del([$this->keyPrefix . $key]);
        } catch (\Throwable $e) {
            // Fail-safe
        }
    }
}
