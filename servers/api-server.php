<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';

use App\Core\Support\Config;

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
        $serverParams = $request->getServerParams() ?? [];
        initializeServerConstant(array_merge($serverParams, $request->getHeaders() ?? []));

        var_dump('Middleware start clientIP:'.clientIP());
        // \App\Core\Support\Log::debug($_SERVER, 'ApiServer.MiddlewareSetup.process.$serverP');
        // \App\Core\Support\Log::debug(getallheaders(), 'ApiServer.MiddlewareSetup.process.getallheaders()');

        // EnsureIpIsValid
        if (!in_array(clientIP(), Config::get('trusted_ips'))) {
            return new Response('Service Unavailable', 503, '', ['Content-Type' => 'text/plain']);
        }

        // Validate Header
        $headers = getallheaders();
        $valid_headers = array_keys_exists(Config::get('valid_headers'), $headers);
        if (false === $valid_headers || ! isset($headers['X-Api-Token'])) {

            if (false === $valid_headers) {
                $statusCode = 500;
                $json = [
                            'status' => false,
                            'statusCode' => $statusCode,
                            'message' => 'Invalid header!',
                        ];
            }

            if ( ! isset($headers['X-Api-Token'])) {
                $statusCode = 403;
                $json = [
                            'status' => false,
                            'statusCode' => $statusCode,
                            'message' => 'Missing api token header!',
                        ];
            }

            return new Response(\json_encode($json), $statusCode, 'Missing credentials', ['Content-Type' => 'application/json']);
        }

        // Validate Api Token
        if(matchEncryptedData(config('app.token'), $headers['X-Api-Token'][0]) === false) {
            $statusCode = 403;
            $json = [
                        'status' => false,
                        'statusCode' => $statusCode,
                        'message' => 'Invalid api token!',
                    ];

            return new Response(\json_encode($json), $statusCode, '', ['Content-Type' => 'application/json']);
        }

        // Validate Token Client
        if (stripos($request->getUri()->getPath(), '/api') === 0) {
            // echo "URI: ". $request->getUri()->getPath();

            $status = true;
            if ( ! isset($headers['X-Client-Token'])) {
                $status = false; $statusCode = 403;
                $json = [
                            'status' => false,
                            'statusCode' => $statusCode,
                            'message' => 'Missing client token header!',
                        ];
            } else {
                if(\App\Core\Support\Session::has('uid')) {
            
                    $clientId = \App\Core\Support\Session::get('uid'); // Get from session
                    $validateClient = new \App\Core\Security\Middleware\ValidateClient($clientId);
                    $validate = $validateClient->matchToken($clientHeaderToken);
        
                    if (! $validate || is_null($validate)) {
                        $status = false; $statusCode = 401; $message = 'Invalid client token!';
                        $json = [
                            'status' => false,
                            'statusCode' => $statusCode,
                            'message' => $message,
                            'errors' => [ 'auth' => 'Invalid token!' ]
                        ];
                    }
                } else {
                    $status = false; $statusCode = 401; $message = 'Please login!';
                    $json = [
                        'status' => false,
                        'statusCode' => $statusCode,
                        'message' => $message,
                        'errors' => [ 'auth' => 'Session expired!' ]
                    ];
                }
            }

            if(false === $status)
            return new Response(\json_encode($json), $statusCode, 'Missing credentials', ['Content-Type' => 'application/json']);
            
        }

        $response = $handler->handle($request);
        var_dump('Middleware end');

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
$dispatcher = include __DIR__ .'/../routes/api-server.php';

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
        $serverParams = $request->getServerParams() ?? [];
        initializeServerConstant(array_merge($serverParams, $request->getHeaders() ?? []));
        // \App\Core\Support\Log::debug($_SERVER, 'ApiServer.RouteMiddleware.process.$_SERVER');

        // Only accept valid JSON content
        $contentType = $request->headers['content-type'];
        if (! is_null($contentType) && str_contains($contentType, 'application/json')) {
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
            return new Response('Not Acceptable content type.', 406, '', ['Content-Type' => 'text/plain']);
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
