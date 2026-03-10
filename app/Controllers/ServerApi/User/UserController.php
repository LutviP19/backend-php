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

        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Set default output
        $status = true;
        $statusCode = 200;
        $output = [];
        $message = '';
        $headers = [];

        // $statusCode = 200;
        $output = [
                    'account' => Session::all()
                ];

        return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
    }

    /**
     * logoutAction function
     *
     * @param  Request  $request
     * @param  Response $response
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
                if (!empty(Session::get('email')) && !empty($email )) {
                    $callback = (bool)(Session::get('email') === $email);
                }
            }

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
            //     ->setupForm(Session::get('uid'), $callback, 5, 10, 1200);

            if (false == $callback) {

                return $this->SetOpenSwooleResponse(false, $statusCode, [$errors], 'Validation errors.');
            } else {

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
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, ['exception', $exception->getMessage()], 'Validation errors.');
        }
    }

    /**
     * updateTokenAction function, update client_token
     *
     * @param  Request  $request
     * @param  Response $data
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
                    $statusCode = 203;
                    $errors = ['auth' => 'Invalid credentials'];

                    $user = User::getUserByEmail($email);
                    $callback = $this->checkCredentials($user, $password);
                }
            }

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
            //     ->setupForm(Session::get('uid'), $callback, 5, 10, 1200);

            if (false === $validEmail || false == $callback || empty($user)) {

                return $this->SetOpenSwooleResponse(false, $statusCode, [$errors], 'Validation errors.');
            } else {

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
            }
        } catch (Exception $exception) {

            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, ['exception', $exception->getMessage()]);
        }
    }
}
