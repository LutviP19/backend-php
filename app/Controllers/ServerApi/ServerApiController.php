<?php

declare(strict_types=1);

namespace App\Controllers\ServerApi;

use App\Core\Http\BaseController;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;
use App\Core\Support\Session;
use App\Core\Security\Hash;
use Exception;

class ServerApiController extends BaseController
{
    protected $filter;
    protected $headers;
    protected $authToken;
    protected $sessionId;
    protected $sessionName;
    protected $activeClientMiddleware = true;

    public function __construct()
    {
        parent::__construct();
        $this->filter = new \App\Core\Validation\Filter();
        $this->headers = getallheaders();
        $this->sessionName = \session_name() ?? '';
        $this->sessionId = $_COOKIE[$this->sessionName] ?? null;

        // Clean Errors MessageBag
        Session::unset('errors');

        // Auto detect from config
        $this->activeClientMiddleware = (!in_array(\clientIP(), config('local_ips')));

        // Start Auth - only for public
        if($this->activeClientMiddleware) {
            // New-BaseAuth
            $this->authToken = $this->initAuthToken();
        }
    }

    protected function SetOpenSwooleResponse(bool $status, int $statusCode, array $output, string $message = '', array $headers = []): OpenSwooleResponse
    {
        $json = $this->getOutput($status, $statusCode, $output, $message);

        $response = (new OpenSwooleResponse(\json_encode($json)))
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($statusCode);

        foreach ($headers as $key => $value) {
            // If format indexed array ["Header-Name: Value"]
            if (is_int($key) && is_string($value) && str_contains($value, ':')) {
                [$headerName, $headerValue] = explode(':', $value, 2);
                $response = $response->withAddedHeader(trim($headerName), trim($headerValue));
                continue;
            }

            $headerName = (string) $key;

            // If value is an array (Multi-Cookie)
            if (is_array($value)) {
                foreach ($value as $cookieValue) {
                    $response = $response->withAddedHeader($headerName, $cookieValue);
                }
            } else {
                $response = $response->withHeader($headerName, $value);
            }
        }

        return $response;
    }

    /**
     * setLoginSession function, Set session for login user
     *
     * @param  object  $user
     * @param  bool  $isRefresh
     * @return mixed
     */
    protected function setLoginSession($user, $isRefresh = false)
    {
        // Save to the DB if there is no ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

        $sessionData = [];
        foreach ($user as $key => $value) {
            [$newKey, $newValue] = match ($key) {
                'id', 'user_id' => ['user_id', encryptData($value)],
                'ulid'          => ['uid', $value],
                default         => [$key, $value],
            };

            $sessionData[$newKey] = $newValue;
        }

        $gnr = generateRandomString(32, true);
        $userId = $user->ulid ?? $sessionData['uid'] ?? \get_device_fingerprint();
        $sessionData['gnr'] = $gnr;

        // delete cache by uid        
        delCache("client_token:{$userId}");
        delCache("user_abilities:{$userId}");

        $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
        $clientToken = $validateClient->getToken();
        $clientTokenGen = $validateClient->generateToken();

        $sessionData['client_token'] = $clientTokenGen;

        // Delete session cache in Redis
        $sessionKeyApi = sessionKeyFormat($userId, 'bpapi-*');
        delCache($sessionKeyApi);

        if (false === $validateClient->matchToken($clientTokenGen)) {

            return false;
        }

        // Set Basic Sessions
        foreach ($sessionData as $sKey => $sVal) {
            Session::set($sKey, $sVal);
        }

        // Secret initialization
        Session::set('secret', encryptData($clientToken, $gnr));
        $sessionData['secret'] = Session::get('secret');
        $sessionData['authId'] = generateUlid();

        // initAuthToken
        $tokenAuth = "";
        if ($this->activeClientMiddleware) {

            $authToken = $this->initAuthToken($sessionData);
            if ($isRefresh) {
                $getRefreshToken = getBearerToken();
                $tokenAuth = $authToken->refreshToken($getRefreshToken, [
                    'uid' => $userId,
                    // "user_permissions" => cacheContent('get', "user_abilities:{$userId}")
                ]) ?? null;
            } else {
                $tokenAuth = $authToken->generateToken([
                    'uid' => $userId,
                    // "user_permissions" => cacheContent('get', "user_abilities:{$userId}")
                ]) ?? null;
            }

            $sessionData['tokenAuth'] = $tokenAuth['access_token'] ?? '';
            $sessionData['tokenAuthRefresh'] = $tokenAuth['refresh_token'] ?? '';

            // Persistence Session in Swoole
            $this->sessionId = session_create_id('bpapi-');
            $prefixKey = sessionKeyFormat($userId, $this->sessionId);
            $sessionData['sessionKeyApi'] = $prefixKey;

            delCache($prefixKey);
            cacheContent('set', $prefixKey, $sessionData, config('session.exptime'));

            // Cache refresh Token            
            $refreshTokenTimeout = days_to_seconds($authToken->getRefreshTokenTimeout());
            $prefixKeyRefreshToken = "refresh_token:{$userId}";
            delCache("refresh_token:{$userId}");
            cacheContent('set', $prefixKeyRefreshToken, $sessionData['tokenAuthRefresh'], $refreshTokenTimeout);

            // Stored in the Swoole Request Context so that it can be accessed in the Controller
            if (class_exists('\OpenSwoole\Coroutine')) {
                \OpenSwoole\Coroutine::getContext()['session'] = $sessionData;
            }

            // // Ambil session dari Coroutine Context aktif
            // $session = Coroutine::getContext()['session'] ?? null;

            foreach ($sessionData as $sKey => $sVal) {
                if (Session::has($sKey)) {
                    continue;
                }

                Session::set($sKey, $sVal);
            }
        }

        return $tokenAuth;
    }

