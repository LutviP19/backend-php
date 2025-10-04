<?php

namespace App\Core\Security\Middleware;


use App\Models\User;
use App\Core\Support\Config;
use App\Core\Security\Hash;
use Exception;
use RuntimeException;

/**
 * ValidateClient class
 * @author Lutvi <lutvip19@gmail.com>
 */
class ValidateClient
{
    protected $clientId;
    protected $columnId;
    protected $minutes_to_expire;
    protected $hash;
    protected $redis;

    public function __construct($clientId, $columnId = 'ulid')
    {
        $this->clientId = $clientId;
        $this->columnId = $columnId;
        $this->minutes_to_expire = (env('SESSION_LIFETIME', 120) * 60);
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
        // get cache from redis
        $token = $this->redis->mget(['client_token:'.$this->clientId]);

        if (! is_null($token) && isset($token[0])) {
            return base64_decode($token[0]);
        }

        $this->__checkColumnId($this->columnId);
        $user = User::select(['client_token'])
            ->where($this->columnId, '=', $this->clientId)
            ->whereAnd('status', '=', 1)
            ->first();


        if ($user &&
           isset($user->client_token) &&
           $user->client_token != '') {

            // cache to redis
            $key = 'client_token:'.$this->clientId;
            $this->redis->mset([$key => base64_encode($user->client_token)]);
            $this->redis->expire($key, $this->minutes_to_expire);

            return $user->client_token;
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
        $token = $this->getToken($this->columnId);

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
        $token = $this->redis->mget(['client_token:'.$this->clientId]);

        if (! is_null($token) && isset($token[0])) {
            // delete cache from redis
            $this->redis->del(['client_token:'.$this->clientId]);
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
