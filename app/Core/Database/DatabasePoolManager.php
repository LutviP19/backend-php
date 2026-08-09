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
     * Helper eksekusi dengan Auto-Ping & Auto-Return ke Pool
     */
    public static function run(callable $callback)
    {
        $pool = self::getPool();
        
        /** @var \PDO $pdo */
        $pdo = $pool->get();

        try {
            // Health check / Ping koneksi
            try {
                $pdo->query('SELECT 1');
            } catch (\Throwable $e) {
                // Jika koneksi mati/gone away, buat instance PDO baru via Factory
                $pdo = PDOFactory::make();
            }

            // Jalankan query bisnis
            return $callback($pdo);

        } finally {
            // Selalu kembalikan koneksi ke pool
            $pool->put($pdo);
        }
    }

    private static function isConnectionLost(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '2006') || 
               str_contains($message, '2013') || 
               str_contains($message, 'MySQL server has gone away') ||
               str_contains($message, 'Error while sending') ||
               str_contains($message, 'HY000');
    }
}