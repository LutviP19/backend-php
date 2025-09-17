<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Core\Http\{Request, Response};

/*
*   Client settings
*/

class ClientController extends ApiController
{

    public function __construct()
    {
        parent::__construct();

        // // Validate header X-Client-Token
        // $this->validateClientToken($request, $response);

        // // Validate JWT
        // $this->validateJwt($request, $response);

        (new \App\Core\Security\Middleware\ValidateSession())
            ->handle($this->request(), $this->response());
    }

    public function profile()
    {
        echo "Client controller.";
    }
}
