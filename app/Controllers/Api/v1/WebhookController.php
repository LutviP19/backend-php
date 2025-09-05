<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use App\Core\Support\Log;

class WebhookController extends ApiController
{
     /**
     * Show the home page.
     * 
     * @param App\Core\Http\Request $request
     * @param App\Core\Http\Response $response
     * @return void
     */
    public function index(Request $request,Response $response)
    {
        // $response->header('X-Value','eyJpdiI6InhmMHhXTGpvZ24wN1lIRmI2TlBKTlE9PSIsInZhbHVlIjoiSnFEajVxWnMwWEQ5aVI5NEd0Y200MDhPVjZKa21jV0lQNk9vWlBRSjdCMD0iLCJtYWMiOiI3ZmY1NmQwNzI0MmU0OTFjNzJjY2YwMzk3MTE2NzA0MmQzZDI3NjA2YWQzZWI0YzJjMjI2NzU2ZDExYjAyOTc0IiwidGFnIjoiIn0=');

        //get the request headers.
        $header = $request->headers();

        $hash = new Hash();
        $encryption = new Encryption();
        // $pass = '$2y$10$CWm7DuEQMXCrBfiEUEQbge7pxw4MzhTBgptWCU2yGmDNqzovQor3e';

        if(!isset($header['X-Value']) || 
            !$encryption->match($this->getPass(), $header['X-Value'])) {
                return $response->json([
                    'token' => $this->getPass(),
                    'message' => 'token missmatch!',
                ], 403);
        }

        // die(Log::getLogdir());

        Log::info($header, false);

        return $response->json([
            'message' => 'hello world!', 
            'genkey' => Encryption::generateKey(),
            'pass' => $encryption->encrypt('fX9&c3@8kLp#5ZqT7v$W!'),
            'strlen' => strlen('fX9&c3@8kLp#5ZqT7v$W!yR2N%hQ8m'),
            'hash' => $hash->make($this->getPass()),
            'unique' => $hash->unique(30),
        ], 200);
    }
}