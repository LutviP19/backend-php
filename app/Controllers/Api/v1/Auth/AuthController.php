<?php

namespace App\Controllers\Api\v1\Auth;

use OpenSwoole\Http\Request as OpenSwooleRequest;
use App\Core\Support\Session;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Security\Middleware\JwtToken;
use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Hash;
use App\Core\Validation\Validator;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use Exception;

/**
 * AuthController class
 * @author Lutvi <lutvip19@gmail.com>
 */
class AuthController extends ApiController
{
    protected $id;
    protected $ulid;

    public function __construct()
    {
        parent::__construct();

        $this->rateLimit = false;
    }

    /**
     * login function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return response
     */
    public function login(Request $request, Response $response)
    {
        try {
            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');
            // \App\Core\Support\Log::debug($errors, 'WebAuth.login.$errors');

            if ($errors) {

                $status = 203;
                $callback = false;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $status = 401;
                $errors = ['auth' => 'Invalid credentials'];

                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                // \App\Core\Support\Log::debug($user, 'WebAuth.login.$user');
                $callback = $this->checkCredentials($user, $password);
                // \App\Core\Support\Log::debug($callback, 'WebAuth.login.$callback');
            }

            // Middleware
            if($this->rateLimit) {
                (new \App\Core\Security\Middleware\RateLimiter('login_request'))
                ->setupForm(clientIP(), $callback, 5, 10, 1200);
            }
            

            if (false == $callback || empty($user)) {
                return endResponse(
                    $this->getOutput(false, $status, [
                        $errors
                   ]), $status);
            }
        } catch (Exception $exception) {
            return endResponse(
                $this->getOutput(false, 429, [
                  $exception->getMessage(),
               ]), 429);
        }

        // // Generate credentials
        // \session_create_id($user->ulid.'-');
        // $this->sessionId = session_id();

        foreach ($user as $key => $value) {
            if ($key === 'ulid') {
                $key = 'uid';
            }

            Session::set($key, $value);
        }

        Session::set('gnr', generateRandomString(32, true));
        $userId =  Session::get('uid');
        $gnr =  Session::get('gnr');

        // Set login session
        $validateClient = new ValidateClient($userId);
        $clientToken = $validateClient->getToken();
        $clientTokenGen = $validateClient->generateToken();
        Session::set('client_token', $clientTokenGen);

        if (false === $validateClient->matchToken($clientTokenGen)) {
            return endResponse(
                    $this->getOutput(false, 401, [
                      'auth' => 'Client not found!',
                   
                    ], 'Invalid Client!'), 401);
        }

        // initJwtToken
        Session::set('secret', encryptData($clientToken, $gnr));
        Session::set('jwtId', generateUlid());
        $this->jwtToken = $this->initJwtToken();

        // Create specific data for jwt
        $info = 'Api jwt-'.$userId;
        $subject = 'Access API for user:'.$userId;
        $tokenJwt =  $this->jwtToken->createToken($userId, $info, $subject);
        Session::set('tokenJwt', $tokenJwt);

        $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
        // $headers = ['Set-Cookie' => "{$sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax;"];
        $headers = ['Set-Cookie' => "{$this->sessionName}={$this->sessionId}; Max-Age={$sessionExp}; Path=/;"];

        // \App\Core\Support\Log::debug($headers, 'AuthController.login.$headers');

        // Cache session data by uid
        if (\in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) {
            cacheContent('set', $_SESSION['uid'] .'-'. $this->sessionId, 'bp_session', $_SESSION);
        }
        
        return endResponse(
            $this->getOutput(true, 201, [
                'token' => $tokenJwt,
                'sessid' => $this->sessionId,
                'account' => Session::all()
            ]), 201, $headers);
    }

    /**
     * updateToken function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return $response->json
     */
    public function updateToken(Request $request, Response $response)
    {
        $this->useMiddleware();
        
        try {
            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');


            if ($errors) {

                $status = 203;
                $callback = false;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $status = 401;
                $errors = ['auth' => 'Invalid credentials'];

                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // Middleware
            if ($this->rateLimit) {
                (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
                    ->setupForm(clientIP(), $callback, 5, 10, 1200);
            }

            if (false == $callback || empty($user)) {
                return endResponse(
                    $this->getOutput(false, $status, [
                        $errors
                   ]), $status);
            }
        } catch (Exception $exception) {
            return endResponse(
                $this->getOutput(false, 429, [
                  $exception->getMessage(),
               ]), 429);
        }

        // Update Client Token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);
        $validateClient->updateToken();

        Session::destroy();

        return endResponse(
                $this->getOutput(true, 201, [
                'auth' => 'Token successfully updated, please re-login to use new token!',
            ]), 201);
    }

    /**
     * logout function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return $response->json
     */
    public function logout(Request $request, Response $response)
    {
        $this->useMiddleware();

        // clear cache token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);
        $validateClient->delToken();

        Session::destroy();

        return $response->json($this->getOutput(true, 200, [
            'auth' => 'You are logged out!',
        ]), 200);
    }

    /**
     * checkCredentials function
     *
     * @param  [string]  $user
     * @param  [string]  $password
     *
     * @return boolean
     */
    private function checkCredentials($user, $password): bool
    {
        if ($user) {
            $hash = new Hash();

            if ($hash->matchPassword($password, $user->password)) {
                return true;
            }
        }

        return false;
    }
}
