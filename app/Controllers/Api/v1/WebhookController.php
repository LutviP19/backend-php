<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Core\Http\{Request,Response};
use App\Core\Security\Hash;
use App\Core\Security\Encryption;
use App\Core\Support\Log;

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
        $hash = new Hash();

        return $response->json(
            $this->getOutput(true, 200, [
                'message' => 'Hello world!', 
                'genkey' => Encryption::generateKey(),
                'pass' => encryptData($this->getPass()),
                'strlen' => strlen('3d1aea28467e1910344e924bee486a7ec4fdc9506ab1d3d7d68bdfc37b874055'),
                'hash' => $hash->make($this->getPass()),
                'unique' => $hash->unique(32),
            ]), 
            200);
    }
}