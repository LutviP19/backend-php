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
        // Validate header X-Client-Token
        $validate = $this->useMiddleware();
        if($validate) return $validate;
        
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
     * logout function
     *
     * @param  Request  $request
     * @param  Response $response
     *
     * @return $response->json
     */
    public function logout($request, array $data)
    {
        // // Validate header X-Client-Token
        // $this->validateClientToken();

        // // Validate JWT
        // $this->validateJwt($request, $response);

        // $requestData = [
        //     'attributes' => $data['attributes'],
        //     'jsonData' => $data['jsonData'],
        //     'requestQuery' => $data['requestQuery']
        // ];
        $jsonData = $data['jsonData'];

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

    /**
     * updateToken function
     *
     * @param  Request  $request
     * @param  Response $data
     *
     * @return $response->json
     */
    public function updateToken($request, array $data)
    {
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

                $statusCode = 401;
                $errors = ['auth' => 'Invalid credentials,',];

                $payload = $request->all();
                $email = readJson('email', $payload);
                $password = readJson('password', $payload);

                $user = User::getUserByEmail($email);
                $callback = $this->checkCredentials($user, $password);
            }

            // // Middleware
            // (new \App\Core\Security\Middleware\RateLimiter('uptoken_request'))
            //     ->setupForm(Session::get('uid'), $callback, 5, 10, 1200);

            if (false == $callback || empty($user)) {
                return $this->SetOpenSwooleResponse(false, $statusCode, $errors, 'Validation errors.');
            }
        } catch (Exception $exception) {
            $statusCode = 429;
            return $this->SetOpenSwooleResponse(false, $statusCode, $exception->getMessage(), 'Validation errors.');
        }

        // Update Client Token
        $userId = Session::get('uid');
        $validateClient = new ValidateClient($userId);

        if (false === $validateClient->updateToken()) {
            $statusCode = 203;
            $output = [
                        'auth' => 'Failed update your token, please try again, in few moments!',
                    ];
            return $this->SetOpenSwooleResponse(false, $statusCode, $output, 'Failed update');
        }

        // Auto logout
        Session::destroy();

        $statusCode = 201;
        $output = [
                    'auth' => 'Token successfully updated, please re-login to use new token!',
                ];

        return $this->SetOpenSwooleResponse(true, $statusCode, $output);
    }
}
