<?php

declare(strict_types=1);

namespace App\Controllers\ServerApi\Auth;

use App\Models\User;
use App\Controllers\ServerApi\ServerApiController;
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
     * loginAction function
     *
     * @return response
     */
    public function loginAction($request, array $data)
    {

        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Set default output
        $status = $callback = false;
        $output = $headers = [];
        $message = $tokenJwt =  '';

        try {

            // Validate Input
            $validator = new Validator();
            $validator->validate($jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');


            if ($errors) {
                $statusCode = 422;
                $callback = false;
                $message = 'Validation errors.';
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                // Default status
                $statusCode = 203;
                $errors = ['auth' => 'Invalid credentials.'];
                $message = 'Credentials errors.';

                $email = readJson('email', $payload, $payload['email']);
                $password = readJson('password', $payload, $payload['password']);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // // Middleware
            // $this->setRatelimiter('login_request', 1200, 10);

            if ($callback && isset($user->ulid)) {

                // Set Session and generate new JwtToken
                // dd($user, true);
                $tokenJwt = $this->setLoginSession($user);
                if ($this->activeClientMiddleware && false === $tokenJwt) {

                    $statusCode = 401;
                    return $this->SetOpenSwooleResponse($status, $statusCode, ['auth' => 'Client not found!'], 'Invalid Client!');
                }

                // Set cookie
                $sessionName = $this->sessionName;
                $sessionId = Session::get('sessionKeyApi');
                $sessionExp = config('session.exptime');

                // Initialize array $headers $this->activeClientMiddleware && 
                if($sessionId !== "") {
                    $cookies = [];
                    
                    $cookies[] = "{$this->sessionName}={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax; HttpOnly";
                    $prefixKey = encryptData(sessionKeyFormat($user->ulid, $this->sessionId));
                    $cookies[]  = "sessionKeyApi={$prefixKey}; Max-Age={$sessionExp}; Path=/; SameSite=Lax; HttpOnly";

                    // 3. Masukkan ke $headers (Siap dikirim ke SetOpenSwooleResponse)
                    $headers = [
                        'Set-Cookie' => $cookies
                    ];
                    \App\Core\Support\Log::debug($headers, 'AuthController.login.$headers');
                }
                
                $status = true;
                $statusCode = 201;
                $output = [                            
                            'jwt_token' => $tokenJwt,
                            'account' => Session::all(),
                            // 'client_token' => Session::get('client_token'),
                            // 'api_token' => encryptData(config('app.token_api')),
                            'sessid' => decryptData(Session::get('sessionKeyApi')),
                            // 'all' => $_SESSION,
                        ];
                        
                return $this->SetOpenSwooleResponse($status, $statusCode, $output, '', $headers);                
            } else {

                return $this->SetOpenSwooleResponse($status, $statusCode, $errors, $message);
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse($status, $statusCode, ['exception', $exception->getMessage()], 'Exception');
        }
    }

}
