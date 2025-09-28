<?php

namespace App\Controllers\ServerApi;


use App\Core\Support\Config;
use App\Models\User;
// Events
use App\Core\Events\EventDispatcher;
use App\Services\OrderService;

class WebhookController extends ServerApiController
{
    protected $orderService;

    public function __construct()
    {
        parent::__construct();

        $dispatcher = new EventDispatcher();
        $this->orderService = new OrderService($dispatcher);
    }

    
    public function indexAction($request, $data): \Psr\Http\Message\ResponseInterface {
        // \App\Core\Support\Log::debug($request, 'WebhookController.indexAction.$request');
        $event = $request->getAttribute('event');
        $event = $data['attributes']['event'] ?: 'users.get';

        $requestData = [
                            'attributes' => $data['attributes'],
                            'jsonData' => $data['jsonData'],
                            'requestQuery' => $data['requestQuery']
                        ];
        
        // Get Status
        $status = false;
        switch ($event) {
            case 'users.get':
                $users = (new User())->all();
                if (!empty($users) && \count($users) <= rand(5,10)) {
                    $status = true;
                }
                break;
            case 'order.placed':
                $order = ['id' => rand(0, 2), 'items' => ['item1', 'item2']];
                if( false !== $this->orderService->placeOrder($order))
                    $status = true;
                break;
            default:
                $status = false;
                break;
        }

        // Format output
        $statusCode = $status ? 200 : 404;
        $output = $this->getOutput($status, $statusCode, [
                        'event'=> $event
                    ], 'Execute event: '. $event . ($status ? ' successfully.' : ' failed.!'));

        // Return \OpenSwoole\Core\Psr\Response
        return (new \OpenSwoole\Core\Psr\Response(\json_encode($output)))
                ->withHeaders(["Content-Type" => "application/json"])
                ->withStatus($statusCode);
    }
}