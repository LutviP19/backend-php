<?php

namespace App\Core\Security\Middleware;

use App\Models\User;
use App\Core\Support\Config;
use App\Core\Security\Hash;
use Exception;
use RuntimeException;

class ValidateClient
{
    protected $clientId;
    protected $columnId;
    protected $hash;
    protected $redis;

    public function __construct($clientId, $columnId = 'ulid')
    {
        $this->clientId = $clientId;
        $this->columnId = $columnId;
        $this->hash = new Hash();

        $this->redis = new \Predis\Client([
                            'host' => Config::get('redis.cache.host'),
                            'port' => Config::get('redis.cache.port'),
                            'database' => Config::get('redis.cache.database')
                        ]);
    }

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
            $this->redis->mset(['client_token:'.$this->clientId => base64_encode($user->client_token)]);

            return $user->client_token;
        }

        return null;
    }

    public function generateToken()
    {
        $token = $this->getToken();

        if (! is_null($token)) {
            return $this->hash->create($token);
        }

        return null;
    }

    public function updateToken()
    {
        $token = User::updateClientToken($this->columnId, $this->clientId);

        if (! is_null($token)) { // cache to redis
            $this->redis->mset(['client_token:'.$this->clientId => base64_encode($token)]);
        }

        return $token;
    }

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

    public function delToken(): void
    {
        // get cache from redis
        $token = $this->redis->mget(['client_token:'.$this->clientId]);

        if (! is_null($token) && isset($token[0])) {
            // delete cache from redis
            $this->redis->del(['client_token:'.$this->clientId]);
        }
    }

    private function __checkColumnId($column)
    {
        $this->clientId = $column === 'id' && gettype($this->clientId) === 'string' ? null : $this->clientId;
    }
}
