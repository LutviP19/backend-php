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
    protected $jwtToken;
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

        // \App\Core\Support\Log::debug($_SERVER, 'ServerApiController.__construct.$_SERVER');
        // \App\Core\Support\Log::debug($this->headers, 'ServerApiController.__construct.$this->headers');

        // Clean Errors MessageBag
        Session::unset('errors');

        // Auto detect from config
        $this->activeClientMiddleware = (!in_array(\clientIP(), config('local_ips')));

        // Start JWT - only for public
        if($this->activeClientMiddleware) {
            $this->jwtToken = $this->initJwtToken();
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
     * @return mixed
     */
    protected function setLoginSession($user)
    {
        $tokenJwt = '';
        $sessionData = [];
        foreach ($user as $key => $value) {
            if ($key === 'ulid') {
                $key = 'uid';
            }
            $sessionData[$key] = $value;
        }

        $gnr = generateRandomString(32, true);
        $userId = $user->ulid ?? $sessionData['uid'] ?? \get_device_fingerprint() ?? null;
        $sessionData['gnr'] = $gnr;
        
        $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
        $clientToken = $validateClient->getToken();
        $clientTokenGen = $validateClient->generateToken();

        $sessionData['client_token'] = $clientTokenGen;

        // Clear Redis Session Cache
        $sessionKeyApi = sessionKeyFormat($userId);
        (new \App\Core\Support\CacheSwoole())->flush($sessionKeyApi);        

        if (false === $validateClient->matchToken($clientTokenGen)) {
            // if (!isSwoole()) {
            //     Session::destroy();
            // }

            return false;
        }

        // JWT & Secret initialization
        if ($this->activeClientMiddleware) {
            Session::set('secret', encryptData($clientToken, $gnr));
            $sessionData['secret'] = Session::get('secret');
            $sessionData['jwtId'] = generateUlid();

            // initJwtToken & create tokenJwt
            $jwtToken = $this->initJwtToken();
            $info = 'Api jwt-' . $userId;
            $subject = 'Access API for web user:' . $userId;
            $tokenJwt = $jwtToken->createToken($userId, $info, $subject);
            $sessionData['tokenJwt'] = $tokenJwt;
        }

        // Persistence Session di Swoole
        $this->sessionId = session_create_id('bpapi-');
        $prefixKey = sessionKeyFormat($userId, $this->sessionId);
        $sessionData['sessionKeyApi'] = $prefixKey;
        
        \App\Core\Support\Log::debug($sessionData, 'Controller.Debug.sessionData');

         (new \App\Core\Support\CacheSwoole())->set($prefixKey, $sessionData, config('session.exptime'));

        // Saved to the Swoole Request Context so it can be accessed in the Controller
        if (class_exists('\OpenSwoole\Coroutine')) {
            \OpenSwoole\Coroutine::getContext()['session'] = $sessionData;
        }

        // // Example Take a session from an active Coroutine Context
        // $session = Coroutine::getContext()['session'] ?? null;        
        
        foreach ($sessionData as $sKey => $sVal) {
            Session::set($sKey, $sVal);
        }

        return $tokenJwt;
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
            if($validate) return $validate;

            // Validate Jwt        
            $validate = $this->validateJwt();
            if ($validate) {
                return $validate;
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

    public function validateJwt()
    {
        try {
            $user = Session::all();
            $tokenJwt = Session::get('tokenJwt');
            $bearerToken = str_replace('Bearer ', '', $this->headers['Authorization'][0] ?? '');

            if (empty($user) ||
                is_null($this->jwtToken) ||
                $bearerToken !== $tokenJwt ||
                false === $this->jwtToken->validateToken($bearerToken)) {

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

            json_response([], 429, "Too many requests", $errors);
        }
    }

}
