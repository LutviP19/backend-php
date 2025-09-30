<?php

declare(strict_types=1);

namespace App\Controllers\ServerApi\Auth;

use App\Models\User;
use App\Controllers\ServerApi\ServerApiController;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Validation\Validator;
use App\Core\Support\Session;
use Exception;

/**
 * AuthController class
 * @author Lutvi <lutvip19@gmail.com>
 */
class AuthController extends ServerApiController
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
    public function login($request, array $data)
    {
        
        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Set output
        $status = false;
        $output = [];
        $message = '';

        try {

            // Validate Input
            $validator = new Validator();
            $validator->validate($jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');


            if ($errors) {
                $statusCode = 203;
                $callback = false;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                // Default status
                $statusCode = 401;
                $errors = ['auth' => 'Invalid credentials.'];

                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('login_request'))
            //     ->setupForm(clientIP(), $callback, 5, 10, 1200);

            if (false == $callback || empty($user)) {

                return $this->SetOpenSwooleResponse(false, $statusCode, $errors, 'Validation errors.');
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, $exception->getMessage(), 'Validation errors.');
        }

        // Set cookie
        $sessionName = session_name();
        $sessionId = session_create_id('bp-');
        $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
        $cookie = session_get_cookie_params();
        setcookie(
            $sessionName,
            $sessionId,
            $sessionExp,
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );

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

            $statusCode = 401;
            return $this->SetOpenSwooleResponse(false, $statusCode, ['auth' => 'Client not found!'], 'Invalid Client!');
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

        $statusCode = 201;
        $headers = ['Set-Cookie' => "{$sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax;"];
        $output = [
                    // 'api_token' => encryptData(config('app.token')),
                    'client_token' => Session::get('client_token'),
                    'jwt_token' => $tokenJwt,
                    // 'sessid' => $sessionId,
                    // 'account' => Session::all()
                ];

        return $this->SetOpenSwooleResponse(true, $statusCode, $output, $message, $headers);
    }

}
