<?php

declare(strict_types=1);

// Disabled Log Errors
ini_set('log_errors', 0);
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/bootstrap.php';

// Set a custom session name
ini_set('session.use_strict_mode', 0);
session_name('HTTPBACKENDPHPSESSID');
// ini_set('session.use_strict_mode', 1);

// Set Session
$sessionName = session_name();
$sessionId = '';



use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;
use OpenSwoole\Core\Psr\Middleware\StackHandler;
use OpenSwoole\Core\Psr\Response as PsrResponse;
use OpenSwoole\HTTP\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

$serverip = "127.0.0.1";
$serverport = 9501;
$max_request = 10000;
$ssl_dir = __DIR__ . "/../storage/ssl";
$requestServer = new OpenSwooleRequest();
$openSwooleResponse = new OpenSwooleResponse();
$clientInfo = '';
$ignoredUri = ['/metrics', '/health', '/favicon.ico'];

$server = new Server($serverip, $serverport);

// https
// $server = new Server($serverip, $serverport, Server::SIMPLE_MODE, \OpenSwoole\Constant::SOCK_TCP | \OpenSwoole\Constant::SSL);

$redis = new \Predis\Client([
    'host' => config('redis.default.host'),
    'port' => config('redis.default.port'),
    'database' => config('redis.default.database')
]);

// Server settings
$server->set([
    // Process ID
    "pid_file" => __DIR__ . "/http-swoole.pid",
    'document_root' => __DIR__ . '/../public/',

    // Workers
    'worker_num' => 2,
    'task_worker_num' => 5,
    //'max_request' => 10000,
    //'max_request_grace' => 0,

    // // Setup SSL files
    // 'ssl_cert_file' => $ssl_dir . '/ssl.crt',
    // 'ssl_key_file' => $ssl_dir . '/ssl.key',

    // Logging
    "log_file" => __DIR__ . "/../storage/logs/http-swoole.log",
    "log_rotation" => SWOOLE_LOG_ROTATION_DAILY,
    "log_date_format" => "%d-%m-%Y %H:%M:%S",
    "log_date_with_microseconds" => false,

    // Compression
    'http_compression' => true,
    'http_compression_level' => 3, // 1 - 9
    'compression_min_length' => 20,

    // // Coroutine
    'enable_coroutine' => false,

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

// $process = new OpenSwoole\Process(function ($process) use ($server) {
//     while (true) {
//         $msg = $process->read();
//         var_dump($msg);

//         foreach ($server->connections as $conn) {
//             $server->send($conn, $msg);
//         }
//     }
// });

// $server->addProcess($process);


class CustomServerRequest extends \OpenSwoole\Core\Psr\ServerRequest
{
}

class ExitException extends \OpenSwoole\ExitException
{
}

// Start session
if (session_status() === PHP_SESSION_NONE) {

    session_start();

    session_regenerate_id(true);
    $sessionId = session_id();
    // setcookie(session_name(), $sessionId, (env('SESSION_LIFETIME', 120) * 60), '/');
}

// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport, $sessionId, $sessionName;

    echo "Swoole http server is started at http://" . $serverip . ":" . $serverport . "\n";
});

$server->on("Connect", function (Server $server, int $fd) {
    global $clientInfo, $sessionId, $sessionName;

    echo "{$fd} Connect, worker:" . $server->worker_id . PHP_EOL;

    $clientInfo = $server->getClientInfo($fd);
    // var_dump($clientInfo);

    if ($clientInfo) {
        echo "Client connected: " . $clientInfo['remote_ip'] . "\n";
        echo "Client connected port: " . $clientInfo['remote_port'] . "\n";
        echo "Http sessStatus: " . session_status() . "\n";
        // echo "Http sessionId: " . $sessionId . "\n";
    }

    // Session Active
    if (session_status() === PHP_SESSION_ACTIVE) {
        
    }

});

