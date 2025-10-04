<?php

namespace App\Core\Support;

/**
 * Cache class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Cache
{
    protected $driver;
    protected $redis;
    protected $path_cache = __DIR__ . '/../../../storage/framework/cache/';
    protected $prefix;

    public function __construct($driver = null, $db = null, $prefix = null)
    {
        $this->driver = $driver ?? env('CACHE_STORE', 'file');
        $this->redis = new \Predis\Client([
            'host' => Config::get('redis.cache.host'),
            'port' => Config::get('redis.cache.port'),
            'database' => $db ?? Config::get('redis.cache.database')
        ]);

        $this->prefix = $prefix ?: "bp_cache";
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

            $this->redis->mset([$key => $data]);
            $this->redis->expire($key, ($minutes_to_expire * 60));
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

            $data = $this->redis->mget([$path]);
            // \App\Core\Support\Log::debug($data, 'Cache.getData.$data');

            if (is_null($data) || ! isset($data[0])) {
                $data = $this->redis->get($path);
            }

            if(! is_null($data) && count($data))
                $data = $data[0];
        }

        if ($this->driver == 'file') {
            $path = realpath($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
            if (! \file_exists($path)) {
                saveData($id, $data);
            }

            $data = \file_get_contents($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
        }

        return unserialize(base64_decode($data));
    }

    public function deleteData($id)
    {
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $this->redis->del($this->prefix.':'.$this->_formatId($id));
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

}
