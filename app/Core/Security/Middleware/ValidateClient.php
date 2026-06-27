<?php

namespace App\Core\Security\Middleware;


use App\Models\User;
use App\Core\Support\Config;
use App\Core\Security\Hash;

/**
 * ValidateClient class
 * @author Lutvi <lutvip19@gmail.com>
 */
class ValidateClient
{
    // protected $clientId;
    // protected $columnId;
    protected $minutes_to_expire;
    protected $hash;
    protected $redis;

    public function __construct(protected ?string $clientId, protected string $columnId = 'ulid')
    {
        $this->clientId = $clientId;
        $this->columnId = $columnId;
        // $this->minutes_to_expire = (env('SESSION_LIFETIME', 120) * 60);
        // 1. Simpan nilai ini dalam satuan DETIK murni (120 menit * 60 = 7200 detik)
        $this->minutes_to_expire = (int)env('SESSION_LIFETIME', 120) * 60;
        
        $this->hash = new Hash();

        $this->redis = new \Predis\Client([
                            'host' => Config::get('redis.cache.host'),
                            'port' => Config::get('redis.cache.port'),
                            'database' => Config::get('redis.cache.database')
                        ]);
    }

    /**
     * getToken function
     *
     * @return mixed
     */
    public function getToken()
    {
        $key = 'client_token:' . $this->clientId;
        $token = $this->redis->get($key);
        
        if ($token !== false && !is_null($token) && $token !== '') {
            return base64_decode($token);
        }

        // Populate token
        $this->__checkColumnId($this->columnId);
        $token = User::getClientId($this->clientId, $this->columnId);        

        if ($token != '') {
            $this->redis->setex($key, $this->minutes_to_expire, base64_encode($token));

            return $token;
        }

        return null;
    }

    /**
     * generateToken function
     *
     * @return mixed
     */
    public function generateToken()
    {
        $token = $this->getToken();

        if (! is_null($token)) {
            return $this->hash->create($token);
        }

        return null;
    }

    /**
     * updateToken function
     *
     * @return void
     */
    public function updateToken()
    {
        $token = User::updateClientToken($this->columnId, $this->clientId);

        if (! is_null($token)) { // delete cache from redis
            $key = 'client_token:'.$this->clientId;
            $this->redis->mset([$key => base64_encode($token)]);
            $this->redis->expire($key, $this->minutes_to_expire);
        } else {
            return false;
        }

        return $token;
    }

    /**
     * matchToken function
     *
     * @param  [string]  $clientToken
     *
     * @return boolean
     */
    public function matchToken($clientToken): bool
    {
        // $token = $this->getToken($this->columnId);
        $token = $this->getToken();

        if (is_null($token) ||
            false === $clientToken ||
            ! is_string($clientToken)) {

            return false;
        }

        return $this->hash->matchHash($token, $clientToken);
    }

    /**
     * delToken function
     *
     * @return void
     */
    public function delToken(): void
    {
        // get cache from redis
        // $token = $this->redis->mget(['client_token:'.$this->clientId]);

        $prefix = 'client_token:'.$this->clientId;
        $keysToDelete = $this->redis->keys($prefix);

        if (!empty($keysToDelete)) {
            // delete cache from redis
            $this->redis->del($keysToDelete);
        }
    }

    /**
     * __checkColumnId function
     *
     * @param  [string] $column
     *
     * @return void
     */
    private function __checkColumnId($column)
    {
        $this->clientId = $column === 'id' && gettype($this->clientId) === 'string' ? null : $this->clientId;
    }
}