$server->on('request', function (OpenSwooleRequest $request, OpenSwooleResponse $response) use ($server) {
    global $server, $clientInfo, $ignoredUri, $sessionId, $sessionName;

    $uri = $request->server['request_uri'];

    $clientInfo = $server->getClientInfo($request->fd);
    // var_dump($clientInfo);
    try {
        // Log the incoming request method
        echo "Received a '{$request->server['request_method']}:'{$request->server['request_uri']} request\n";

        if ((session_status()) == PHP_SESSION_ACTIVE) {
            $sessionName = session_name();

            echo "Http sessionName: " . $sessionName . "\n";
            echo "Http sessionId: " . $sessionId . "\n";
        }

        if (isset($request->header['user-agent'])) {
            $userAgent = $request->header['user-agent'];
            echo  "Client User-Agent: " . $userAgent . PHP_EOL;
        } else {
            echo "Client User-Agent header not found." . PHP_EOL;
        }

        // Handle an OPTIONS request with an empty response
        if ($request->server['request_method'] === 'OPTIONS') {
            // Explicitly set an HTTP status code for preflight requests
            $response->status(204); // 204 No Content
            // End the response without a body
            $response->end();
            return;
        }

        // Handle /metrics URI
        if ($uri === '/metrics') {
            $response->header("Content-Type", "text/plain");

            $localIps = config('local_ips');
            if (in_array($clientInfo["remote_ip"], config('local_ips'))) {
                $content = $server->stats(\OPENSWOOLE_STATS_OPENMETRICS);
            } else {
                $content = "404 Not Found";
                $response->status(404);
            }

            $response->end($server->stats(\OPENSWOOLE_STATS_OPENMETRICS));
            return;
        }

        // \App\Core\Support\Log::debug($request, 'HttpServer.fetchDataAsynchronously.$request');
        // Init Server constants
        initializeServerConstant($request);

        // Get header metadata
        $headers = getallheaders();

        if (isset($_COOKIE[$sessionName])) {
            // Try get session data from Redis
            $_SESSION = cacheContent('get', $_COOKIE[$sessionName], 'bp_session') ?: [];

            // \App\Core\Support\Log::debug($sessionData, 'HttpServer.fetchDataAsynchronously.first.$sessionData');
            // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.first.$_SESSION');
            // \App\Core\Support\Log::debug(\App\Core\Support\Session::all(), 'HttpServer.fetchDataAsynchronously.first.Session::all()');

            // \App\Core\Support\Log::debug( $_COOKIE[$sessionName], 'HttpServer.request.$_COOKIE[$sessionName]');
            $getSessionId = explode("-", $_COOKIE[$sessionName]);
            if (count($getSessionId) == 2) {
                $sessionId = $_COOKIE[$sessionName];
            } else {
                $sessionId = session_id();
            }
        }


        // print_r($server->stats());
        // \App\Core\Support\Log::debug($server->stats() 'Swoole.request.$server->stats()');

        // Simulate some asynchronous operation (e.g., fetching data from a database)
        go(function () use ($server, $request, $response, $clientInfo, $sessionId, $sessionName, $uri, $ignoredUri) {
            
            // $returned = ['response', 'tmp', 'void'];
            $content = fetchDataAsynchronously($request, $response, 'content', $_SESSION);

            if ($response->isWritable()) {
                $response->end($content);
            }

            throw new ExitException();
        });


        // End process
        echo  "---------" . PHP_EOL;
    } catch (Throwable $e) {

        // Handle exceptions and errors
        $response->status(500);
        $response->end('Internal Server Error');
        // Log the exception or send it to an error monitoring service
        echo "Exception: " . $e->getMessage() . "\n";
    }
});

$server->on('Task', function (Swoole\Server $server, $task_id, $reactorId, $data) {
    echo "Task Worker Process received data";
    echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . strlen($data) . "." . PHP_EOL;
    $server->finish($data);
});

$server->start();

