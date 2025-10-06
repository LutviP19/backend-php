<?php

declare(strict_types=1);

// Disabled Log Errors
ini_set('log_errors', 0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require_once __DIR__ . '/bootstrap.php';

// Set a custom session name
ini_set('session.use_strict_mode', 0);
session_name('WEBBACKENDPHPSESSID');
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


use App\Core\Http\Request;
use App\Core\Http\Router;
use App\Core\Support\App;
use App\Core\Support\Session;
use App\Core\Validation\MessageBag;

$serverip = "127.0.0.1";
$serverport = 8008;
$max_request = 10000;
$ssl_dir = realpath(__DIR__ . "/../storage/ssl");
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
    "pid_file" => realpath(__DIR__ . "/web-swoole.pid"),
    // 'document_root' => __DIR__ .'../public',
    'document_root' => realpath(__DIR__ . '/../public/'),

    // Workers
    'worker_num' => 2,
    'task_worker_num' => 5,
    //'max_request' => 10000,
    //'max_request_grace' => 0,

    // // Setup SSL files
    // 'ssl_cert_file' => $ssl_dir . '/ssl.crt',
    // 'ssl_key_file' => $ssl_dir . '/ssl.key',

    // Logging
    "log_file" => realpath(__DIR__ . "/../storage/logs/web-swoole.log"),
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
        if ($request->server['request_uri'] === '/metrics') {
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


//============= START WEB
        // \App\Core\Support\Log::debug($request, 'HttpServer.fetchDataAsynchronously.$request');
        // Init Server constants
        initializeServerConstant($request);

        $_SESSION = [];
        if (isset($_COOKIE[$sessionName])) {
            // Try get session data from Redis
            // $_SESSION = cacheContent('get', $_COOKIE[$sessionName], 'bp_web_session') ?: [];

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
            // returned of fetchDataAsynchronously
            $returned = ['response', 'content', 'tmp', 'void'];

            // Return void
            while (true) {
                // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.request.$_SESSION-A');
                $response = fetchDataAsynchronously($request, $response, 'response', $_SESSION);
                break;
            }

            if ($response->isWritable()) {
                $response->end();
            }
    
            // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.request.$_SESSION-B');
            // exit;
            throw new ExitException();
            // die(0);
        });
//============= END WEB

        

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

    // requestServer
    $requestServer = $request;

    // Get header metadata
    $headers = getallheaders();

    $uri = $request->request_uri ?? $_SERVER['REQUEST_URI'];

    if (! in_array($uri, $ignoredUri)) {
        // Try get session data from Redis
        // $_SESSION['app'] = 'web';
        // $_SESSION = array_merge($_SESSION, $sessionData, cacheContent('get', $_COOKIE[$sessionName], 'bp_web_session') ?: []);
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

    $baseDir = realpath(__DIR__ .'/../public');
    $fd = $request->fd;

    $filePath = $baseDir . $uri;
    if (is_dir($filePath)) {
        $filePath = rtrim($filePath, '/') . '/index.php';
    }

    if (file_exists($filePath)) {
        $fileInfo = pathinfo($filePath);
        $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
        $fileName = isset($fileInfo['basename']) ? $fileInfo['basename'] : '';

        switch ($extension) {
            case 'htnl':
            case 'php':
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                $response->header('Content-Type', 'text/html; charset=UTF-8');
                $response->header('Content-Encoding', 'gzip');
                $response->header('Content-Length', strlen(gzencode($content)));

                $setHeaders[] = "Content-Type, text/html; charset=UTF-8";
                $setHeaders[] = "Content-Encoding, gzip";
                $setHeaders[] = "Content-Length, ".strlen(gzencode($content));

                if ($response->isWritable() && $returned === 'response') {
                    $response->end(gzencode($content));
                } else {
                    echo "{$filePath}, URI Not rendered! \n";
                }
                break;
            case 'ico':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/vnd.microsoft.icon');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, image/vnd.microsoft.icon";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'css':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'text/css; charset=UTF-8');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, text/css; charset=UTF-8";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'js':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'application/javascript');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, application/javascript";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->end($content);
                break;
            case 'jpg':
            case 'jpeg':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/jpeg');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, image/jpeg";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'png':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/png');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, image/png";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'gif':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/gif');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, image/gif";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'svg':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/svg+xml');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, image/svg+xml";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'woff':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/woff');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, font/woff";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'woff2':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/woff2');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, font/woff2";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'ttf':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/ttf');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, font/ttf";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'otf':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/otf');
                $response->header('Content-Length', strlen($content));

                // $setHeaders[] = "Content-Type, font/otf";
                // $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            default:
                // Explicitly set an HTTP status code for preflight requests
                $response->status(204); // 204 No Content
                break;
        }

    } else {

        $filePath = $baseDir . '/index.php';
        // $lastSegment = $uri;
        $fileName = str_replace('/', '', $uri).".php";

        // Routing content
        switch ($uri) {
            case '/home':
            case '/contact':
            case '/about':
            case '/dashboard':
            case '/extra':
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                $response->header('Content-Type', 'text/html; charset=UTF-8');
                $response->header('Content-Encoding', 'gzip');
                $response->header('Content-Length', strlen(gzencode($content)));

                $setHeaders[] = "Content-Type, text/html; charset=UTF-8";
                $setHeaders[] = "Content-Encoding, gzip";
                $setHeaders[] = "Content-Length, ".strlen(gzencode($content));

                if ($response->isWritable() && $returned === 'response') {
                    $response->end(gzencode($content));
                } else {
                    echo "{$filePath}, URI Not rendered! \n";
                }
                break;
            // case '/auth/login':
            // case '/auth/uptoken':
            // case '/auth/logout':
            // case '/api/v1/webhook':
            //     ob_start();
            //     include $filePath;
            //     $content = ob_get_contents();
            //     ob_clean();

            //     $response->header("Content-Type", "application/json");
            //     $setHeaders[] = "Content-Type, application/json";

            //     // Parsing content to ressponse
            //     // \App\Core\Support\Log::debug(gettype($content), 'HttpServer.fetchDataAsynchronously.type.$content');
            //     // \App\Core\Support\Log::debug($content, 'HttpServer.fetchDataAsynchronously.json.$content');

            //     // Get first index
            //     $contents = explode('@|@', $content);
            //     // \App\Core\Support\Log::debug(gettype($contents), 'HttpServer.fetchDataAsynchronously.gettype.$contents');
            //     // \App\Core\Support\Log::debug($contents, 'HttpServer.fetchDataAsynchronously.$contents');
            //     // \App\Core\Support\Log::debug($contents[0], 'HttpServer.fetchDataAsynchronously.gettype.$contents[0]');

            //     if ($response->isWritable() && count($contents)) {
            //         $convertArr = json_decode($contents[0], true);

            //         // Set response headers
            //         // \App\Core\Support\Log::debug($convertArr, 'HttpServer.fetchDataAsynchronously.$convertArr');
            //         if (isset($convertArr["headers"]) && count($convertArr["headers"])) {

            //             // \App\Core\Support\Log::debug($convertArr["headers"], 'HttpServer.fetchDataAsynchronously.$convertArr["headers"]');
            //             foreach ($convertArr["headers"] as $header => $value) {
            //                 $response->header($header, $value);

            //                 if (is_array($header)) {
            //                     foreach ($header as $key => $val) {
            //                         $response->header($key, $val);
            //                     }
            //                 }
            //             }
            //         }

            //         // Find key sessionId
            //         $sessionIdx = readJson('data.sessionId', $convertArr);

            //         // \App\Core\Support\Log::debug($sessionIdx, 'HttpServer.fetchDataAsynchronously.Routing.$sessionIdx');
            //         // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.Routing.$_SESSION');
            //         if (! empty($sessionIdx)) {
            //             $sessionExp = (env('SESSION_LIFETIME', 120) * 60);

            //             $response->header('Set-Cookie', "{$sessionName}={$sessionIdx}; Max-Age={$sessionExp}; Path=/;");

            //             $sessionId = $sessionIdx;
            //         }

            //         // Hidden session ID on non debug mode
            //         if (false === config('app.debug') && isset($convertArr['data']['sessionId'])) {
            //             unset($convertArr['data']['sessionId']);
            //         }

            //         $response->status($convertArr['code']);
            //         $response->end(json_encode($convertArr['data'], JSON_UNESCAPED_SLASHES));
            //         // throw new ExitException();
            //     } else {
            //         echo "{$filePath}, URI Not rendered! \n";
            //     }

            //     break;
            default:
                // Handle any other unmatched requests with a 404 Not Found
                $content = "404 Not Found";
                $response->status(404);
                $response->header("Content-Type", "text/plain");
                $response->end($content);
                break;
        }
    }


    // \App\Core\Support\Log::debug(session_id(), 'HttpServer.fetchDataAsynchronously.end.session_id()');
    // \App\Core\Support\Log::debug($sessionId, 'HttpServer.fetchDataAsynchronously.end.$sessionId');
    // \App\Core\Support\Log::debug($_SESSION, 'HttpServer.fetchDataAsynchronously.end.$_SESSION');
    // // \App\Core\Support\Log::debug(\App\Core\Support\Session::all(), 'HttpServer.fetchDataAsynchronously.Session::all()');

    if (isset($_COOKIE[$sessionName]) && count($_SESSION) > 1) {
        cacheContent('set', $_COOKIE[$sessionName], 'bp_web_session', $_SESSION);

        // Delete old session_id()
        $getSessionId = explode("-", $_COOKIE[$sessionName]);
        if (count($getSessionId) == 2) {
            delCache($getSessionId[1], 'bp_web_session');
        }
    }

    // End process
    echo  "---------" . PHP_EOL;

    if ($returned === 'tmp' && $setHeaders) {
        $tmpFile = createTmp($fd, $fileName, $setHeaders, $content);
        return $tmpFile;
    }

    if ($returned === 'response') {
        return $response;
    }

    if ($returned === 'content') {
        return $content;
    }
}

function createTmp($fd, $fileName, $setHeaders, $content)
{
    $tmpPath = realpath(__DIR__ . "/../storage/framework/tmp");

    $fdPath = "{$tmpPath}/{$fd}";
    if (! file_exists($fdPath)) {
        mkdir($fdPath);
    }

    $filePath = "{$fdPath}/$fileName";
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $fileContents = "<?php " . PHP_EOL;
    foreach ($setHeaders as $header) {
        $value = explode(",", $header);
        if (is_numeric($value[1])) {
            $fileContents .= '$response->header("'.$value[0].'", '.$value[1].');' . PHP_EOL;
        } else {
            $fileContents .= '$response->header("'.$value[0].'", "'.trim($value[1]).'");' . PHP_EOL;
        }
    }

    $content = base64_encode($content);
    $fileContents .= '$content=\''.($content).'\';' . PHP_EOL;
    $fileContents .= '$response->end(base64_decode($content));' . PHP_EOL;

    // write contents to file
    file_put_contents($filePath, $fileContents);

    return $filePath;
}
