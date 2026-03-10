<?php

namespace App\Core\Security;

use App\Core\Database\QueryBuilder;
use App\Core\Security\Hash;

class RememberMe
{
    // protected $table_user;
    // protected $table_primary_id;
    protected $phone_number;
    protected $hash;
    protected $hashKey;
    protected $hashMode;

    // public function __construct($table_user, $table_primary_id, $phone_number)
    public function __construct(protected $table_user, protected $table_primary_id, $phone_number)
    {
        // // users, customers, drivers
        // $this->table_user = $table_user;

        // // primary id column
        // $this->table_primary_id = $table_primary_id;

        // unique phone_number
        $this->phone_number = \is_numeric($phone_number) ? $phone_number : base64url_decode($phone_number);

        $this->hash = new Hash();
        $this->hashKey = "okx#Xx2upsKd@yv!v%s28^43dt1G^&QF";
        $this->hashMode = "string";
    }

    public function generateTokens(): array
    {
        // $phone_number = $this->hash->create($this->phone_number, $this->hashKey, $this->hashMode);
        $phone_number = base64url_encode($this->phone_number);
        $validator = $this->hash->randomString(32, false);
        $hash_validator = password_hash((string) $validator, PASSWORD_DEFAULT);

        // \App\Core\Support\Log::debug($hash_validator, 'RememberMe.generateTokens.hash_validator');
        // \App\Core\Support\Log::debug(strlen($hash_validator), 'RememberMe.generateTokens.hash_validator-strlen');
        $token = $phone_number . ':' . $hash_validator;

        return [$phone_number, $validator, $hash_validator, $token];
    }

    public function parseToken(string $token): ?array
    {
        $parts = explode(':', $token);

        if ($parts && count($parts) == 2) {
            return [$parts[0], $parts[1]];
        }
        return null;
    }

    public function rememberToken(int $user_id, int $day = 30)
    {
        // remove all existing token associated with the user id
        $this->deleteUserToken($user_id);

        // generateTokens
        [$phone_number, $validator, $hash_validator, $token] = $this->generateTokens();

        // set expiration date
        $expired_seconds = time() + (60 * 60 * 24 * $day);

        // insert a token to the database 'Y-m-d H:i:s'
        $expiry = date('Y-m-d H:i:s', $expired_seconds);
        
        $matched = password_verify((string) $validator, (string) $hash_validator);
        // \App\Core\Support\Log::debug($matched, 'RememberMe.rememberToken.matchHash');
        if($matched) {
            $insertData = $this->insertUserToken($user_id, $phone_number, $validator, $expiry);
            // \App\Core\Support\Log::debug($insertData, 'RememberMe.rememberToken.$insertData');

            if ($insertData) {
                $token = $phone_number.':'.$hash_validator;
                // \App\Core\Support\Log::debug($token, 'RememberMe.rememberToken.$token-setcookie');
                // setcookie('remember_me', $token, $expired_seconds);

                $domain = env('APP_ENV') === 'local' ? 'localhost' : 'happyfew.org';
                // $path = $this->table_user === 'drivers' ? '/driver' : '/';
                $path = '/';
                return ['Set-Cookie' => "remember_me_{$this->table_user}={$token}; Max-Age={$expired_seconds}; Path={$path}; Domain={$domain}; HttpOnly; SameSite=Lax; Secure;"];
            }
        }
        
        return false;
    }

    public function insertUserToken(int $user_id, string $phone_number, string $validator, string $expiry): bool
    {
        $remember_token = $phone_number.':'.$validator;
        // \App\Core\Support\Log::debug(strlen($remember_token), 'RememberMe.insertUserToken.remember_token-strlen');
        return QueryBuilder::table($this->table_user)->execQuery('UPDATE '.$this->table_user.' SET remember_token = ?, remember_token_expiry = ? WHERE '.$this->table_primary_id.' = ?', [$remember_token, $expiry, $user_id]);
    }

