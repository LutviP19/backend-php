<?php

namespace App\Controllers\Api\v1\Auth;


use App\Core\Http\Client\NativeCurlStreamer;
use App\Core\Support\App;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Support\Session;
use App\Core\Validation\Validator;
use App\Models\User;
use Exception;

// use OpenSwoole\Http\Request as OpenSwooleRequest;
// use App\Core\Security\Middleware\JwtToken;
// use App\Core\Support\Config;
// use App\Core\Security\Hash;


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
            $headers = [];
            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');
            // \App\Core\Support\Log::debug($errors, 'WebAuth.login.$errors');

            if ($errors) {

                $statusCode = 422;
                $callback = false;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $statusCode = 203;
                $errors = ['auth' => 'Invalid credentials'];

                $email = readJson('email', $payload);
                $password = readJson('password', $payload);
                $callback = false;
                $user = [];

                // Call Api Server
                $streamer = new NativeCurlStreamer();
                $singleTask = App::externalApi('backend_login', [
                    'body' => ['email' => $email, 'password' => $password]
                ]);
                $single = $streamer->singleStream($singleTask);

                if ($single['error']) {
                    exit_response($single['body'], $single['statusCode']);
                } else {
                    $data = json_decode((string) $single['body'], true);
                    $user = readJson('data.account', $data);

                    if($email === $user['email']) {
                        $callback = true;
                    }
                }
            }

            // // Middleware
            // if ($this->rateLimit) {
            //     $this->setRatelimiter('login_request', 1200, 5);
            // }

            if ($callback && !empty($user)) {
                
                $tokenJwtValid = $this->setLoginSession($user);
                if (!$tokenJwtValid) {
                    return endResponse(
                        $this->getOutput(false, 401, [
                          'auth' => 'Client not found!',

                        ], 'Invalid Client!'),
                        401
                    );
                }
                // exit_response($_SESSION, 200);

                // Set cookie
                $sessionName = $this->sessionName;
                $sessionId = Session::get('sessionKey');
                $sessionExp = config('session.exptime');
                $headers[] = ['Set-Cookie' => "{$sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax;"];

                // Cache session data by uid
                if (\isSwoole()) {
                    $prefixKey = encryptData(sessionKeyFormat(($user['uid'] ?? $user['ulid']), $this->sessionId));
                    $headers[] = ['Set-Cookie' => "sessionKey={$prefixKey}; Max-Age={$sessionExp}; Path=/; SameSite=Lax;"];
                    // \App\Core\Support\Log::debug($headers, 'AuthController.login.$headers');
                }

                return endResponse(
                    $this->getOutput(true, 201, [                            
                            'jwt_token' => $tokenJwtValid,
                            'account' => Session::all(),
                            // 'client_token' => Session::get('client_token'),
                            // 'sessionKey' => $prefixKey,                            
                            // 'jwt_secret' =>  Session::get('secret')
                    ]),
                    201,
                    $headers
                );

            } else {
                
                return endResponse(
                    $this->getOutput(false, $statusCode, [
                        $errors
                   ]),
                    $statusCode
                );
            }
        } catch (Exception $exception) {

            return endResponse(
                $this->getOutput(false, 429, [
                  'exception', $exception->getMessage(),
               ]),
                429
            );
        }
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

            $callback = false;
            if ($errors) {

                $statusCode = 422;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $statusCode = 203;
                $errors = ['auth' => 'Missing credentials'];

                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                // Match email with auth session
                $validEmail = false;
                if (!empty($email)) {
                    $validEmail = (bool)(Session::get('email') === $email);
                }

                if ($validEmail) {
                    $statusCode = 203;
                    $errors = ['auth' => 'Invalid credentials'];

                    $user = User::getUserByEmail($email);
                    $callback = $this->checkCredentials($user, $password);
                }
            }

            // Middleware
            if ($this->rateLimit) {
                $this->setRatelimiter('uptoken_request', 1200, 5);
            }

            if (false == $callback || empty($user)) {

                return endResponse(
                    $this->getOutput(false, $statusCode, [
                        $errors
                   ], 'Validation errors.'),
                    $statusCode
                );
            } else {

                // Update Client Token
                $userId = Session::get('uid');
                $validateClient = new ValidateClient($userId);
                $validateClient->updateToken();

                Session::destroy();

                return endResponse(
                    $this->getOutput(true, 201, [
                    'auth' => 'Token successfully updated, please re-login to use new token!',
                ]),
                    201
                );
            }
        } catch (Exception $exception) {

            return endResponse(
                $this->getOutput(false, 429, [
                  'exception', $exception->getMessage(),
               ]),
                429
            );
        }
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

        try {

            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
            ]);
            $errors = \App\Core\Support\Session::get('errors');

            $callback = false;
            if ($errors) {

                $statusCode = 422;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email']);

                $statusCode = 203;
                $errors = ['auth' => 'Invalid credentials'];

                $email = readJson('email', $payload);

                $user = Session::get('email');
                $callback = (bool)($email === $user);
            }

            // Middleware
            if ($this->rateLimit) {
                $this->setRatelimiter('logout_request', 1200, 5);
            }

            if (false == $callback || empty($user)) {

                return endResponse(
                    $this->getOutput(false, $statusCode, [
                        $errors
                   ], 'Validation errors.'),
                    $statusCode
                );
            } else {

                // clear cache token
                $userId = Session::get('uid');
                $validateClient = new ValidateClient($userId);
                $validateClient->delToken();

                // Delete Cache session data by uid
                if(\isSwoole()) {

                    // Clean old keys
                    delCache($userId.'*');
                }

                Session::destroy();

                return endResponse(
                    $this->getOutput(true, 200, [
                    'auth' => 'You are logged out!',
                    ]),
                    200
                );
            }
        } catch (Exception $exception) {

            return endResponse(
                $this->getOutput(false, 429, [
                  'exception', $exception->getMessage(),
               ]),
                429
            );
        }
    }


}
