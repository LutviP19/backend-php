<?php

declare(strict_types=1);

namespace App\Controllers\Api\v1;

use App\Core\Auth\BaseAuth;
use App\Core\Support\Session;
use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Middleware\ValidateClient;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use App\Core\Validation\Validator;
use Exception;

class WebhookController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->rateLimit = false;

        // Middlewares
        if ($this->rateLimit) {
            try {
                $this->setRatelimiter('webhook_request', 500, 5);
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
        // global $requestServer;

        // if ( \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // on OpenSwoole Server

        //     $headers = $requestServer->header;
        //     $jsonData = \is_string($requestServer->rawContent()) ? \json_decode($requestServer->rawContent(), true) : [];
        // }

        // \App\Core\Support\Log::debug(gettype($request), 'WebhookController.index.gettype($request)');
        // \App\Core\Support\Log::debug($request, 'WebhookController.index.$request');

        // $jsonData = $jsonData ?: $request->all();
        // $filter = new \App\Core\Validation\Filter();

        // Validate Input
        $validator = new Validator();
        $validator->validate($this->jsonData, [
            'email' => 'required|email',
            'password'  => 'required|min:8|max:100',
        ]);
        $errors = \App\Core\Support\Session::get('errors');
        // \App\Core\Support\Log::debug($errors, 'WebhookController.index.errors');

        if ($errors) {
            $callback = false;
            // \App\Core\Support\Log::debug(gettype($callback), 'WebhookController.index.gettype($callback)');

            return endResponse(
                $this->getOutput(false, 203, [
                   $errors
                ]),
                203
            );
        }

        // Filter Input
        $this->jsonData = $this->filter->filter($this->jsonData, [
            'email' => 'trim|sanitize_string',
            'password'  => 'trim|sanitize_string',
        ]);
        // \App\Core\Support\Log::debug($jsonData, 'WebhookController.index.$filtered');

        // Sanitize Input
        $payload = $this->filter->sanitize($this->jsonData, ['email', 'password']);
        // \App\Core\Support\Log::debug($payload, 'WebhookController.index.sanitize.$payload');


        // \App\Core\Support\Log::debug($_SERVER, 'WebhookController.index.$_SERVER');
        // \App\Core\Support\Log::debug($_COOKIE, 'WebhookController.index.$_COOKIE');

        // \App\Core\Support\Log::debug($payload, 'WebhookController.index.payload');

        $email = readJson('email', $payload);
        $password = readJson('password', $payload);

        $canRead = readJson('credentials.read', $payload);
        $canWrite = readJson('credentials.write', $payload);
        $canDelete = readJson('credentials.delete', $payload);

        $status = $canRead;
        if ($status) {
            // \App\Core\Support\Log::debug($status, 'WebhookController.index.CREDENTIAL');

            $user = User::getUserByEmail($email);
            // \App\Core\Support\Log::debug($user, 'WebhookController.index.user');
        }


        $hash = new Hash();
        $unik = $hash->unique(32);
        // $unik = $this->getPass();
        // $unik = '01JP9MA549R9NNVNGHTHJFTNXJ';
        $myhash = $hash->create($unik);

        $pass = 'password123';
        $password = $hash->makePassword($pass);

        $id = 1;
        $clientId =  User::getUlid($id) ?? null;
        // $clientId =  '01JP9MA549R9NNVNGHTHJFTNXJ';
        // \json_response($clientId);

        // Init ValidateClient
        // $validateClient = new ValidateClient($id, 'id');
        $validateClient = new ValidateClient($clientId);

        // // Update Token
        // $validateClient->updateToken();
        // (new User())->updateClientToken('ulid', $clientId);

        // Get Token
        $clientToken = $validateClient->generateToken();
        $clientStrToken = $validateClient->getToken();

        // generate ULID
        $ulid = generateUlid();

        Session::set('uid', $clientId);
        $uid = Session::get('uid');

        // BaseAuth Key Generator
        $keygenJwt = generateRandomString(64);
        $keygenSodium = generateUniqueString(32);
        $keygenSodiumV4 = (new \App\Core\Auth\SodiumAuthV4())->generateKeys();

        // Init BaseAuth Token
        $authTokenJwt = new BaseAuth('JWT', Config::get('session.exptime'));
        $authTokenJwt->setCustomKeys($keygenJwt); // Custom Key
        $tokenAuthJwt = $authTokenJwt->generateToken(['uid' => $ulid]);

        $authToken = new BaseAuth('Sodium', Config::get('session.exptime'));
        $authToken->setCustomKeys($keygenSodium); // Custom Key
        $tokenAuth = $authToken->generateToken(['uid' => $uid, "user_permissions" => cacheContent('get', "user_abilities:{$uid}")]);
        $tokenAuthRefresh = $authToken->refreshToken($tokenAuth['refresh_token'] ?? '');

        $authTokenV4 = new BaseAuth('Sodium-v4', Config::get('session.exptime'));
        // ['private' => $privateKey, 'public' => $publicKey] = $keygenSodiumV4;
        // $authTokenV4->setCustomKeys($privateKey, $publicKey); // Custom Keys
        $authTokenV4->setCustomKeys(...array_values($keygenSodiumV4)); // Simple Custom Keys
        $tokenAuthV4 = $authTokenV4->generateToken(['uid' => $ulid]);

        $genPass = encryptData($this->getPass());
        $output = $this->getOutput(true, 200, [
                'message' => 'Hello world!',
                'server' =>  \isSwoole() ? "OpenSwoole" : "PHP FPM",
                'client_ip' => clientIP(),
                'session_id' => session_id(),
                'ulid' => $ulid,
                'client_id' => $clientId,

                // BaseAuth
                'keygenJwt' => $keygenJwt,
                'keygenSodium' => $keygenSodium,
                'keygenSodiumV4' => $keygenSodiumV4,
                'token_jwt' => $tokenAuthJwt,
                'match_jwt' => $authTokenJwt->validateToken($tokenAuthJwt['access_token']),
                'token_auth' => $tokenAuth,
                'match_auth' => $authToken->validateToken($tokenAuth['access_token']),
                'token_auth_refresh' => $tokenAuthRefresh,
                'abilities_auth' => cacheContent('get', "user_abilities:{$uid}"),
                // 'validateAuthToken' => $this->validateAuthToken('super.view'),

                'token_auth_v4' => $tokenAuthV4,
                'match_auth_v4' => $authTokenV4->validateToken($tokenAuthV4['access_token']),

                // Others Encrypt
                'token' => $clientToken,
                'str_token' =>  $clientStrToken,
                'match_token' => $validateClient->matchToken($clientToken),
                'strlen' => strlen('1Lzmakn0yrtdW0Ws1GF!AK9MDm6V305p*KKK8ED!2XNm8^aAnhg!PV9n5G5ubjyp'),
                'genkey' => Encryption::generateKey(),
                'pass' => $genPass,
                'decrypt_pass' => \decryptData($genPass),
                'match_pass' => matchEncryptedData($this->getPass(), $genPass),
                'myhash' => $myhash,
                'check_hash' => $hash->matchHash($unik, $myhash),
                'password' => $password,
                'check_pass' => $hash->matchPassword($pass, $password),
                'rand_str' => generateRandomString(16),
                'unique' => $hash->unique(32),
                'user' => Session::all()
            ], 'WEBHOOK EXAMPLE');
        // \App\Core\Support\Log::debug($output, 'WebhookController');

        return endResponse($output, 200);
    }
}