    // Check token from cookies
    public function tokenIsValid(string $token): bool
    {
        // parse the token to get the selector and validator
        [$phone_number, $hash_validator] = $this->parseToken($token);

        // if (false === $this->hash->matchHash($this->phone_number, $phone_number, $this->hashKey, $this->hashMode)) {
        if (! \is_numeric($this->phone_number) || ! \is_numeric(base64url_decode($phone_number))) {
            return false;
        }

        $tokens = $this->findUserTokenByPhone();
        // \App\Core\Support\Log::debug($tokens, 'RememberMe.tokenIsValid.$tokens');
        if (! $tokens) {
            return false;
        }

        // Parse token from db remember_token
        $remember_token = $tokens->remember_token;
        $validator = \explode(':', (string) $remember_token)[1];

        $matched = password_verify($validator, (string) $hash_validator);
        // \App\Core\Support\Log::debug($matched, 'RememberMe.tokenIsValid.$matchHash');
        
        return $matched;
    }

    public function findUserTokenByPhone()
    {
        $getSelector = QueryBuilder::table($this->table_user)->select([$this->table_primary_id, 'remember_token', 'remember_token_expiry'])
            ->where('phone_number', '=', $this->phone_number)
            ->whereAnd('remember_token_expiry', '>=', date('Y-m-d H:i:s'))
            ->first();

        if ($getSelector) {
            return $getSelector;
        }

        return false;
    }

    public function deleteUserToken(int $user_id)
    {
        QueryBuilder::table($this->table_user)->execQuery('UPDATE '.$this->table_user.'  SET remember_token = ?, remember_token_expiry = ? WHERE '.$this->table_primary_id.' = ?', ['', null, $user_id]);
    }

    // Get token from cookies
    public function findUserByToken(string $token)
    {
        $tokens = $this->parseToken($token);

        if (!$tokens) {
            return null;
        }

        // Check is matchHash phone number
        $phone_number = base64url_decode($tokens[0]);
        // if (! $this->hash->matchHash($this->phone_number, $phone_number, $this->hashKey, $this->hashMode)) {
        if (! \is_numeric($phone_number)) {
            return null;
        }

        $getToken = QueryBuilder::table($this->table_user)->select()
            ->where('phone_number', '=', $this->phone_number)
            ->whereAnd('remember_token_expiry', '>=', date('Y-m-d H:i:s'))
            ->first();

        if ($getToken) {
            return $getToken;
        }

        return null;
    }


    public function login(string $username, string $password, bool $remember = false): bool
    {

        // $user = $this->findUserByUsername($username);

        // // if user found, check the password
        // if ($user && is_user_active($user) && password_verify($password, $user['password'])) {

        //     logUserIn($user);

        //     if ($remember) {
        //         rememberToken($user['id']);
        //     }

        //     return true;
        // }

        // return false;
    }

    public function isUserRememberLoggedIn(): bool
    {
        // check the remember_me in cookie
        $token = filter_input(INPUT_COOKIE, 'remember_me', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($token && $this->tokenIsValid($token)) {

            $user = $this->findUserByToken($token);

            if ($user) {
                return $this->logUserIn($user);
            }
        }

        return false;
    }

    /**
    * log a user in
    * @param array $user
    * @return bool
    */
    public function logUserIn(array $user): bool
    {
        $oldSessionId = session_id();
        bp_session_regenerate_id($oldSessionId);

        // prevent session fixation attack
        if ($_SESSION['phone'] === $user['phone_number']) {
            // set username & id in the session
            $_SESSION['username'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        if ($this->isUserRememberLoggedIn()) {

            // delete the user token
            $this->deleteUserToken($_SESSION['user_id']);

            // delete session
            unset($_SESSION['username'], $_SESSION['user_id`']);

            // remove the remember_me cookie
            if (isset($_COOKIE['remember_me'])) {
                unset($_COOKIE['remember_me']);
                // setcookie('remember_user', null, -1);
                setcookie('remember_user', '', ['expires' => -1]);
            }

            // remove all session data
            session_destroy();

            // redirect to the login page
            // redirect_to('login.php');
        } else {

        }
    }


}
