<?php
declare(strict_types=1);

namespace App\Controllers\ServerApi;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use App\Core\Validation\Validator;
use App\Core\Support\Session;
use App\Core\Support\Config;
use App\Models\User;
// Events
use App\Core\Events\EventDispatcher;
use App\Services\OrderService;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;

class WebhookController extends ServerApiController
{
    protected $orderService;

    public function __construct()
    {
        parent::__construct();

        $dispatcher = new EventDispatcher();
        $this->orderService = new OrderService($dispatcher);
    }


    public function indexAction($request, array $data) {
        // \App\Core\Support\Log::debug($request, 'APIWebhookController.indexAction.$request');
        $event = $request->getAttribute('event');
        $event = $data['attributes']['event'] ?: 'users.get';

        $requestData = [
                            'attributes' => $data['attributes'],
                            'jsonData' => $data['jsonData'],
                            'requestQuery' => $data['requestQuery']
                        ];

        $jsonData = $data['jsonData'];
        $filter = new \App\Core\Validation\Filter();

        // Validate Input
        \App\Core\Support\Session::unset('errors');
        $validator = new Validator();
        $validator->validate($jsonData, [
            'email' => 'required|email',
            'password'  => 'required|min:8|max:100',
        ]);
        $errors = \App\Core\Support\Session::get('errors');
        // \App\Core\Support\Log::debug($errors, 'APIWebhookController.indexAction.errors');
            
        if ($errors) {
            $statusCode = 203;
            return $this->SetOpenSwooleResponse(false, $statusCode, $errors, 'Validation errors.');
        }

        // Filter Input
        $jsonData = $this->filter->filter($jsonData, [
            'email' => 'trim|sanitize_string',
            'password'  => 'trim|sanitize_string',
        ]);
        // \App\Core\Support\Log::debug($jsonData, 'APIWebhookController.indexAction.$filtered');

        // Sanitize Input
        $jsonData = $this->filter->sanitize($jsonData, ['email', 'password', 'credentials']);
        // \App\Core\Support\Log::debug($jsonData, 'APIWebhookController.indexAction.sanitize.$jsonData');

        // If Session not set
        if (false === Session::has('indexAction')) {
            Session::set('indexAction', \generateUlid());
        }
        
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
        $message = 'Execute event: '. $event . ($status ? ' successfully.' : ' failed.!');
        $output = [
                        'event' => $event,
                        'errors' => $errors,
                        'session' => Session::all(),
                    ];

        // Unset Session
        // Session::unset('indexAction');

        // Return \OpenSwoole\Core\Psr\Response
        return $this->SetOpenSwooleResponse($status, $statusCode, $output);
    }
}