<?php 
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Session;
use App\Core\Security\CSRF;

/**
 * ApiController class
 * @author Lutvi <lutvip19@gmail.com>
 */
class ApiController extends BaseController
{
    protected $authToken;
    protected $filter;
    protected $requestServer;
    protected $sessionId;
    protected $sessionName;
    protected $headers;
    protected $jsonData;
    protected bool $rateLimit;
    protected $isDev;

    public function __construct()
    {
        global $requestServer, $sessionId, $sessionName;

        parent::__construct();

        $this->isDev ??= false;
        $this->rateLimit = false;
        $this->filter = new \App\Core\Validation\Filter();
        $this->requestServer = $requestServer;
        $this->sessionId = $_COOKIE[$sessionName] ?? $sessionId ?? \session_id();
        $this->sessionName = $sessionName ?? \session_name();

        $rawBody = '';
        $this->headers = [];
        $this->jsonData = [];

        if (\isSwoole() || $this->requestServer instanceof \OpenSwoole\Http\Request) { // on OpenSwoole Server
        
            // Format Headers
            if (!empty($this->requestServer->header) && is_array($this->requestServer->header)) {
                foreach ($this->requestServer->header as $key => $value) {
                    $this->headers[ucwords((string) $key, "-")] = $value;
                }
            }

            // Ambil raw body dari Swoole Request
            $rawBody = $this->requestServer->rawContent() ?: '';
            
            if (!empty($rawBody) && checkValidJSON($rawBody)) {
                $this->jsonData = \json_decode($rawBody, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
            }
        } else {

            $this->headers = getallheaders();

            $rawBody =  \request()->getBody();
            $this->jsonData = \request()->all();
        }

        // Accepted type is JSON
        $validate = $this->onlyAcceptedJSON();
        if ($validate) {
            return $validate;
        }

        // Validate JSON
        $validate = $this->checkValidJSON($rawBody);
        if ($validate) {
            return $validate;
        }


        // Clean Errors MessageBag
        Session::unset('errors');


        // Middlewares
        (new \App\Core\Security\Middleware\EnsureIpIsValid())->handle();
        (new \App\Core\Security\Middleware\EnsureHeaderIsValid())->handle($this->headers);

        // Validate token and CSRF
        $this->validateApiToken();

        // Validate with session data
        if (Session::has('uid') && Session::has('secret') && Session::has('authId')) {
            // New-BaseAuth
            $this->authToken = $this->initAuthToken();
        }
    }

    public function onlyAcceptedJSON()
    {
        if ($this->headers['Accept'] !== 'application/json' || 
        $this->headers['Content-Type'] !== 'application/json' ||
        $_SERVER['HTTP_ACCEPT'] !== 'application/json') {

            return endResponse(
                $this->getOutput(false, 406, [
                    'Invalid Content Type!',
                ], 'Only accepted JSON.'),
                406
            );
        }

        return;
    }

    public function checkValidJSON($rawBody) 
    {
        if ($rawBody === '') {

            return endResponse(
                $this->getOutput(false, 406, [
                    'Invalid data!'
                ], 'Invalid Data.'),
                406
            );
        }
        $validBody = json_decode(trim((string) $rawBody), true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
        
            return endResponse(
                $this->getOutput(false, 406, [
                    'Invalid Json format!'
                ], 'Invalid JSON.'),
                406
            );
        }

        return '';
    }

    /**
     * setLoginSession function, Set session for login user
     *
     * @param  [object]  $user
     * @param  [bool]  $isRefresh
     *
     * @return mixed
     */
    protected function setLoginSession($user, $isRefresh = false)
    {
        // Save to the DB if there is no ulid
        // $db->query("UPDATE {$this->table} SET ulid) = ? WHERE id = ?", [$newUlid, $user_id]);

        $sessionData = [];
        foreach ($user as $key => $value) {
            [$newKey, $newValue] = match ($key) {
                'id', 'user_id' => ['user_id', is_string($value) ? $value : encryptData($value)],
                'ulid'          => ['uid', $value],
                default         => [$key, $value],
            };

            $sessionData[$newKey] = $newValue;
        }

        $gnr = generateRandomString(32, true);
        $userId = $user->ulid ?? $sessionData['uid'] ?? \get_device_fingerprint();
        $sessionData['gnr'] = $gnr;

        // delete cache by uid
        delCache("refresh_token:{$userId}");
        delCache("client_token:{$userId}");
        delCache("user_abilities:{$userId}");

        $validateClient = new \App\Core\Security\Middleware\ValidateClient($userId);
        $clientToken = $validateClient->getToken();
        $clientTokenGen = $validateClient->generateToken();

        $sessionData['client_token'] = $clientTokenGen;

        // Delete session cache in Redis
        if (isSwoole()) {
            $sessionKey = sessionKeyFormat($userId);
            delCache($sessionKey);
        }

        if (false === $validateClient->matchToken($clientTokenGen)) {
            if (!isSwoole()) {
                Session::destroy();
            }

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

        // Persistence based on Flag isSwoole()
        if (isSwoole()) {
            $this->sessionId = session_create_id('bp-');
            $prefixKey = sessionKeyFormat($userId, $this->sessionId);
            $sessionData['sessionKey'] = $prefixKey;
            
            delCache($prefixKey);
            cacheContent('set', $prefixKey, $sessionData, config('session.exptime'));

            // Stored in the Swoole Request Context so that it can be accessed in the Controller
            if (class_exists('\OpenSwoole\Coroutine')) {
                \OpenSwoole\Coroutine::getContext()['session'] = $sessionData;
            }
        }

        // Cache refresh Token
        $refreshTokenTimeout = days_to_seconds($authToken->getRefreshTokenTimeout());
        $prefixKeyRefreshToken = "refresh_token:{$userId}";
        cacheContent('set', $prefixKeyRefreshToken, $sessionData['tokenAuthRefresh'], $refreshTokenTimeout);

        foreach ($sessionData as $sKey => $sVal) {
            if (Session::has($sKey)) {
                continue;
            }

            Session::set($sKey, $sVal);
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
            $hash = new \App\Core\Security\Hash();

            if ($hash->matchPassword($password, $user->password)) {
                return true;
            }
        }

        return false;
    }

    protected function useMiddleware($guest = false)
    {
        if($guest) {
            // Validate Api token
            $this->validateApiToken();
        } else {
            // Validate header X-Client-Token
            $validate = $this->validateClientToken();
            if ($validate) {
                return $validate;
            }

            // Validate Auth
            $validateAuth = $this->validateAuthToken();
            if ($validateAuth) {
                return $validateAuth;
            }

            // Validate using session data
            if (Session::has('uid') && Session::has('secret') && Session::has('authId')) {
                // ValidateSession
                $validate = (new \App\Core\Security\Middleware\ValidateSession())->handle();
                if ($validate) {
                    return $validate;
                }
            }
        }
    }

    /**
     * validateApiToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    protected function validateApiToken($csrf = false)
    {
        if (isset($this->headers['X-Api-Token']) === false ||
            matchEncryptedData($this->getPass(), $this->headers['X-Api-Token']) === false) {

            // Middleware - Rate limiter
            $identifier = 'API_KEY-'.\clientIP();
            $perSeconds = 6000;
            $this->setRatelimiter($identifier, $perSeconds, 3);

            return endResponse(
                $this->getOutput(false, 403, [
                    'token' => 'Invalid api token!',
                ], 'Invalid api token!'),
                403
            );
        }

        // CSRF_TOKEN
        if ($csrf) {

            // Check isDev
            if($this->isDev === true) {
                $request_token = CSRF::generate();
            } else {
                // Assuming token is sent in X-CSRF-Token header
                $request_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            }
            
            if (CSRF::match($request_token) === false) {

                $request_token = filter_input(INPUT_COOKIE, 'XSRF-TOKEN', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

                if (CSRF::match($request_token) === false) {
                    // Middleware - Rate limiter
                    $identifier = 'CSRF_TOKEN-'.\clientIP();
                    $perSeconds = 600;
                    $this->setRatelimiter($identifier, $perSeconds, 10);

                    $errors = [
                        'csrfToken' => ['Token expired, please reload page.'],
                    ];
                    return endResponse(
                        $this->getOutput(false, 403, [
                           $errors
                        ]),
                        403
                    );
                }
            }
        }
    }

    /**
     * validateClientToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return void
     */
    protected function validateClientToken()
    {
        $clientId = Session::get('uid'); // Get from session
        $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);

        if (empty($clientId)) {
            return endResponse(
                $this->getOutput(false, 401, [
                    'auth' => 'Session expired!'
                ], 'Please login!'), 
                401
            );
        }

        if(! isset($this->headers['X-Client-Token'])) {
            return endResponse(
                $this->getOutput(false, 403, [
                        'client_token' => 'Missing client token!',
                    ], 'Missing client token header!'),
                403
            );
        }
        
        if(! $validateClient->matchToken($this->headers['X-Client-Token'])) {

            return endResponse(
                $this->getOutput(false, 403, [
                        'client_token' => 'Invalid client token!',
                    ], 'Invalid client token!'),
                403
            );
        }
    }

    // New-BaseAuth
    protected function validateAuthToken(?string $permission = null)
    {
        $user = Session::all();
        $tokenAuth = Session::get('tokenAuth');
        $bearerToken = $this->getBearerToken();

        if (empty($user) ||
            is_null($bearerToken) ||
            is_null($this->authToken) ||
            $bearerToken !== $tokenAuth ||
            false === $this->authToken->validateToken($bearerToken, $permission)) {

            return endResponse(
                $this->getOutput(false, 401, [
                    'token' => 'Invalid WEB token!',
                ], 'Please login!'),
                401
            );
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

    protected function returnSystemBusy($errCode = 422, $info = 'System busy, please try again in few moments.')
    {
        $errors = [
            'busy' => [$info],
        ];
        return endResponse(
            $this->getOutput(false, $errCode, [
               $errors
            ]),
            $errCode
        );
    }

    protected function formatCamelCaseKey(array $jsonData = [], $replaced = ['' => '']): array
    {
        $formated = $output = [];
        foreach($jsonData as $key => $value) {
            $key = str_replace_multi($replaced, $key);
            $formatKey = camelCaseToUnderscore($key);

            $formated[$formatKey] = $value;
        }

        return $formated;
    }

}
