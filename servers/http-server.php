<?php

require_once __DIR__ . '/bootstrap.php';


use OpenSwoole\Core\Psr\Middleware\StackHandler;
use OpenSwoole\Core\Psr\Response;
use OpenSwoole\HTTP\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

$serverip = "127.0.0.1";
$serverport = 9501;
$max_request = 1000;

$server = new Server($serverip, $serverport);

// Server settings
$server->set([
    // Process ID
    "pid_file" => __DIR__ . "/swoole.pid",
    // 'document_root' => __DIR__,

    // Logging
    "log_file" => __DIR__ . "/../storage/logs/swoole.log",
    "log_rotation" => SWOOLE_LOG_ROTATION_DAILY,
    "log_date_format" => "%d-%m-%Y %H:%M:%S",
    "log_date_with_microseconds" => false,

    // // Compression
    // 'http_compression' => true,
    // 'http_compression_level' => 3, // 1 - 9
    // 'compression_min_length' => 20,

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


// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole http server is started at http://" . $serverip . ":" . $serverport . "\n";
});

use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;
use App\Core\Http\Request as RequestCore;
use App\Core\Http\Response as ResponseCore;
use App\Core\Http\Router;
use App\Core\Support\App;
use App\Core\Support\Session;
use App\Core\Validation\MessageBag;

class DefaultResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        return (new Response('aaaaa'))->withHeader('x-a', '1234');
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
        \App\Core\Support\Log::debug($request, 'Swoole.MiddlewareB.request');
        \App\Core\Support\Log::debug($requestBody, 'Swoole.MiddlewareB.requestBody');

        $response = $handler->handle($request);
        var_dump('MiddlewareB 2');
        \App\Core\Support\Log::debug($response, 'Swoole.MiddlewareB.response');
        \App\Core\Support\Log::debug($response->getStatusCode(), 'Swoole.MiddlewareB.responsegetStatusCode');

        return $response;
    }
}

// $stack = (new StackHandler())
//     ->add(new DefaultResponseMiddleware())
//     ->add(new MiddlewareA())
//     ->add(new MiddlewareB());
    
// $server->setHandler($stack);


$server->on('request', function (OpenSwoole\Http\Request $request, OpenSwoole\Http\Response $response) {
    // try {
        
        $response = \OpenSwoole\Http\Response::create($request->fd);
        echo "New-FD:{$request->fd}, Created!\n";

        // Simulate some asynchronous operation (e.g., fetching data from a database)
        go(function () use ($request, $response) {
            // Perform some asynchronous task (e.g., database query)
            // Replace this with your actual asynchronous operation
            $file = fetchDataAsynchronously($request, $response);
            include($file);

            // Send response once the asynchronous task is complete
            // if($response->isWritable())
                // $response->write($data);
                // Detach the response, making it independent and send the file descriptor of the client to a task
                // $response->detach();
            // else 
                // $response->end($data);
        });
    // } catch (Throwable $e) {
    //     // Handle exceptions and errors
    //     $response->status(500);
    //     $response->end('Internal Server Error');
    //     // Log the exception or send it to an error monitoring service
    //     echo "Exception: " . $e->getMessage() . "\n";
    // }
});

$server->start();


