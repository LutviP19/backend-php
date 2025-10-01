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
$requestServer = new OpenSwooleRequest();

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
    "pid_file" => __DIR__ . "/swoole.pid",
    // 'document_root' => __DIR__ .'../public',
    'document_root' => realpath(__DIR__ . '/../public/'),

    // Workers
    'worker_num' => 4,
    'task_worker_num' => 10,
    //'max_request' => 10000,
    //'max_request_grace' => 0,

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

$process = new OpenSwoole\Process(function ($process) use ($server) {
    while (true) {
        $msg = $process->read();
        var_dump($msg);

        foreach ($server->connections as $conn) {
            $server->send($conn, $msg);
        }
    }
});

$server->addProcess($process);


class CustomServerRequest extends \OpenSwoole\Core\Psr\ServerRequest
{
}

class ExitException extends Exception
{
}

// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole http server is started at http://" . $serverip . ":" . $serverport . "\n";
});

$server->on("Connect", function (Server $server, int $fd) {
    $clientInfo = $server->getClientInfo($fd);

    if ($clientInfo) {
        echo "Client connected: " . $clientInfo['remote_ip'] . "\n";
        echo "Http sessStatus: " . session_status() . "\n";
    }
});

$server->on('request', function (OpenSwooleRequest $request, OpenSwooleResponse $response) use ($server) {
    try {
        // Log the incoming request method
        echo "Received a '{$request->server['request_method']}:'{$request->server['request_uri']} request\n";

        // Handle an OPTIONS request with an empty response
        if ($request->server['request_method'] === 'OPTIONS') {
            // Explicitly set an HTTP status code for preflight requests
            $response->status(204); // 204 No Content
            // End the response without a body
            $response->end();
            return;
        }

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
                    $response = fetchDataAsynchronously($request, $response, 'response');
                    // ob_end_flush();
                    break;
                }

                if ($response->isWritable())
                $response->end();
            
                throw new ExitException();
            } catch (ExitException $e) {
                // ... handle gracefully ...
                die(0);
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

$server->on('Task', function (Swoole\Server $server, $task_id, $reactorId, $data) {
    echo "Task Worker Process received data";
    echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . strlen($data) . "." . PHP_EOL;
    $server->finish($data);
});

$server->start();

// Simulated asynchronous function to fetch data from a database
function fetchDataAsynchronously(OpenSwooleRequest $request, OpenSwooleResponse $response, $returned = 'response')
{
    global $server, $requestServer;

    $requestServer = $request;
    
    // Check response Writable status
    if ($response->isWritable()) {
        echo "FD:{$request->fd}, Writable!\n";
    } else {
        $response = $response::create($request->fd);
        echo "New-FD:{$request->fd}, Created!\n";
    }

    // Init Server constants
    initializeServerConstant($request);
    // \App\Core\Support\Log::debug($request, 'HttpServer.fetchDataAsynchronously.$request');
    // \App\Core\Support\Log::debug($_SERVER, 'HttpServer.fetchDataAsynchronously.$_SERVER');

    // Get header metadata
    $headers = getallheaders();

    if (isset($request->header['user-agent'])) {
        $userAgent = $request->header['user-agent'];
        echo  "Client User-Agent: " . $userAgent . PHP_EOL . "---------" . PHP_EOL;
    } else {
        echo "Client User-Agent header not found." . PHP_EOL . "---------" . PHP_EOL;
    }


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

                $response->write(gzencode($content));
                break;
            case 'ico':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'image/vnd.microsoft.icon');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, image/vnd.microsoft.icon";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'css':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'text/css; charset=UTF-8');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, text/css; charset=UTF-8";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
                break;
            case 'js':
                $content = file_get_contents($filePath);
                $response->header('Content-Type', 'application/javascript');
                $response->header('Content-Length', strlen($content));

                $setHeaders[] = "Content-Type, application/javascript";
                $setHeaders[] = "Content-Length, ".strlen($content);

                $response->write($content);
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
                // Explicitly set an HTTP status code for preflight requests
                $response->status(204); // 204 No Content
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
                    $response->write(gzencode($content));
                } else {
                    echo "{$filePath}, URI Not rendered!";
                }
                break;
            case '/auth/login':
            case '/auth/uptoken':
            case '/auth/logout':
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
            case '/metrics':
                $response->header("Content-Type", "text/plain");

                $localIps = ['::1', '0.0.0.0', '127.0.0.1', 'localhost', 'host.docker.internal'];
                if(in_array(clientIP(), $localIps))
                    $content = $server->stats(\OPENSWOOLE_STATS_OPENMETRICS);
                else {
                    $content = "404 Not Found";
                    $response->status(404);
                }
                    
                $response->write($server->stats(\OPENSWOOLE_STATS_OPENMETRICS));
                break;
            default:
                // Handle any other unmatched requests with a 404 Not Found
                $content = "404 Not Found";
                $response->status(404);
                $response->header("Content-Type", "text/plain");
                $response->write($content);
                break;
        }
    }

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
