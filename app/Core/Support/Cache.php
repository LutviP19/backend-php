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
    protected $prefix = "bp_cache";

    public function __construct($driver = null) {
        $this->driver = $driver ?: env('CACHE_STORE', 'file');
        $this->$redis = new \Predis\Client([
            'host' => Config::get('redis.cache.host'),
            'port' => Config::get('redis.cache.port'),
            'database' => Config::get('redis.cache.database')
        ]);
    }

    /**
     * saveData cacke function
     *
     * @param  [type] $id
     * @param  [type] $data
     *
     * @return void
     */
    public function saveData($id, $data) {
        $data = serialize($data);
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $this->redis->mset([$this->prefix.':'.$this->_formatId($id).':' => $data]);
        }
        if ($this->driver == 'file') {
           \file_put_contents($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache', $data);
        }
    }

    /**
     * getData cache function
     *
     * @param  [type] $id
     *
     * @return void
     */
    public function getData($id) {
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $data = $this->redis->get($this->prefix.':'.$this->_formatId($id));
        }
        if ($this->driver == 'file') {
            $data = \file_get_contents($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
        }

        return unserialize($data);
    }

    public function deleteData($id) {
        if ($this->driver == 'database' || $this->driver == 'redis') {
            $this->redis->del($this->prefix.':'.$this->_formatId($id));
        }
        if ($this->driver == 'file') {
            \unlink($this->path_cache.$this->prefix.'_'.$this->_formatId($id).'.cache');
        }
    }

    public function clearData() {
        if ($this->driver == 'database' || $this->driver == 'redis') {
            clearRedisDataByPrefix($this->prefix);
        }
        if ($this->driver == 'file') {
            clearCacheFileByPrefix($this->path_cache, $this->prefix.'*');
        }
    }

    private function _formatId($id) {
        return \str_replace([' ', '.', '/', '-'], '_', $id);
    }
            
}