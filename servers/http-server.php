<?php

declare(strict_types=1);

// Disabled Log Errors
ini_set('log_errors', 0);
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/bootstrap.php';


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

$server = new Server($serverip, $serverport);

// https
// $server = new Server($serverip, $serverport, Server::SIMPLE_MODE, \OpenSwoole\Constant::SOCK_TCP | \OpenSwoole\Constant::SSL);

$redis = new \Predis\Client([
    'host' => config('redis.cache.host'),
    'port' => config('redis.cache.port'),
    'database' => config('redis.cache.database')
]);

// Server settings
$server->set([
    // Process ID
    "pid_file" => __DIR__ . "/swoole.pid",
    // 'document_root' => __DIR__ .'../public',
    'document_root' => realpath(__DIR__ . '/../public/'),

    // // Setup SSL files
    // 'ssl_cert_file' => $ssl_dir . '/ssl.crt',
    // 'ssl_key_file' => $ssl_dir . '/ssl.key',

    // Logging
    "log_file" => __DIR__ . "/../storage/logs/swoole.log",
    "log_rotation" => SWOOLE_LOG_ROTATION_DAILY,
    "log_date_format" => "%d-%m-%Y %H:%M:%S",
    "log_date_with_microseconds" => false,

    // Compression
    'http_compression' => true,
    'http_compression_level' => 3, // 1 - 9
    'compression_min_length' => 20,

    // // Coroutine
    // 'enable_coroutine' => true,
    // 'max_coroutine' => 3000,
    // 'send_yield' => false,

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


// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole http server is started at http://" . $serverip . ":" . $serverport . "\n";
});

class CustomServerRequest extends \OpenSwoole\Core\Psr\ServerRequest
{
}

class ExitException extends Exception
{
}


class DefaultResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new PsrResponse('aaaa'))->withHeader('x-a', '1234');
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
        var_dump('MiddlewareB 1');
        // \App\Core\Support\Log::debug($request, 'Swoole.MiddlewareB.request');
        // \App\Core\Support\Log::debug($requestBody, 'Swoole.MiddlewareB.requestBody');

        $response = $handler->handle($request);
        var_dump('MiddlewareB 2');
        // \App\Core\Support\Log::debug($response, 'Swoole.MiddlewareB.response');
        // \App\Core\Support\Log::debug($response->getStatusCode(), 'Swoole.MiddlewareB.responsegetStatusCode');

        return $response;
    }
}


$server->on('request', function (OpenSwooleRequest $request, OpenSwooleResponse $response) use ($server) {
    try {
        // returned of fetchDataAsynchronously
        $returned = ['response', 'content', 'tmp', 'void'];

        // print_r($server->stats());
        // \App\Core\Support\Log::debug($server->stats() 'Swoole.request.$server->stats()');

        // Simulate some asynchronous operation (e.g., fetching data from a database)
        go(function () use ($request, $response, $returned) {
            try {
                // Return void
                while (true) {
                    // ob_flush();
                    fetchDataAsynchronously($request, $response, $returned[3]);
                    // ob_end_flush();
                    break;
                }

                throw new ExitException();
            } catch (ExitException $e) {
                // ... handle gracefully ...
                exit(1);
            }
        });
    } catch (Throwable $e) {
        // Handle exceptions and errors
        $response->status(500);
        $response->end('Internal Server Error');
        // Log the exception or send it to an error monitoring service
        echo "Exception: " . $e->getMessage() . "\n";
    }
});

// // Use Middleware
// $stack = (new StackHandler())
//     ->add(new DefaultResponseMiddleware())
//     ->add(new MiddlewareA())
//     ->add(new MiddlewareB());

// $server->setHandler($stack);

$server->start();


// Simulated asynchronous function to fetch data from a database
function fetchDataAsynchronously(OpenSwooleRequest $request, OpenSwooleResponse $response, $returned = 'response')
{

    // Check response status
    if ($response->isWritable()) {
        echo "FD:{$request->fd}, rendered!\n";
    } else {
        $response = $response::create($request->fd);
        echo "New-FD:{$request->fd}, Created!\n";
    }

    // Init Server constants
    initializeServerConstant($request);

    // Get header metadata
    $headers = getallheaders();

    $baseDir = __DIR__ .'/../public';

    $fd = $request->fd;
    $uri = $_SERVER['REQUEST_URI'];

    $filePath = $baseDir . $uri;
    if (is_dir($filePath)) {
        $filePath = rtrim($filePath, '/') . '/index.php';
    }

    if (file_exists($filePath)) {
        $fileInfo = pathinfo($filePath);
        $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
        $fileName = isset($fileInfo['basename']) ? $fileInfo['basename'] : '';

        switch ($extension) {
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

                $response->end(gzencode($content));
                break;
            case 'ico':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/vnd.microsoft.icon');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/vnd.microsoft.icon";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->end($content);
                break;
            case 'css':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'text/css; charset=UTF-8');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, text/css; charset=UTF-8";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->end($content);
                break;
            case 'js':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'application/javascript');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, application/javascript";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->end($content);
                break;
            case 'jpg':
            case 'jpeg':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/jpeg');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/jpeg";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'png':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/png');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/png";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'gif':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/gif');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/gif";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'svg':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/svg+xml');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/svg+xml";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'woff':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/woff');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/woff";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'woff2':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/woff2');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/woff2";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'ttf':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/ttf');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/ttf";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'otf':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'font/otf');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/otf";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            default:
                break;
        }
    } else {

        $filePath = $baseDir . '/index.php';
        $lastSegment = $uri;

        $fileName = str_replace('/', '', $lastSegment).".php";

        // Routing content
        switch ($lastSegment) {
            case '/home':
            case '/contact':
            case '/about':
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

                if ($response->isWritable()) {
                    $response->end(gzencode($content));
                } else {
                    echo "{$filePath}, URI Not rendered!";
                }
                break;
            case '/webhook':
                ob_start();
                include $filePath;
                $content = ob_get_clean();
                $response->header("Content-Type", "application/json");

                $setHeaders[] = "Content-Type, application/json";

                if ($response->isWritable()) {
                    $response->end($content);
                } else {
                    echo "{$filePath}, URI Not rendered!";
                }
                break;
            default:
                break;
        }
    }

    startSession($response);
    saveSession();
    session_write_close(); // Ensure session data is saved

    if ($returned === 'tmp') {
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
    $tmpPath = __DIR__ . "/../storage/framework/tmp";

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
    $fileContents .= '$response->write(base64_decode($content));' . PHP_EOL;

    // write contents to file
    file_put_contents($filePath, $fileContents);

    return $filePath;
}

