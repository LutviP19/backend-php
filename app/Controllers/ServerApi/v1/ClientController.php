<?php
declare(strict_types=1);

namespace App\Controllers\ServerApi\v1;


use App\Controllers\ServerApi\ServerApiController;
use App\Core\Support\Session;


class ClientController extends ServerApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function indexAction($request, array $data)
    {
        // Validate header X-Client-Token + JWT
        $validateOutput = $this->useMiddleware();
        if($validateOutput) return $validateOutput;
        
        $requestData = [
            'attributes' => $data['attributes'],
            'jsonData' => $data['jsonData'],
            'requestQuery' => $data['requestQuery']
        ];
        $jsonData = $data['jsonData'];

        // Set default output
        $status = true;
        $statusCode = 200;
        $output = [];
        $message = '';
        $headers = [];

        

        $statusCode = 200;
        $output = [ 'account' => Session::all() ];

        return $this->SetOpenSwooleResponse($status, $statusCode, $output, $message, $headers);
    }

}