// Simulated asynchronous function to fetch data from a database
function fetchDataAsynchronously(OpenSwooleRequest $request, OpenSwooleResponse $response, $returned = 'response', &$sessionData)
{
    global $server, $clientInfo, $ignoredUri, $requestServer, $sessionId, $sessionName;

    // \App\Core\Support\Log::debug($request, 'HttpServer.fetchDataAsynchronously.$request');
    // requestServer
    $requestServer = $request;

    $uri = $request->request_uri ?? $_SERVER['REQUEST_URI'];

    if (! in_array($uri, $ignoredUri)) {
        // Try get session data from Redis
        $_SESSION['app'] = 'web';
        // $_SESSION = array_merge($_SESSION, $sessionData, cacheContent('get', $_COOKIE[$sessionName], 'bp_session') ?: []);
        $_SESSION = array_merge($_SESSION, $sessionData);
    }

    // \App\Core\Support\Log::debug($sessionData, 'HttpServer.fetchDataAsynchronously.first.$sessionData');
    // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.first.$_SESSION');
    // \App\Core\Support\Log::debug(\App\Core\Support\Session::all(), 'HttpServer.fetchDataAsynchronously.first.Session::all()');

    // Check response Writable status
    if ($response->isWritable()) {
        echo "FD:{$request->fd}, Writable!\n";
        echo "Curr sessionName: " . $sessionName . "\n";
        echo "Curr sessionId: " . $sessionId . "\n";
    } else {
        $response = $response::create($request->fd);
        echo "New-FD:{$request->fd}, Created!\n";
    }

    // \App\Core\Support\Log::debug($request, 'HttpServer.fetchDataAsynchronously.$request');
    // \App\Core\Support\Log::debug($_SERVER, 'HttpServer.fetchDataAsynchronously.$_SERVER');
    // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.$_SESSION');
    // \App\Core\Support\Log::debug($_COOKIE, 'HttpServer.fetchDataAsynchronously.$_COOKIE');

    $baseDir = __DIR__ .'/../public';
    $filePath = $baseDir . '/index.php';
    $fileName = str_replace('/', '', $uri).".php";

    // Routing REST-API content
    switch ($uri) {
        case '/auth/login':
        case '/auth/uptoken':
        case '/auth/logout':
        case '/api/v1/webhook':
            ob_start();
            include $filePath;
            $content = ob_get_clean();

            $response->header("Content-Type", "application/json");

            // Parsing content to ressponse
            // \App\Core\Support\Log::debug(gettype($content), 'HttpServer.fetchDataAsynchronously.type.$content');
            // \App\Core\Support\Log::debug($content, 'HttpServer.fetchDataAsynchronously.json.$content');

            // Get first index
            $contents = explode('@|@', $content);
            // \App\Core\Support\Log::debug(gettype($contents), 'HttpServer.fetchDataAsynchronously.gettype.$contents');
            // \App\Core\Support\Log::debug($contents, 'HttpServer.fetchDataAsynchronously.$contents');
            // \App\Core\Support\Log::debug($contents[0], 'HttpServer.fetchDataAsynchronously.gettype.$contents[0]');

            if ($response->isWritable() && count($contents)) {
                $convertArr = json_decode($contents[0], true);

                // Set response headers
                // \App\Core\Support\Log::debug($convertArr, 'HttpServer.fetchDataAsynchronously.$convertArr');
                if (isset($convertArr["headers"]) && count($convertArr["headers"])) {

                    // \App\Core\Support\Log::debug($convertArr["headers"], 'HttpServer.fetchDataAsynchronously.$convertArr["headers"]');
                    foreach ($convertArr["headers"] as $header => $value) {
                        $response->header($header, $value);

                        if (is_array($header)) {
                            foreach ($header as $key => $val) {
                                $response->header($key, $val);
                            }
                        }
                    }
                }

                // Find key sessionId
                $sessionIdx = readJson('data.sessionId', $convertArr);

                // \App\Core\Support\Log::debug($sessionIdx, 'HttpServer.fetchDataAsynchronously.Routing.$sessionIdx');
                // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.Routing.$_SESSION');
                if ($sessionIdx && ! empty($sessionIdx)) {
                    $sessionExp = (env('SESSION_LIFETIME', 120) * 60);
                    $response->header('Set-Cookie', "{$sessionName}={$sessionIdx}; Max-Age={$sessionExp}; Path=/;");

                    $sessionId = $sessionIdx;
                } else {
                    $response->header('Set-Cookie', "{$sessionName}=; Max-Age={$sessionExp}; Path=/;");
                }

                // Hidden session ID on non debug mode
                if (false === config('app.debug') && isset($convertArr['data']['sessionId'])) {
                    unset($convertArr['data']['sessionId']);
                }

                $response->status($convertArr['code'] ?? 500);

                if ($returned === 'response') {
                    $response->end(json_encode($convertArr['data'], JSON_UNESCAPED_SLASHES));
                }

                if ($returned === 'content') {
                    $content = json_encode($convertArr['data'], JSON_UNESCAPED_SLASHES);
                }
            } else {

                echo "{$filePath}, URI Not rendered! \n";
            }

            break;
        default:
            // Handle any other unmatched requests with a 404 Not Found
            $content = "404 Not Found";
            $response->status(404);
            $response->header("Content-Type", "text/plain");
            $response->end($content);

            break;
    }


    // \App\Core\Support\Log::debug(session_id(), 'HttpServer.fetchDataAsynchronously.end.session_id()');
    // \App\Core\Support\Log::debug($sessionId, 'HttpServer.fetchDataAsynchronously.end.$sessionId');
    // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.end.$_SESSION');
    // // \App\Core\Support\Log::debug(\App\Core\Support\Session::all(), 'HttpServer.fetchDataAsynchronously.Session::all()');

    if (isset($_COOKIE[$sessionName]) && count($_SESSION) > 1) {
        cacheContent('set', $_COOKIE[$sessionName], 'bp_session', $_SESSION);

        // Delete old session_id()
        $getSessionId = explode("-", $_COOKIE[$sessionName]);
        if (count($getSessionId) == 2) {
            delCache($getSessionId[1], 'bp_session');            
        }
    }

    if ($returned === 'response') {
        return $response;
    }

    if ($returned === 'content') {
        return $content;
    }
}