function initializeServerConstant($request) //OpenSwoole\Http\Request
{// Setup
    global $serverip, $serverport;

    $_SERVER = [];
    $uri = $request->server["request_uri"] ?? "/";
    $requestip = $request->server["remote_addr"] ?? clientIP();

    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    $_SERVER['SERVER_NAME'] = $serverip;
    $_SERVER['SERVER_PORT'] = $serverport;
    $_SERVER['SERVER_SOFTWARE'] = "Backend PHP";
    $_SERVER['SERVER_PROTOCOL'] = isset($request->server['server_protocol']) ? $request->server['server_protocol'] : null;
    $_SERVER['HTTP_HOST'] = $serverip.":".$serverport;
    $_SERVER['HTTP_ACCEPT'] = isset($request->header['accept']) ? $request->header['accept'] : "*";
    $_SERVER['HTTP_USER_AGENT'] = $request->header['user-agent'] ?? null;
    $_SERVER['HTTP_ACCEPT_ENCODING'] = $request->header['accept-encoding'] ?? null;
    $_SERVER['QUERY_STRING'] = isset($request->server['query_string']) ? $request->server['query_string'] : null;
    $_SERVER['PHP_SELF'] = isset($request->server['php_self']) ? $request->server['php_self'] : null;
    $_SERVER['SCRIPT_NAME'] = isset($request->server['script_name']) ? $request->server['script_name'] : null;
    $_SERVER['SCRIPT_FILENAME'] = isset($request->server['script_filename']) ? $request->server['script_filename'] : null;
    $_SERVER['REMOTE_ADDR'] = $requestip;
    $_SERVER['REMOTE_PORT'] = isset($request->server['remote_port']) ? $request->server['remote_port'] : null;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REQUEST_METHOD'] = $request->server["request_method"];
    $_SERVER['REQUEST_TIME'] = isset($request->server['request_time']) ? $request->server['request_time'] : null;
    $_SERVER['REQUEST_TIME_FLOAT'] = isset($request->server['request_time_float']) ? $request->server['request_time_float'] : null;

    foreach ($request->server as $key => $value) {
        $_SERVER[strtoupper($key)] = $value;
    }

    $_REQUEST = [];
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    $_FILES = $request->files ?? [];
    $_COOKIE = $request->cookie ?? [];

    $_REQUEST = array_merge($_GET, $_POST);

    foreach ($request->header as $key => $value) {
        $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
    }

    if (isset($request->header['host'])) {
        $_SERVER['HTTP_HOST'] = $request->header['host'];
    }

    if (isset($request->cookie)) {
        foreach ($request->cookie as $key => $value) {
            $_COOKIE[$key] = $value;
        }
    }
}

function startSession($response)
{
    global $redis;

    $sessionExp = 60 * 60 * 24 * 2;


    if (!isset($_COOKIE['BACKENDPHPSESSID']) || is_null($_COOKIE['BACKENDPHPSESSID'])) {
        $sessionId = session_id();
        $response->header('Set-Cookie', "BACKENDPHPSESSID={$sessionId}; Max-Age={$sessionExp}; Path=/; SameSite=Lax;");

        $redis->set("session:$sessionId", serialize($_SESSION));
        $_COOKIE['BACKENDPHPSESSID'] = $sessionId;
    } else {
        $sessionId = $_COOKIE['BACKENDPHPSESSID'];
    }

    $sessionData = $redis->get("session:$sessionId");

    // \App\Core\Support\Log::debug("Current Session ID: " . $sessionId, 'startSession.sessionId');
    // \App\Core\Support\Log::debug($sessionData, 'startSession.sessionData');
    // \App\Core\Support\Log::debug($_COOKIE, 'startSession.$_COOKIE');

    if ($sessionData) {
        $_SESSION = unserialize($sessionData);
    } else {
        $_SESSION = [];
    }
}

function saveSession()
{
    global $redis;

    if (!is_null($_COOKIE['BACKENDPHPSESSID'])) {
        $sessionId = $_COOKIE['BACKENDPHPSESSID'];
        $redis->set("session:$sessionId", serialize($_SESSION));
    }
}
