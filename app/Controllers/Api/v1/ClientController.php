<?php

namespace App\Controllers\Api\v1;

use App\Core\Http\Client\NativeCurlStreamer;
use App\Core\Support\App;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request, Response};
use App\Core\Support\Session;
use App\Core\Validation\Validator;

/*
*   Client settings
*/

class ClientController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->useMiddleware();
    }

    public function profile(Request $request, Response $response)
    {
         try {
            $headers = [];
            $callback = false;
            $message = '';
            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
            ]);
            $errors = \App\Core\Support\Session::get('errors');
            // \App\Core\Support\Log::debug($errors, 'WebAuth.login.$errors');

            if ($errors) {
                $statusCode = 422;
                $message = 'Validation errors.';
            } else {
                $statusCode = 203;
                $message = 'Credentials errors.';
                $errors = ['auth' => 'Invalid credentials'];

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string',
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email']);

                $email = readJson('email', $payload);
                $callback = false;

                // A. TEST SINGLE STREAM 
                $headers = getallheaders();
                $streamer = new NativeCurlStreamer();
                $singleTask = App::externalApi('backend_profile', [
                    'body' => ['email' => $email],
                    'headers' => [
                        'X-Client-Token' => $headers['X-Client-Token'] ?? Session::get('client_token') ?? null,
                    ]
                ]);
                $single = $streamer->singleStream($singleTask);
                // exit_response($single['body'], $single['statusCode']);

                if ($single['error']) {
                    exit_response($single['body'], $single['statusCode']);
                } else {
                    $data = json_decode((string) $single['body'], true);
                    $user = readJson('data.account', $data);
                    $rspEmail = readJson('data.account.email', $data);

                    if(!$user) {
                        exit_response($single['body'], $single['statusCode']);
                    }

                    $callback = ($email === $user['email'] && Session::get('email') === $rspEmail);
                }
            }

            // // Middleware
            // if ($this->rateLimit) {
            //     $this->setRatelimiter('login_request', 1200, 5);
            // }

            if ($callback && !empty($user)) {

                // Cache session data by uid
                if (\isSwoole()) {

                    // Clean old keys
                    // delCache($_SESSION['uid'].'*', 'bp_session');
                    // cacheContent('set', $_SESSION['uid'] .'-'. $this->sessionId, 'bp_session', $_SESSION);
                    
                    // delCache($_SESSION['uid']);
                    // cacheContent('set', $_SESSION['uid'] .'-'. $this->sessionId, $_SESSION);
                }

                return endResponse(
                    $this->getOutput(true, 201, [
                            // 'token' => $tokenJwt,
                            // 'all' => $_SESSION,
                            // 'sessionKey' => Session::get('sessionKey'),
                            'account' => Session::all()
                    ]),
                    201,
                    $headers
                );

            } else {
                
                return endResponse(
                    $this->getOutput(false, $statusCode, [
                        $errors
                   ], $message),
                    $statusCode
                );
            }
        } catch (\Exception $exception) {

            return endResponse(
                $this->getOutput(false, 429, [
                  'exception', $exception->getMessage(),
               ]),
                429
            );
        }
    }
}
