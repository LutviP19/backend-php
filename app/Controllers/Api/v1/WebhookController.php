<?php

namespace App\Controllers\Api\v1;

use App\Core\Support\Config;
use App\Models\User;
use App\Core\Security\Middleware\ValidateClient;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use Exception;

use ReallySimpleJWT\Token;


class WebhookController extends ApiController
{
    public function __construct() {
        parent::__construct();

        // Middlewares
        try {
            (new \App\Core\Security\Middleware\RateLimiter('webhook_request', 3, 500, 1200))->setup();
        } catch(Exception $exception) {
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
    public function index(Request $request,Response $response)
    {
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

        // $ulid = User::getUlid(1);
        $ulid = generateUlid();

        // JWT
        $userId = $clientId;
        $secret = '3hrdBZGheOXrk%73Wvh%!!zbSRzfGj5Q%Q3!X9ib$16AP3HNFXe3pReTPdAy*Q%o';
        $expiration = time() + 3600;
        $issuer = clientIP();
        $tokenJwt = Token::create($userId, $secret, $expiration, $issuer);


        $output = $this->getOutput(true, 200, [ 
                'message' => 'Hello world!', 
                'client_ip' => clientIP(),
                'ulid' => $ulid,
                'token_jwt' => $tokenJwt,
                'match_jwt' => Token::validate($tokenJwt, $secret),
                'token' => $clientToken,
                'str_token' =>  $clientStrToken,
                'match_token' => $validateClient->matchToken($clientToken),
                'strlen' => strlen('5gbSVtgMFs96tGNGyBKVyjwREtj6uzPHmVnauvyhFpkLuZXEW4GIh8HGM2lW'),
                'genkey' => Encryption::generateKey(),
                'pass' => encryptData($this->getPass()),
                'myhash' => $myhash,
                'check_hash' => $hash->matchHash($unik, $myhash),
                'password' => $password,
                'check' => $hash->matchPassword($pass, $password),
                'unique' => $hash->unique(32),
            ]);
        // \App\Core\Support\Log::debug($output, 'WebhookController');

        return $response->json($output, 200);
    }
}