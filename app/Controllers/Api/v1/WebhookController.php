<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
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
        // $response->header('X-Value','eyJpdiI6ImRGSmtjVEJpY0dOTVZsbHBUMWRhY2c9PSIsInZhbHVlIjoiN3M5QU41VDBjbEZiaXNsOWRwM1A1dz09IiwibWFjIjoiMDlkNDg3YzllOGYyMDgwYWZlOTYwNzM5ZWZkMzE0YWYwMWNkYjc4NzI4MTVhMzUwN2ExYjU3YjM2NzUyNDRlYyIsInRhZyI6IiJ9');

        //get the request headers.
        $header = $request->headers();

        $hash = new Hash();
        // $pass = '$2y$10$CWm7DuEQMXCrBfiEUEQbge7pxw4MzhTBgptWCU2yGmDNqzovQor3e';

        if(!isset($header['X-Value']) || 
            !$hash->match($this->getPass(), $header['X-Value'])) {
                return $response->json([
                    'message' => 'token missmatch!',
                ], 403);
        }

        // die(Log::getLogdir());

        Log::info($header, false);

        return $response->json([
            'message' => 'hello world!', 
            'token' => $hash->make($this->getPass()),
            'unique' => $hash->unique(10),
        ], 200);
    }
}