// Simulated asynchronous function to fetch data from a database
function fetchDataAsynchronously(OpenSwoole\Http\Request $request, OpenSwoole\Http\Response $response) {

    // $response = \OpenSwoole\Http\Response::create($request->fd);
    // if($response->isWritable())
        echo "FD:{$request->fd}, rendered!\n";
    // else {
    //     $response = \OpenSwoole\Http\Response::create($request->fd+1);
    //     echo "New-FD:{$request->fd}, Created!\n";
    // }
    
    initializeServerConstant($request);
    \App\Core\Support\Log::debug("=============".$request->fd, 'OpenSwoole.fetchDataAsynchronously.separator');
    // \App\Core\Support\Log::debug($_SERVER, 'OpenSwoole.fetchDataAsynchronously.$_SERVER');
    // \App\Core\Support\Log::debug($_REQUEST, 'OpenSwoole.fetchDataAsynchronously.$_REQUEST');

    $headers = getallheaders();
    // \App\Core\Support\Log::debug($headers, 'Swoole.fetchDataAsynchronously.headers');
    // \App\Core\Support\Log::debug($request, 'Swoole.fetchDataAsynchronously.request');
    // \App\Core\Support\Log::debug($request->fd, 'Swoole.fetchDataAsynchronously.$request->fd');
    
    // \App\Core\Support\Log::debug($response, 'Swoole.fetchDataAsynchronously.$response');
    // \App\Core\Support\Log::debug($response->fd, 'Swoole.fetchDataAsynchronously.$response->fd');

    // startSession();

    $baseDir = __DIR__ .'/../public';
    
    $fd = $request->fd;
    $uri = $_SERVER['REQUEST_URI'];
    \App\Core\Support\Log::debug($uri, 'Swoole.fetchDataAsynchronously.$uri');
    $filePath = $baseDir . $uri;
    if (is_dir($filePath)) {
        $filePath = rtrim($filePath, '/') . '/index.php';
    }

    \App\Core\Support\Log::debug($filePath, 'Swoole.fetchDataAsynchronously.$filePath');
    if (file_exists($filePath)) {
        $fileInfo = pathinfo($filePath);
        $extension = isset($fileInfo['extension']) ? $fileInfo['extension'] : '';
        // \App\Core\Support\Log::debug($extension, 'Swoole.fetchDataAsynchronously.extension');

        $fileName = isset($fileInfo['basename']) ? $fileInfo['basename'] : '';
        \App\Core\Support\Log::debug($fileName, 'Swoole.fetchDataAsynchronously.fileName');

        switch ($extension) {
            case 'php':
                // go(function () use ($filePath, $response) {
                    ob_start();
                    include $filePath;
                    $content = ob_get_clean();
                    // $response->header('Content-Type', 'text/html');
                    // $response->header('Content-Encoding', 'gzip');
                    // $response->header('Content-Length', strlen(gzencode($content)));

                    $setHeaders[] = "Content-Type, text/html";
                    $setHeaders[] = "Content-Encoding, gzip";
                    $setHeaders[] = "Content-Length, ".strlen(gzencode($content));

                    // $response->end(gzencode($content));
                // });
                break;
            case 'ico':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'font/otf');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/otf";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->end($content);
                break;
            case 'css':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'text/css');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, text/css";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->end($content);
                break;
            case 'js':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'application/javascript');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, application/javascript";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->end($content);
                break;
            case 'jpg':
            case 'jpeg':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'image/jpeg');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/jpeg";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'png':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'image/png');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/png";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'gif':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'image/gif');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/gif";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'svg':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'image/svg+xml');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/svg+xml";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'woff':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'font/woff');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/woff";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'woff2':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'font/woff2');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/woff2";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'ttf':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'font/ttf');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/ttf";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;
            case 'otf':
                $content = file_get_contents($filePath);
                // $response->header('Content-Type', 'font/otf');
                // $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, font/otf";
                $setHeaders[] = "Content-Length, ".strlen($content);

                // $response->write($content);
                break;                
            default:
                break;
        }
    } else {

        // $path = parse_url($url, PHP_URL_PATH);
        // $pathTrimmed = trim($path, '/');
        // $segments = explode('/', $pathTrimmed);
        // $lastSegment = end($segments);
        
        
        $filePath = $baseDir . '/index.php';
        $lastSegment = $uri;
        \App\Core\Support\Log::debug($lastSegment, 'Swoole.fetchDataAsynchronously.lastSegment');

        $fileName = str_replace('/', '', $lastSegment).".php";
        \App\Core\Support\Log::debug($fileName, 'Swoole.fetchDataAsynchronously.fileName');
    
        switch ($lastSegment) {
            case '/home':
            case '/contact':
            case '/about':
            case '/extra':
                // go(function () use ($filePath, $response) {
                    ob_start();
                    include $filePath;
                    $content = ob_get_clean();
                    // $response->header('Content-Type', 'text/html');
                    // $response->header('Content-Encoding', 'gzip');
                    // $response->header('Content-Length', strlen(gzencode($content)));

                    $setHeaders[] = "Content-Type, text/html";
                    $setHeaders[] = "Content-Encoding, gzip";
                    $setHeaders[] = "Content-Length, ".strlen(gzencode($content));

                    // if($response->isWritable())
                    //     $response->end(gzencode($content));
                    // else
                    //     echo "{$filePath}, URI Not rendered!";
                // });
                break;
            case '/webhook':
                // go(function () use ($filePath, $response) {
                    ob_start();
                    include $filePath;
                    $content = ob_get_clean();
                    // $response->header("Content-Type", "application/json");

                    $setHeaders[] = "Content-Type, application/json";

                    // \App\Core\Support\Log::debug($content, 'Swoole.server.$json');
                    // if($response->isWritable())
                    //     $response->end($content);
                    // else
                    //     echo "{$filePath}, URI Not rendered!";
                // });
                break;
            default:
                break;
        }
    }

    // session_write_close(); // Ensure session data is saved

    $tmpFile = createTmp($fd, $fileName, $setHeaders, $content);
    \App\Core\Support\Log::debug($tmpFile, 'Swoole.fetchDataAsynchronously.tmpFile');
    return $tmpFile;

    \App\Core\Support\Log::debug($response, 'Swoole.fetchDataAsynchronously.response');
    return $response;

    \App\Core\Support\Log::debug($content, 'Swoole.fetchDataAsynchronously.content');
    return $content;
}

