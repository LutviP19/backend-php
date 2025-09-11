<?php

namespace App\Controllers\Api\v1;

use App\Models\User;
use App\Core\Security\Middleware\ValidateClient;
use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;

class WebhookController extends ApiController
{
    public function __construct() {
        parent::__construct();
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

        $clientId = '';
        $validateClient = new ValidateClient('01JP9MA549R9NNVNGHTHJFTNXJ');
        // $validateClient = new ValidateClient(1, 'id');
        $clientToken = $validateClient->generateToken();

        return $response->json(
            $this->getOutput(true, 200, [
                'message' => 'Hello world!', 
                'client_ip' => clientIP(),
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
            ]), 
            200);
    }
}