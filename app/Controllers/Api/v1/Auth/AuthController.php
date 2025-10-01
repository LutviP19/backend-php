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
        global $requestServer;

        if ( \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // on OpenSwoole Server

            $headers = $requestServer->header;
            $jsonData = \is_string($requestServer->rawContent()) ? \json_decode($requestServer->rawContent(), true) : [];
        }
        

        
        // \App\Core\Support\Log::debug($jsonData, 'WebAuth.login.GET.$jsonData');

        // \App\Core\Support\Log::debug($_SERVER, 'login.$_SERVER');
        // \App\Core\Support\Log::debug($request::isJsonRequest(), 'login.isJsonRequest');
        // \App\Core\Support\Log::debug($request, 'WebAuth.login.$request');
        // \App\Core\Support\Log::debug($request->all(), 'WebAuth.login.GET.$$request->all()');
        // \App\Core\Support\Log::debug(gettype($requestServer->rawContent()), 'WebAuth.login.GET.$requestServer.gettype');
        // \App\Core\Support\Log::debug($requestServer->rawContent(), 'WebAuth.login.GET.$requestServer');

        // \App\Core\Support\Log::debug($requestServer->header, 'WebAuth.login.GET.$requestServer->header');

        // return stopHere(
        //     $response->json(
        //        $this->getOutput(true, 200, [
        //             'status' => 'ok'
        //        ]),
        //         200
        //     )
        // );

        try {
            $jsonData = $jsonData ?: $request->all();
            $validator = new Validator();
            $validator->validate($jsonData, [
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
                $jsonData = $this->filter->filter($jsonData, [
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

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('login_request'))
            //     ->setupForm(clientIP(), $callback, 5, 10, 1200);

            if (false == $callback || empty($user)) {
                return stopHere(
                    $response->json(
                        $this->getOutput(false, $status, [
                            $errors
                       ]),
                        $status
                    )
                );
            }
        } catch (Exception $exception) {
            return stopHere(
                $response->json(
                    $this->getOutput(false, 429, [
                      $exception->getMessage(),
                   ]),
                    429
                )
            );
        }

        // Generate credentials
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
            return stopHere(
                $response->json(
                    $this->getOutput(false, 401, [
                      'auth' => 'Client not found!',
                   ], 'Invalid Client!'),
                    401
                )
            );
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

        return $response->json($this->getOutput(true, 201, [
                    'token' => $tokenJwt,
                    'sessid' => session_id(),
                    'account' => Session::all()
                ]), 201);
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
        global $requestServer;

        $headers = $request->headers() ?: $requestServer->header;

        if ( ! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // on OpenSwoole Server
    
            // Validate header X-Client-Token
            $this->validateClientToken($headers, $response);

            // Validate JWT
            $this->validateJwt($response);
        } else {

            $jsonData = \is_string($requestServer->rawContent()) ? \json_decode($requestServer->rawContent(), true) : [];
        }
        

        try {

            $jsonData = $jsonData ?: $request->all();
            $validator = new Validator();
            $validator->validate($jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');


            if ($errors) {

                $status = 203;
                $callback = false;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $status = 401;
                $errors = ['auth' => 'Invalid credentials'];

                $payload = $request->all();
                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
            //     ->setupForm(clientIP(), $callback, 5, 10, 1200);

            if (false == $callback || empty($user)) {
                return stopHere(
                    $response->json(
                        $this->getOutput(false, $status, [
                            $errors
                       ]),
                        $status
                    )
                );
            }
        } catch (Exception $exception) {
            return stopHere(
                $response->json(
                    $this->getOutput(false, 429, [
                      $exception->getMessage(),
                   ]),
                    429
                )
            );
        }

        // Update Client Token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);
        $validateClient->updateToken();

        Session::destroy();

        return $response->json($this->getOutput(true, 201, [
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
        // Validate header X-Client-Token
        $this->validateClientToken($request, $response);

        // Validate JWT
        $this->validateJwt($request, $response);

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
