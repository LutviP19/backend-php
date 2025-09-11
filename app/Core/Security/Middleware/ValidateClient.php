<?php

namespace App\Core\Security\Middleware;

use App\Models\User;
use App\Core\Security\Hash;
use Exception;
use RuntimeException;

class ValidateClient
{
    protected $clientId;
    protected $columnId;
    protected $hash;

    public function __construct($clientId, $columnId = 'ulid')
    {
        $this->clientId = $clientId;
        $this->columnId = $columnId;
        $this->hash = new Hash();
    }

    public function getToken()
    {
        $this->__checkColumnId($this->columnId);
        
        $user = User::select(['client_token'])
            ->where($this->columnId, '=', $this->clientId)
            ->whereAnd('status', '=', 1)
            ->first();
        
        // dd($user);
        if($user && 
           isset($user->client_token) && 
           $user->client_token != '') {
            
            return $user->client_token;
        }

        return null;
    }

    public function generateToken()
    {
        $token = $this->getToken($this->columnId);

        if (!is_null($token))
            return $this->hash->create($token);

        return null;
    }

    public function matchToken($clientToken): bool
    {
        $token = $this->getToken($this->columnId);

        if (is_null($token) || 
            false === $clientToken || 
            !is_string($clientToken)) {
            
            return false;
        }

        return $this->hash->matchHash($token, $clientToken);
    }

    private function __checkColumnId($column) 
    {
        $this->clientId = $column === 'id' && gettype($this->clientId) === 'string' ? null : $this->clientId;
    }
}