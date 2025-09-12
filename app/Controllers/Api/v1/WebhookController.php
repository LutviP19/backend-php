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

// use Maba\GentleForce\RateLimit\UsageRateLimit;
// use Maba\GentleForce\RateLimitProvider;
// use Maba\GentleForce\Throttler;
// use Maba\GentleForce\Exception\RateLimitReachedException;

class WebhookController extends ApiController
{
    protected $rateLimitProvider;
    protected $throttler;


    public function __construct() {
        parent::__construct();

        // Middlewares
        try {
            (new \App\Core\Security\Middleware\RateLimiter('webhook_request', 3, 900, 1200))->setup();
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
        // User::updateClientToken(3);        

        $hash = new Hash();
        $unik = $hash->unique(32);
        $unik = $this->getPass();
        $unik = '01JP9MA549R9NNVNGHTHJFTNXJ';
        $myhash = $hash->create($unik);

        $pass = 'password123';
        $password = $hash->makePassword($pass);

        $clientId = '01JP9MA549R9NNVNGHTHJFTNXJ';
        $validateClient = new ValidateClient($clientId);
        // $validateClient = new ValidateClient(1, 'id');
        $clientToken = $validateClient->generateToken();

        $ulid = generateUlid();

        // JWT
        $userId = '01JP9MA549R9NNVNGHTHJFTNXJ';
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
                'new_token' => generateRandomString(),
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