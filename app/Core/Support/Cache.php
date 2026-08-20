<?php

/**
 * Cache class
 * Supported drivers: files(default) and redis
 * @author LutviP19 <lutvip19@gmail.com>
 * @package PHP-Microdata
 */

namespace App\Core\Support;

use Exception;
use Predis\Client as PredisClient;

class Cache
{
    // Object class
    private $redisClient;
    private $storagePath;
    private $defaultExpiry = 3600;

    public function __construct()
    {
        $this->redisClient = null;

        // Lazy Initialization of Redis (Only if set as CACHE_DRIVER)
        if (Config::get("app.cache_driver") === "redis") {
            try {
                $this->redisClient = new PredisClient([
                    "host" => Config::get("redis.cache.host"),
                    "port" => Config::get("redis.cache.port"),
                    "database" => Config::get("redis.cache.database"),
                ]);
            } catch (Exception) {
                $this->redisClient = null;
            }
        }

        // Define cache folder (Adapt to your folder structure)
        $this->storagePath = storage_path("/framework/cache/");
    }

    /**
     * Remember Pattern: Fetch cache or save if it doesn't exist
     */
    public function remember($key, $callback, $expiry = null)
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

    /**
     * Retrieve Data
     */
    public function get($key)
    {
        if ($this->redisClient) {
            try {
                $data = $this->redisClient->get($key);
                if ($data) {
                    return unserialize($data);
                }
            } catch (Exception) {
                $this->redisClient = null; // Fallback ke file
            }
        }

        // Strategy 2: Fallback Files
        $file = $this->storagePath . md5((string) $key) . ".cache";
        if (file_exists($file)) {
            $content = unserialize(file_get_contents($file));
            if (time() < $content["expiry"]) {
                return unserialize($content["data"]);
            }
            unlink($file); // Delete if expired
        }

        return null;
    }

    /**
     * Save Data
     */
    public function set($key, $data, $expiry = 3600)
    {
        //  Only cache if data is not empty
        if (!empty($data)) {

            //  Only cache if total data not 0
            if (is_array($data) && count($data) <= 0) {
                return;
            }

            $serialized = serialize($data);

            // Save to Redis
            if ($this->redisClient) {
                try {
                    $this->redisClient->setex($key, $expiry, $serialized);
                    return;
                } catch (Exception) {
                    $this->redisClient = null;
                }
            }

            // Save to File (Fallback)
            if (!is_dir($this->storagePath)) {
                mkdir($this->storagePath, 0775, true);
            }
            $content = serialize([
                "expiry" => time() + $expiry,
                "data" => $serialized,
            ]);
            file_put_contents($this->storagePath . md5((string) $key) . ".cache", $content);
        }
    }

    /**
     * Hapus Cache (Flush)
     */
    public function flush($key)
    {
        // 1. Handling Redis Cache
        if ($this->redisClient) {
            try {
                if (str_contains($key, '*')) {
                    $keys = $this->redisClient->keys($key);
                    if (!empty($keys)) {
                        $this->redisClient->del($keys);
                    }
                } else {
                    $this->redisClient->del($key);
                }
            } catch (\Throwable $e) {
                // Log errors if needed
            }
        }

        // 2. Handling File Cache
        if (str_contains($key, '*')) {
            // If there are wildcards, iterate over the entire .cache file and match the hashes
            // Note: If the cache file is saved in md5(key) form, the wildcard is at the beginning of the original string
            // cannot be searched directly via glob(md5(key)).
            // Best solution for files: save key or use pattern matching in manifest/folder.
            
            $files = glob($this->storagePath . "*.cache");
            if ($files) {
                $prefix = str_replace('*', '', $key);
                foreach ($files as $file) {
                    // Retrieve the contents of the original key if stored in the file header /delete according to the criteria
                    unlink($file); 
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
