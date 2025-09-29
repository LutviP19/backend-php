<?php

namespace App\Controllers\ServerApi;

use App\Core\Http\BaseController;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;

class ServerApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // Session::unset('errors');
    }

    protected function SetOpenSwooleResponse($output, $statusCode, $headers = [])
    {
        return (new OpenSwooleResponse(\json_encode($output)))
                ->withHeaders(["Content-Type" => "application/json"] + $headers)
                ->withStatus($statusCode);
    }
}