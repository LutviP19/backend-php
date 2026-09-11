<?php

declare(strict_types=1);

namespace App\Core\Support;

use Predis\Client as PredisClient;
use OpenSwoole\Coroutine\Channel;

/**
 * CacheSwoole class
 * Supported drivers: files(default) and redis via Predis Connection Pool
 * @author LutviP19 <lutvip19@gmail.com>
 * @package Backend-PHP
 */
class CacheSwoole
{
    private static ?Channel $pool = null;
    private string $storagePath;
    private int $defaultExpiry;

    public function __construct(int $defaultExpiry = 3600)
    {
        $this->storagePath = storage_path("/framework/cache/");
        $this->defaultExpiry = $defaultExpiry;
    }

    /**
     * Inisialisasi Predis Connection Pool untuk OpenSwoole
     */
    public static function initPool(): void
    {
        if (Config::get("app.cache_driver") === "redis" && self::$pool === null) {
            $poolSize = (int) Config::get("redis.cache.pool_size", 10);
            self::$pool = new Channel($poolSize);

            $config = [
                "scheme"   => "tcp",
                "host"     => Config::get("redis.cache.host", "127.0.0.1"),
                "port"     => (int) Config::get("redis.cache.port", 6379),
                "database" => (int) Config::get("redis.cache.database", 0),
            ];

            if ($password = Config::get("redis.cache.password")) {
                $config["password"] = $password;
            }

            // Populate channel dengan instance Predis Client
            for ($i = 0; $i < $poolSize; $i++) {
                try {
                    $client = new PredisClient($config);
                    self::$pool->push($client);
                } catch (\Throwable $e) {
                    // Log error jika gagal inisialisasi client
                }
            }
        }
    }

    /**
     * Helper untuk mengambil & mengembalikan koneksi Predis dari Channel Pool
     */
    private function executeRedis(callable $callback)
    {
        if (!self::$pool || self::$pool->isEmpty()) {
            return null;
        }

        // Ambil 1 client Predis dari pool (Timeout 2.0 detik)
        /** @var PredisClient|false $redis */
        $redis = self::$pool->pop(2.0);

        if (!$redis) {
            return null;
        }

        try {
            return $callback($redis);
        } catch (\Throwable $e) {
            return null;
        } finally {
            self::$pool->push($redis);
        }
    }

    public function remember(string $key, callable $callback, ?int $expiry = null)
    {
        $expiry = $expiry ?: $this->defaultExpiry;
        $data = $this->get($key);

        if ($data !== null) {
            return $data;
        }

        $data = $callback();
        $this->set($key, $data, $expiry);

        return $data;
    }

    public function get(string $key)
    {
        // 1. Cek Redis via Pool
        $data = $this->executeRedis(function (PredisClient $redis) use ($key) {
            $res = $redis->get($key);
            return $res !== null ? $res : false;
        });

        if ($data !== null && $data !== false) {
            return @unserialize($data);
        }

        // 2. Fallback File Cache
        $file = $this->storagePath . md5($key) . ".cache";
        if (file_exists($file)) {
            $content = @unserialize(file_get_contents($file));
            if (is_array($content) && time() < $content["expiry"]) {
                return @unserialize($content["data"]);
            }
            @unlink($file);
        }

        return null;
    }

    public function set(string $key, mixed $data, int $expiry = 3600): void
    {
        if (empty($data) || (is_array($data) && count($data) <= 0)) {
            return;
        }

        $serialized = serialize($data);

        // 1. Simpan ke Redis
        $saved = $this->executeRedis(function (PredisClient $redis) use ($key, $expiry, $serialized) {
            $res = $redis->setex($key, $expiry, $serialized);
            return $res ? true : null;
        });

        if ($saved) {
            return;
        }

        // 2. Fallback Simpan ke File
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0775, true);
        }

        $content = serialize([
            "expiry" => time() + $expiry,
            "data"   => $serialized,
        ]);

        @file_put_contents($this->storagePath . md5($key) . ".cache", $content);
    }

    public function flush(string $key): void
    {
        // 1. Flush Redis
        $this->executeRedis(function (PredisClient $redis) use ($key) {
            if (str_contains($key, '*')) {
                $keys = $redis->keys($key);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                $redis->del($key);
            }
        });

        // 2. Flush File
        if (str_contains($key, '*')) {
            $files = glob($this->storagePath . "*.cache");
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        } else {
            $file = $this->storagePath . md5($key) . ".cache";
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
