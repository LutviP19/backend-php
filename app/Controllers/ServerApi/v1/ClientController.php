<?php


namespace App\Controllers\ServerApi\v1;

use App\Controllers\ServerApi\ServerApiController;
use App\Core\Support\Session;

class ClientController extends ServerApiController
{
    public function __construct() {
        parent::__construct();

        
    }

    public function indexAction($request, array $data) {

        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Set default output
        $status = true; $statusCode = 200; $output = []; $message = ''; $headers = [];

        // $statusCode = 200;
        $output = [
                    'account' => Session::all()
                ];
        
        return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
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
            // \App\Core\Support\Session::unset('errors');
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
            //     ->setupForm(clientIP(), $callback, 5, 10, 1200);

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