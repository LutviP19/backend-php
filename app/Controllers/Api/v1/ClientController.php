<?php

declare(strict_types=1);

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
        // echo "Client controller, with default client middleware(useMiddleware).";
        try {
            $headers = [];
            $callback = false;
            $user = null;
            $message = '';
            $validator = new Validator();
            $validator->validate($this->jsonData, [
                'email' => 'required|email',
                // 'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');

            if ($errors) {
                $statusCode = 422;
                $message = 'Validation errors.';
            } else {
                $statusCode = 203;
                $message = 'Credentials errors.';
                $errors = ['auth' => 'Invalid credentials'];

                // Filter Input
                $jsonData = $this->filter->filter($this->jsonData, [
                    'email' => 'trim|sanitize_string'
                ]);

                // Sanitize Input
                $payload = $this->filter->sanitize($jsonData, ['email']);
                $email = readJson('email', $payload);

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
                // exit_response($single, $single['statusCode']);
                // exit_response($single['body'], $single['statusCode']);

                if ($single['error']) {
                    exit_response($single['body'], $single['statusCode']);
                } else {
                    $data = json_decode((string) $single['body'], true);
                    $user = (object) readJson('data.account', $data);
                    $rspEmail = readJson('data.account.email', $data);

                    if (!$user) {
                        exit_response($single['body'], $single['statusCode']);
                    }

                    $callback = (!empty($user) && $email === ($user->email ?? null) && Session::get('email') === $rspEmail);
                }
            }

            // // Middleware
            // // TODO:
            // // Invalid username_prefix dari get_device_fingerprint()
            // // decrease RateLimit lebih lama * (total kegagalan)
            // if ($this->rateLimit) {
            //     $this->setRatelimiter('login_request', 1200, 5);
            // }

            // // Test Gate - permission
            // // $this->validateAuthToken('super.view');
            // $this->validateAuthToken('administrator.view');

            // json_response(Session::all(), 200, "test");

            if ($callback) {

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