    /**
     * checkCredentials function
     *
     * @param  [string]  $user
     * @param  [string]  $password
     *
     * @return boolean
     */
    protected function checkCredentials($user, $password): bool
    {
        if ($user) {
            $hash = new Hash();

            if ($hash->matchPassword($password, $user->password)) {
                return true;
            }
        }

        return false;
    }

    public function useMiddleware()
    {
        if($this->activeClientMiddleware) {
            // Validate header X-Client-Token
            $validate = $this->validateClientToken();
            if ($validate) {
                return $validate;
            }

            // Validate Auth
            $validateAuth = $this->validateAuth();
            if ($validateAuth) {
                return $validateAuth;
            }
        }
    }

    /**
     * validateClientToken function
     *
     * @return \OpenSwooleResponseon $status === false, or void
     */
    public function validateClientToken()
    {
        // Set default output
        $status = true;
        $statusCode = 200;
        $output = [];
        $message = '';
        $headers = [];

        if (Session::has('uid')) {

            $clientHeaderToken = $this->headers['X-Client-Token'][0] ?? '';

            $clientId = Session::get('uid'); // Get from session
            $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
            $validate = $validateClient->matchToken($clientHeaderToken);

            if (! $validate || empty($validate)) {
                $status = false;
                $statusCode = 401;
                $message = 'Invalid client token!';
                $output = [ 'auth' => 'Invalid token!' ];
            }
        } else {

            $status = false;
            $statusCode = 401;
            $message = 'Please login!';
            $output = [ 'auth' => 'Session expired!' ];
        }

        if (false === $status) {
            return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
        }

        return false;
    }

    // New-BaseAuth
    public function validateAuth()
    {
        try {
            $user = Session::all();
            $tokenAuth = Session::get('tokenAuth');
            $bearerToken = $this->getBearerToken();

            if (empty($user) ||
                is_null($this->authToken) ||
                is_null($bearerTokenn) ||
                $bearerToken !== $tokenAuth ||
                false === $this->authToken->validateToken($bearerToken)) {

                $statusCode = 401;
                $message = 'Please login!';
                $output = [ 'jwt' => 'Invalid jwt!' ];

                return $this->SetOpenSwooleResponse(false, $statusCode, $output, $message);
            }

            return false;
        } catch (Exception $e) {
            $statusCode = 401;
            $message = 'Please login!';
            $output = [ 'jwt' => $e->getMessage() ];

            return $this->SetOpenSwooleResponse(false, $statusCode, $output, $message);
        }

    }

    protected function setRatelimiter(?string $identifier, int $perSeconds = 120, int $limit = 10)
    {
        $identifier = str_replace(" ", "_", $identifier);
        $identifier = $identifier . "-" . \clientIP();
        if (false === checkRateLimit($identifier, $limit, $perSeconds)) {
            $after = round($perSeconds / 60);
            $afteText = $after > 1 ? "{$after} minutes" : "{$after} minute";
            $errors = [
                "busy" => ["Please try again after {$afteText}."],
            ];

            return $this->SetOpenSwooleResponse(false, 429, $errors, "Too many requests");
        }
    }

}
