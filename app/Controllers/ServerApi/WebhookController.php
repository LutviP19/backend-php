<?php

namespace App\Controllers\ServerApi;


use App\Core\Support\Config;
use App\Models\User;

class WebhookController extends ServerApiController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function indexAction($request, $data): \Psr\Http\Message\ResponseInterface {
        \App\Core\Support\Log::debug($request, 'WebhookController.bpIndex.$request');
        $name = $request->getAttribute('name');
        $name = $data['attributes']['name'] ?: '';

        $users = (new User())->all();

        $output = $this->getOutput(true, 200, [
                        'message' => 'Hello world!, '.$name,
                        'users' => $users,
                        'jsonData' => $data['jsonData'],
                        'requestQuery' => $data['requestQuery'],
                    ]);

        return (new \OpenSwoole\Core\Psr\Response(\json_encode($output)))
                ->withHeaders(["Content-Type" => "application/json"])
                ->withStatus(200);
    }
}