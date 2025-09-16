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
    public function __construct() {
        parent::__construct();

        // Middlewares
        try {
            (new \App\Core\Security\Middleware\RateLimiter('webhook_request'))
                ->setup(clientIP(), 5, 500, 1200);
        } 
        catch(Exception $exception) {
            die($exception->getMessage());
        }
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

        $validator = new Validator;
        $validator->validate($request, [
            'email' => 'required|email',
            'password'  => 'required|min:8|max:100',
        ]);
        $errors = errors()->all();
        \App\Core\Support\Log::debug($errors, 'WebhookController.index.errors');

        if($errors) {
            $callback = function(){ return false; };

            \App\Core\Support\Log::debug(gettype($callback), 'WebhookController.index.gettype($callback)');

            die($response->json(
                $this->getOutput(false, 203, [
                   $errors
                ])
             , 203));
        }

        // \App\Core\Support\Log::debug($_REQUEST, 'WebhookController.index.$_REQUEST');

        $payload = $request->all();
        \App\Core\Support\Log::debug($payload, 'WebhookController.index.payload');

        $email = readJson('email', $payload);
        $password = readJson('password', $payload);

        $canRead = readJson('credentials.read', $payload);
        $canWrite = readJson('credentials.write', $payload);
        $canDelete = readJson('credentials.delete', $payload);

        $status = $canRead;
        if($status)
        // \App\Core\Support\Log::debug($status, 'WebhookController.index.CREDENTIAL');

        $user = User::getUserByEmail($email);
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

        $output = $this->getOutput(true, 200, [
                'message' => 'Hello world!', 
                'client_ip' => clientIP(),
                'ulid' => $ulid,
                'token_jwt' => $tokenJwt,
                'parse_jwt' => $jwtToken->parseJwt($tokenJwt),
                'match_jwt' => $jwtToken->validateToken($tokenJwt),
                'token' => $clientToken,
                'str_token' =>  $clientStrToken,
                'match_token' => $validateClient->matchToken($clientToken),
                'strlen' => strlen('5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW'),
                'genkey' => Encryption::generateKey(),
                'pass' => encryptData($this->getPass()),
                'myhash' => $myhash,
                'check_hash' => $hash->matchHash($unik, $myhash),
                'password' => $password,
                'check_pass' => $hash->matchPassword($pass, $password),
                'rand_str' => generateRandomString(16),
                'unique' => $hash->unique(32),
            ]);
        \App\Core\Support\Log::debug($output, 'WebhookController');

        return $response->json($output, 200);
    }
}