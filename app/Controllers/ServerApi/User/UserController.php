<?php

declare(strict_types=1);

namespace App\Controllers\ServerApi\User;

use App\Models\User;
use App\Controllers\ServerApi\ServerApiController;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Validation\Validator;
use App\Core\Support\Session;
use Exception;

class UserController extends ServerApiController
{
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * indexAction function, dispaly information about logged user
     *
     * @param  [type] $request
     * @param  array  $data
     *
     * @return void
     */
    public function indexAction($request, array $data)
    {
        // Validate header X-Client-Token + JWT
        $validateOutput = $this->useMiddleware();
        if ($validateOutput) {
            return $validateOutput;
        }
        
        // Set default output
        $headers = [];
        $status = false;
        $output = null;        
        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Validate Input
        $validator = new Validator();
        $validator->validate($jsonData, [
            'email' => 'required|email'
        ]);
        $errors = \App\Core\Support\Session::get('errors');

        if ($errors) {
            $statusCode = 422;
            $message = 'Validation errors.';
        } else {

            // Filter Input
            $jsonData = $this->filter->filter($jsonData, [
                'email' => 'trim|sanitize_string',
            ]);

            // Sanitize Input
            $payload = $this->filter->sanitize($jsonData, ['email']);
            $email = readJson('email', $payload, $payload['email']);
            
            $statusCode = 203;
            $errors = ['auth' => 'Invalid credentials.'];
            $message = 'Credentials errors.';
            $account = User::getUserByEmail($email);
            $sessionEmail = $this->activeClientMiddleware ? Session::get('email') : (Session::has('email') ? Session::get('email') : $account->email);
            $callback = ($email === $account->email && $account->email === $sessionEmail);
            // dd($sessionEmail);
            // dd($callback);

            // $this->activeClientMiddleware
            if (!$callback) {
                $status = true;
                $statusCode = 203;
                $message = 'Credentials errors.';
                $errors = ['auth' => 'Invalid credentials'];
            } else {
                $status = true;
                $statusCode = 200;
                $message = '';
                $output = [
                            'account' => $this->activeClientMiddleware && Session::has('email') ? Session::all() : array_except($account, ['ulid', 'password', 'client_token']),
                            // 'callback' => $callback,
                            // 'session' => Session::all(),
                        ];
            }
        }

        return $this->SetOpenSwooleResponse($status, $statusCode, $output ?: $errors, $message, $headers);
    }

    /**
     * logoutAction function
     *
     * @return $response->json
     */
    public function logoutAction($request, array $data)
    {
        // Validate header X-Client-Token + JWT
        $validateOutput = $this->useMiddleware();
        if ($validateOutput) {
            return $validateOutput;
        }

        // $attributes = $data['attributes']; // Parse URI-Path
        // $requestQuery = $data['requestQuery']; // Parse Query-String
        $jsonData = $data['jsonData'];

        try {

            // Validate Input
            $validator = new Validator();
            $validator->validate($jsonData, [
                'email' => 'required|email'
            ]);
            $errors = \App\Core\Support\Session::get('errors');

            $callback = false;
            if ($errors) {

                $statusCode = 422;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($jsonData, [
                    'email' => 'trim|sanitize_string'
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email']);

                $statusCode = 203;
                $errors = ['auth' => 'Invalid credentials'];

                $email = readJson('email', $payload, $payload['email']);

                // Match email with auth session
                if (!empty(Session::get('email')) && !empty($email)) {
                    $callback = (bool)(Session::get('email') === $email);
                }
            }

            // // Middleware
            // $this->setRatelimiter('uptoken_request', 1200, 5);


            if ($callback) {

                // clear cache token
                $userId = Session::get('uid');
                $validateClient = new ValidateClient($userId);
                $validateClient->delToken();

                Session::destroy();

                $statusCode = 200;
                $output = [
                            'auth' => 'You are logged out!',
                        ];

                return $this->SetOpenSwooleResponse(true, $statusCode, $output);
            } else {

                return $this->SetOpenSwooleResponse(false, $statusCode, [$errors], 'Validation errors.');
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, ['exception', $exception->getMessage()], 'Validation errors.');
        }
    }

    /**
     * updateTokenAction function, update client_token
     *
     * @return $response->json
     */
    public function updateTokenAction($request, array $data)
    {
        // Validate header X-Client-Token + JWT
        $validateOutput = $this->useMiddleware();
        if ($validateOutput) {
            return $validateOutput;
        }

        // $requestData = [
        //     'attributes' => $data['attributes'],
        //     'jsonData' => $data['jsonData'],
        //     'requestQuery' => $data['requestQuery']
        // ];
        $jsonData = $data['jsonData'];

        try {

            // Validate Input
            $validator = new Validator();
            $validator->validate($jsonData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');

            $callback = false;
            if ($errors) {

                $statusCode = 422;
            } else {

                // Filter Input
                $jsonData = $this->filter->filter($jsonData, [
                    'email' => 'trim|sanitize_string',
                    'password'  => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email', 'password']);

                $statusCode = 203;
                $errors = ['auth' => 'Missing credentials'];

                $email = readJson('email', $payload, $payload['email']);
                $password = readJson('password', $payload, $payload['password']);

                // Match email with auth session
                $validEmail = false;
                if (!empty($email)) {
                    $validEmail = (bool)(Session::get('email') === $email);
                }

                if ($validEmail) {
                    $user = User::getUserByEmail($email);
                    $callback = $this->checkCredentials($user, $password);
                }
            }

            // // Middleware
            // $this->setRatelimiter('uptoken_request', 1200, 5);


            if ($validEmail && $callback && !is_null($user)) {
                // Update Client Token
                $userId = Session::get('uid');
                $validateClient = new ValidateClient($userId);

                if (false === $validateClient->updateToken()) {
                    $statusCode = 203;
                    $output = ['auth' => 'Failed update your token, please try again in few moments!'];

                    return $this->SetOpenSwooleResponse(false, $statusCode, $output, 'Failed update');
                }

                // Auto logout
                $validateClient->delToken();
                Session::destroy();

                $statusCode = 201;
                $output = [ 'auth' => 'Token successfully updated, please re-login to use new token!' ];

                return $this->SetOpenSwooleResponse(true, $statusCode, $output);
            } else {

                $statusCode = 203;
                $errors = ['auth' => 'Invalid credentials'];

                return $this->SetOpenSwooleResponse(false, $statusCode, [$errors], 'Validation errors.');
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, ['exception', $exception->getMessage()]);
        }
    }
}
