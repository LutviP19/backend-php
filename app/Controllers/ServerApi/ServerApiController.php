<?php

namespace App\Controllers\ServerApi;

use App\Core\Http\BaseController;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;

class ServerApiController extends BaseController
{
    protected $filter;

    public function __construct()
    {
        parent::__construct();
        $this->filter = new \App\Core\Validation\Filter();

        // Session::unset('errors');
    }

    protected function SetOpenSwooleResponse(bool $status, int $statusCode, array $output, string $message = '', array $headers = []) : OpenSwooleResponse
    {
        $json = $this->getOutput($status, $statusCode, $output, $message);

        return (new OpenSwooleResponse(\json_encode($json)))
                ->withHeaders(["Content-Type" => "application/json"] + $headers)
                ->withStatus($statusCode);
    }
}