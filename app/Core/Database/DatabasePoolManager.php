<?php

namespace App\Core\Database;

use OpenSwoole\Core\Coroutine\Pool\ClientPool;
use PDO;
use Throwable;

class DatabasePoolManager
{
    private static ?ClientPool $pool = null;

    public static function init(int $capacity = 10): void
    {
        if (self::$pool !== null) {
            return;
        }

        // Pass class name string 'PDOFactory::class' (yang memiliki static method make())
        self::$pool = new ClientPool(PDOFactory::class, $capacity);
    }

    public static function getPool(): ClientPool
    {
        if (self::$pool === null) {
            throw new \RuntimeException("Database Connection Pool belum diinisialisasi!");
        }

        return self::$pool;
    }

    /**
     * Helper eksekusi dengan Auto-Ping, Auto-Retry, & Safe Pool Return
     */
    public static function run(callable $callback, int $maxRetries = 2)
    {
        $pool = self::getPool();
        
        /** @var PDO $pdo */
        $pdo = $pool->get();
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                // 1. Health check / Ping koneksi sebelum query dijalankan
                try {
                    $pdo->query('SELECT 1');
                } catch (Throwable $e) {
                    // Jika koneksi idle ternyata mati, ganti dengan instance PDO baru
                    $pdo = PDOFactory::make();
                }

                // 2. Eksekusi query bisnis
                $result = $callback($pdo);

                // 3. Kembalikan koneksi ke pool dan return hasil
                $pool->put($pdo);
                return $result;

            } catch (Throwable $e) {
                // Jika error adalah masalah koneksi terputus & jatah retry masih ada
                if (self::isConnectionLost($e) && $attempts <= $maxRetries) {
                    // Buat instance PDO baru untuk percobaan berikutnya
                    $pdo = PDOFactory::make();

                    if (function_exists('write_log')) {
                        write_log('error', "[POOL] Connection lost during query execution. Retrying ({$attempts}/{$maxRetries})... Error: " . $e->getMessage(), '/Core/Database/DatabasePoolManager', false);
                    }

                    // Jittered Exponential Backoff (Mencegah Thundering Herd)
                    // Attempt 1: ~100ms - 150ms
                    // Attempt 2: ~200ms - 250ms
                    $jitter = rand(10, 50) / 1000; // 0.01 - 0.05s
                    $sleepDuration = (0.1 * $attempts) + $jitter;

                    if (class_exists(\OpenSwoole\Coroutine::class)) {
                        \OpenSwoole\Coroutine::sleep($sleepDuration);
                    }

                    continue; // Ulangi loop retry
                }

                // Jika error BUKAN koneksi putus (seperti Syntax Error / Duplicate Entry)
                // ATAU jatah retry sudah habis, kembalikan PDO ke pool lalu lempar Exception
                $pool->put($pdo);
                throw $e;
            }
        }
    }

    /**
     * Mendeteksi apakah Throwable disebabkan oleh koneksi jaringan/database yang terputus
     */
    private static function isConnectionLost(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '2006') || 
               str_contains($message, '2013') || 
               str_contains($message, '2002') || 
               str_contains($message, 'MySQL server has gone away') ||
               str_contains($message, 'Error while sending') ||
               str_contains($message, 'Connection refused') ||
               str_contains($message, 'Server shutdown in progress') ||
               str_contains($message, 'HY000');
    }
}