function createTmp($fd, $fileName, $setHeaders, $content)
{
    $tmpPath = __DIR__ . "/../storage/framework/tmp";

    $fdPath = "{$tmpPath}/{$fd}";
    if(! file_exists($fdPath))
        mkdir($fdPath);

    $filePath = "{$fdPath}/$fileName";
    if(file_exists($filePath))
        unlink($filePath);

    $fileContents = "<?php " . PHP_EOL;
    foreach($setHeaders as $header) {
        $value = explode(",", $header);
        if(is_numeric($value[1]))
        $fileContents .= '$response->header("'.$value[0].'", '.$value[1].');' . PHP_EOL;
        else
        $fileContents .= '$response->header("'.$value[0].'", "'.trim($value[1]).'");' . PHP_EOL;
    }

    $content = 
    $content = base64_encode($content);
    $fileContents .= '$content=\''.($content).'\';' . PHP_EOL;
    $fileContents .= '$response->end(base64_decode($content));' . PHP_EOL;

    // write contents to file
    @file_put_contents($filePath, $fileContents);
    
    return $filePath;
}

function initializeServerConstant(OpenSwoole\Http\Request $request) {
    // Setup
    global $serverip, $serverport;

    $_SERVER = [];
    $uri = $request->server["request_uri"];
    $requestip = $request->server["remote_addr"];

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

function startSession() {
    global $redis;
    if (!isset($_COOKIE['BACKENDPHPSESSID'])) {
        $sessionId = bin2hex(random_bytes(16));
        setcookie('BACKENDPHPSESSID', $sessionId);
    } else {
        $sessionId = $_COOKIE['BACKENDPHPSESSID'];
    }

    $sessionData = $redis->get("session:$sessionId");
    if ($sessionData) {
        $_SESSION = unserialize($sessionData);
    } else {
        $_SESSION = [];
    }
}

function saveSession() {
    global $redis;
    if (isset($_COOKIE['BACKENDPHPSESSID'])) {
        $sessionId = $_COOKIE['BACKENDPHPSESSID'];
        $redis->set("session:$sessionId", serialize($_SESSION));
    }
}