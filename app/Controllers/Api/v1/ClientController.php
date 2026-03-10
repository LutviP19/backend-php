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

        $this->useMiddleware();
    }

    public function profile()
    {
        echo "Client controller.";
    }
}
