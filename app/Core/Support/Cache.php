<?php

namespace App\Core\Support;

/**
 * Cache class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Cache
{
    protected $driver;
    protected $redisClient;
    protected $path_cache = __DIR__ . '/../../../storage/framework/cache/';
    protected $prefix;
    private $storagePath;
    private $defaultExpiry = 3600;

    public function __construct($driver = null, $db = null, $prefix = null)
    {
        $this->redisClient = null;
        $this->driver = $driver ?? Config::get("app.cache_driver", "files");
        $this->prefix = $prefix ?: "bp_cache";

        // Lazy Initialization of Redis (Only if set as CACHE_DRIVER)
        if ($this->driver === "redis") {            
            $this->redisClient = setupRedisConnection();
        } else {
            // Define cache folder (Adapt to your folder structure)
            $this->storagePath = storage_path("/framework/cache/");
        }
    }

    /**
     * saveData cacke function
     *
     * @param  [type] $id
     * @param  [type] $data
     *
     * @return void
     */
    public function saveData($id, $data, $minutes_to_expire = 120)
    {
        $data = base64_encode(serialize($data));

        if ($this->driver == 'database' || $this->driver == 'redis') {
            $key = $this->prefix.':'.$this->_formatId($id);

            $this->redisClient->mset([$key => $data]);
            $this->redisClient->expire($key, ($minutes_to_expire * 60));
        }

        if ($this->driver == 'file') {
            \file_put_contents($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache', $data);
        }
    }

    /**
     * getData cache function
     *
     * @param  string $id
     *
     * @return void
     */
    public function getData($id)
    {
        $data = '';
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $path = $this->prefix.':'.$this->_formatId($id);

            // \App\Core\Support\Log::debug($path, 'Cache.getData.$path');

            $data = $this->redisClient->mget([$path]);
            // \App\Core\Support\Log::debug($data, 'Cache.getData.$data');

            if (is_null($data) || ! isset($data[0])) {
                $data = $this->redisClient->get($path);
            }

            if(! is_null($data) && count($data))
                $data = $data[0];
        }

        if ($this->driver == 'file') {
            $path = $this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache';
            if (! \file_exists($path)) {
                saveData($id, $data);
            }

            $data = \file_get_contents($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
        }

        return unserialize(base64_decode((string) $data));
    }

    public function deleteData($id)
    {
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $prefix = $this->prefix.':'.$this->_formatId($id);

            $keysToDelete = $this->redisClient->keys($prefix);
            if (!empty($keysToDelete))
                $this->redisClient->del($keysToDelete);
        }
        if ($this->driver == 'file') {
            \unlink($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
        }
    }

    public function clearData($all = false)
    {
        if ($this->driver == 'database' || $this->driver == 'redis' || $all) {
            clearRedisDataByPrefix($this->prefix);
        }

        if ($this->driver == 'file' || $all) {
            clearCacheFileByPrefix($this->path_cache, $this->prefix.'*');
        }
    }

    private function _formatId($id)
    {
        return \str_replace([' ', '.', '/', '-'], '_', $id);
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
            // //  Only cache if total data not 0
            // if (is_array($data)) {
            //     $total = $data['total'] ?? $data['data']['total'] ?? null;
            //     if ($total === 0) {
            //         return;
            //     }
            // }

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
                } catch (\Exception) {
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
        // Delete in Redis
        if ($this->redisClient) {
            try {
                $this->redisClient->del($key);
            } catch (\Exception) {
            }
        }

        // Delete in Files
        $file = $this->storagePath . md5((string) $key) . ".cache";
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
