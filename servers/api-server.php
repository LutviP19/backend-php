<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';


use FastRoute\RouteCollector;
use OpenSwoole\Core\Psr\Middleware\StackHandler;
use OpenSwoole\Core\Psr\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;

$server = new Server($serverip, $serverport);
// Server settings
$server->set([
    // Process ID
    "pid_file" => __DIR__ . "/apisrv-swoole.pid",
    // 'document_root' => __DIR__ .'../public',
    'document_root' => realpath(__DIR__ . '/../public/'),

    // // Setup SSL files
    // 'ssl_cert_file' => $ssl_dir . '/ssl.crt',
    // 'ssl_key_file' => $ssl_dir . '/ssl.key',

    // Logging
    "log_file" => __DIR__ . "/../storage/logs/apisrv-swoole.log",
    "log_rotation" => SWOOLE_LOG_ROTATION_DAILY,
    "log_date_format" => "%d-%m-%Y %H:%M:%S",
    "log_date_with_microseconds" => false,

    // Compression
    'http_compression' => true,
    'http_compression_level' => 3, // 1 - 9
    'compression_min_length' => 20,

    // // Coroutine
    // 'enable_coroutine' => false,

    // // Protocol
    // 'open_http_protocol' => true,
    // 'open_http2_protocol' => true,
    // 'open_websocket_protocol' => true,
    // 'open_mqtt_protocol' => true,

    // // HTTP2
    // 'http2_header_table_size' => 4095,
    // 'http2_initial_window_size' => 65534,
    // 'http2_max_concurrent_streams' => 1281,
    // 'http2_max_frame_size' => 16383,
    // 'http2_max_header_list_size' => 4095,
]);

// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole api server is started at http://" . $serverip . ":" . $serverport . "\n";
});


class MiddlewareSetup implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        var_dump('Setup start');


        $response = $handler->handle($request);
        var_dump('Setup end');

        return $response;
    }
}

class MiddlewareA implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestBody = $request->getBody();
        var_dump('A1');
        $response = $handler->handle($request);
        var_dump('A2');
        return $response;
    }
}

class MiddlewareB implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestBody = $request->getBody();
        var_dump('B1');
        $response = $handler->handle($request);
        var_dump('B2');
        return $response;
    }
}

// Routing API here
$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/hello/{name}', function ($request) {
        $name = $request->getAttribute('name');
        $json = json_encode(
            [
                        'message' => $name,
                        'data' => ['users' => [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']]]
                    ]
        );

        return (new Response($json))->withHeaders(["Content-Type" => "application/json"])->withStatus(200);
    });

    // Testing Call Controller
    $r->addRoute('POST', '/webhook/{name}', function ($request) {
        // return  (new \App\Controllers\Api\v1\WebhookController())->bpIndex($request, getRequestData($request));
        return  (new \App\Controllers\ServerApi\WebhookController())->indexAction($request, getRequestData($request));
    });
});

class RouteMiddleware implements MiddlewareInterface
{
    private $dispatcher;

    public function __construct($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    //\OpenSwoole\Core\Psr\ServerRequest ServerRequestInterface
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Init $_SERVER attributes
        $serverParams = $request->getServerParams();
        initializeServerConstant($serverParams);
        \App\Core\Support\Log::debug($_SERVER, 'ApiServer.RouteMiddleware.process.$_SERVER');

        $contentType = $request->headers['content-type'];

        // Only accept JSON content
        if (str_contains($contentType, 'application/json')) {
            
            // Get JSON
            $body = $request->getBody();
            $body->rewind();
            $rawBody = $body->getContents();
            $jsonData = json_decode($rawBody, true);

            // Check valid JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle JSON decoding error
                $error = json_last_error_msg();
                // Log or display the error message
                // \App\Core\Support\Log::debug($error, 'ApiServer.RouteMiddleware.addRoute.json_last_error_msg');
                return new Response('Invalid Json data!,'.$error, 406, '', ['Content-Type' => 'text/plain']);
            }

            // Dispatch Route
            $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

            switch ($routeInfo[0]) {
                case \FastRoute\Dispatcher::NOT_FOUND:
                    return new Response('Not found', 404, '', ['Content-Type' => 'text/plain']);
                case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    return new Response('Method not allowed', 405, '', ['Content-Type' => 'text/plain']);
                case \FastRoute\Dispatcher::FOUND:
                    foreach ($routeInfo[2] as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                    return $routeInfo[1]($request);
            }

        } else {
            return new Response('Not Acceptable format', 406, '', ['Content-Type' => 'text/plain']);
        }
    }
}

$stack = (new StackHandler())
    ->add(new RouteMiddleware($dispatcher))
    ->add(new MiddlewareA())
    ->add(new MiddlewareB())
    ->add(new MiddlewareSetup())
;

$server->setHandler($stack);

$server->start();
