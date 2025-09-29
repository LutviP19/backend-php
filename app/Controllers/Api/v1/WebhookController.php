<?php

namespace App\Controllers\Api\v1;

use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Middleware\ValidateClient;
use App\Core\Security\Middleware\JwtToken;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use App\Core\Validation\Validator;
use Exception;
use ReallySimpleJWT\Token;

class WebhookController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        // Middlewares
        if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // OpenSwoole Server
            try {
                (new \App\Core\Security\Middleware\RateLimiter('webhook_request'))
                    ->setup(clientIP(), 5, 500, 1200);
            } catch (Exception $exception) {
                die($exception->getMessage());
            }
        }
    }

    // Test Api Server OpenSwoole
    public function bpIndex($request, $data): \Psr\Http\Message\ResponseInterface
    {
        \App\Core\Support\Log::debug($request, 'WebhookController.bpIndex.$request');
        $name = $request->getAttribute('name');
        $name = $data['attributes']['name'] ?: '';

        $output = $this->getOutput(true, 200, [
                        'message' => 'Hello world!, '.$name,
                        'jsonData' => $data['jsonData'],
                        'requestQuery' => $data['requestQuery'],
                    ]);

        return (new \OpenSwoole\Core\Psr\Response(\json_encode($output)))
                ->withHeaders(["Content-Type" => "application/json"])
                ->withStatus(200);
    }

    /**
    * Show the home page.
    *
    * @param App\Core\Http\Request $request
    * @param App\Core\Http\Response $response
    * @return void
    */
    public function index(Request $request, Response $response)
    {
        // \App\Core\Support\Log::debug(gettype($request), 'WebhookController.index.gettype($request)');
        \App\Core\Support\Log::debug($request, 'WebhookController.index.$request');

        if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // OpenSwoole Server
            $reqData = $request->all();
            $filter = new \App\Core\Validation\Filter();

            // Validate Input
            \App\Core\Support\Session::unset('errors');
            $validator = new Validator();
            $validator->validate($reqData, [
                'email' => 'required|email',
                'password'  => 'required|min:8|max:100',
            ]);
            $errors = \App\Core\Support\Session::get('errors');
            \App\Core\Support\Log::debug($errors, 'WebhookController.index.errors');


            if ($errors) {
                $callback = function () { return false; };

                \App\Core\Support\Log::debug(gettype($callback), 'WebhookController.index.gettype($callback)');

                die($response->json(
                    $this->getOutput(false, 203, [
                       $errors
                    ]),
                    203
                ));
            }

            // Filter Input
            $reqData = $filter->filter($reqData, [
                'email' => 'trim|sanitize_string|sanitize_email',
                'password'  => 'trim|sanitize_string',
            ]);
            \App\Core\Support\Log::debug($reqData, 'WebhookController.index.$filtered');

            // Sanitize Input
            $reqData = $filter->sanitize($reqData, ['email', 'password', 'credentials']);
            \App\Core\Support\Log::debug($reqData, 'WebhookController.index.sanitize.$reqData');
        }

        \App\Core\Support\Log::debug($_SERVER, 'WebhookController.index.$_SERVER');
        \App\Core\Support\Log::debug($_COOKIE, 'WebhookController.index.$_COOKIE');

        $payload = $request->all();
        \App\Core\Support\Log::debug($payload, 'WebhookController.index.payload');

        $email = readJson('email', $payload);
        $password = readJson('password', $payload);

        $canRead = readJson('credentials.read', $payload);
        $canWrite = readJson('credentials.write', $payload);
        $canDelete = readJson('credentials.delete', $payload);

        $status = $canRead;
        if ($status) {
            // \App\Core\Support\Log::debug($status, 'WebhookController.index.CREDENTIAL');

            $user = User::getUserByEmail($email);
        }
        // \App\Core\Support\Log::debug($user, 'WebhookController.index.user');


        $hash = new Hash();
        $unik = $hash->unique(32);
        // $unik = $this->getPass();
        // $unik = '01JP9MA549R9NNVNGHTHJFTNXJ';
        $myhash = $hash->create($unik);

        $pass = 'password123';
        $password = $hash->makePassword($pass);

        $clientId =  User::getUlid(1) ?: null;
        // $clientId =  '01JP9MA549R9NNVNGHTHJFTNXJ';

        // Init ValidateClient
        // $validateClient = new ValidateClient(1, 'id');
        $validateClient = new ValidateClient($clientId);

        // Update Token
        // $validateClient->updateToken();
        // (new User())->updateClientToken('ulid', $clientId);

        // Get Token
        $clientToken = $validateClient->generateToken();
        $clientStrToken = $validateClient->getToken();

        // generate ULID
        $ulid = generateUlid();

        // JWT
        $userId = $clientId;
        $secret = $clientStrToken;
        $expirationTime = 3600;
        $jwtId = generateUlid();
        $issuer = clientIP();
        $audience = Config::get('app.url');
        // Init JwtToken
        $jwtToken = new JwtToken($secret, $expirationTime, $jwtId, $issuer, $audience);
        // create specific Token
        $info = 'Webhook jwt';
        $subject = 'Access Webhook API';
        $tokenJwt = $jwtToken->createToken($userId, $info, $subject);

        $token_api = encryptData($this->getPass());
        $output = $this->getOutput(true, 200, [
                'message' => 'Hello world!',
                'client_ip' => clientIP(),
                'session_id' => session_id(),
                'ulid' => $ulid,
                'token_jwt' => $tokenJwt,
                'parse_jwt' => $jwtToken->parseJwt($tokenJwt),
                'match_jwt' => $jwtToken->validateToken($tokenJwt),
                'token' => $clientToken,
                'str_token' =>  $clientStrToken,
                'match_token' => $validateClient->matchToken($clientToken),
                'strlen' => strlen('5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW'),
                'genkey' => Encryption::generateKey(),
                'pass' => $token_api,
                'decrypt_pass' => \decryptData($token_api),
                'match_pass' => matchEncryptedData($this->getPass(), $token_api),
                'myhash' => $myhash,
                'check_hash' => $hash->matchHash($unik, $myhash),
                'password' => $password,
                'check_pass' => $hash->matchPassword($pass, $password),
                'rand_str' => generateRandomString(16),
                'unique' => $hash->unique(32),
            ]);
        // \App\Core\Support\Log::debug($output, 'WebhookController');

        return $response->json($output, 200);
    }
}
