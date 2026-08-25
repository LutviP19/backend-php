<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
// error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';

// Load All Middleware
$apiMiddlewarePath = BASEPATH . '/servers/middleware/api/*.php';
foreach (glob($apiMiddlewarePath) as $filePath) {
    if (is_file($filePath)) {
        require_once $filePath;
    }
}

$serverip = "127.0.0.1";
// $serverport = 8080;
$serverport = 8008;
$sessionName = '';
$sessionId = '';

// Set a custom session name
ini_set('session.use_strict_mode', 0);
session_name('APIBACKENDPHPSESSID');
ini_set('session.use_strict_mode', 1);

// use App\Core\Support\Config;
// use FastRoute\RouteCollector;
use Servers\Middleware\Api\{MiddlewareSetup, MiddlewareA, MiddlewareB};
use OpenSwoole\Core\Psr\Middleware\StackHandler;
use OpenSwoole\Core\Psr\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\HTTP\Server;
use OpenSwoole\Coroutine;
use OpenSwoole\Runtime;
use App\Core\Database\DatabasePoolManager;
use App\Core\Support\CacheSwoole;

// use OpenSwoole\Http\Request as OpenSwooleRequest;
// use OpenSwoole\Http\Response as OpenSwooleResponse;

// Otomatis meng-hook seluruh I/O (File, PDO/TCP, cURL, Redis, dll)
Runtime::enableCoroutine(true);

// $table = new Swoole\Table(1024);
// $table->column('name', Swoole\Table::TYPE_STRING, 64);
// $table->column('id', Swoole\Table::TYPE_INT, 4);       //1,2,4,8
// $table->column('num', Swoole\Table::TYPE_FLOAT);
// $table->create();

// $table1 = new Swoole\Table(1024);
// $table1->column('name', Swoole\Table::TYPE_STRING, 64);
// $table1->column('id', Swoole\Table::TYPE_INT, 4);       //1,2,4,8
// $table1->column('num', Swoole\Table::TYPE_FLOAT);
// $table1->create();

$serverip = "0.0.0.0";
$serverport = 8080;
// $serverport = 8009;

$server = new Server($serverip, $serverport);
// Server settings
$server->set([
    // Process ID
    "pid_file" => __DIR__ . "/apisrv-swoole.pid",
    // 'document_root' => __DIR__ .'../public',
    'document_root' => __DIR__ . '/../public/',

    // Worker
    'worker_num' => 2,
    'task_worker_num' => 5,
    // 'max_request' => 10000,
    //'max_request_grace' => 0,

    // --- KOREKSI PENTING UNTUK MENCEGAH DEADLOCK ---
    'max_wait_time' => 10,    // Toleransi waktu (detik) saat worker reload/stop sebelum force kill coroutine
    'max_request' => config('app.env') !== 'production' ? 3000 : 300, // Restart worker otomatis setelah 3000 request untuk cegah memory leak

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

    // Coroutine
    'enable_coroutine' => true,
    'task_enable_coroutine' => true,

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

// Tambahkan sebuah Process khusus yang berjalan di background
$pingProcess = new \OpenSwoole\Process(function (\OpenSwoole\Process $process) {
    // echo "Background Ping Process Started...\n";

    // Interval longgar: 30 detik (30.000 ms) - Timer berjalan independen
    \OpenSwoole\Timer::tick(30000, function () {
        \OpenSwoole\Coroutine::create(function () {
            try {
                // Beri jeda acak 0-3000 ms agar antar worker tidak hit DB bersamaan
                \OpenSwoole\Coroutine::sleep(mt_rand(0, 3000) / 1000);

                // Hanya lakukan ping jika pool benar-benar IDLE lebih dari 30 detik
                DatabasePoolManager::ping(null, 30);
                // echo "[" . date('Y-m-d H:i:s') . "] [OK] Ping Database Berhasil\n";
            } catch (\Throwable $e) {
                // Safe catch agar timer tidak terhenti
            }
        });
    });
});
// Sisipkan proses background ke dalam server
$server->addProcess($pingProcess);


// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole api server is started at http://" . $serverip . ":" . $serverport . "\n";
});

$server->on('Task', function (Swoole\Server $server, $task_id, $reactorId, $data) {
    echo "Task Worker Process received data";
    echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . strlen((string) $data) . "." . PHP_EOL;
    $server->finish($data);
});

// Routing API here
$dispatcher = include __DIR__ .'/../routes/api-server.php';

class RouteMiddleware implements MiddlewareInterface
{
    public function __construct(private $dispatcher)
    {

    }

    //\OpenSwoole\Core\Psr\ServerRequest ServerRequestInterface
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Buka Output Buffering untuk menangkap echo dari dd() / var_dump()
        ob_start();

        try {
            // Init $_SERVER attributes
            $serverParams = $request->getServerParams() ?? [];
            $req = array_merge($serverParams, $request->getHeaders() ?? []);
            // $response = $handler->handle($request);
            initializeServerConstant($req);
            // \App\Core\Support\Log::debug($_SERVER, 'ApiServer.RouteMiddleware.process.$_SERVER');

            // Get metadata headers
            // 1. Retrieve Session Token from Header or Cookie
            $sessionHeader = $request->getHeaderLine('sessionKeyApi');
            $cookies       = $request->getCookieParams();
            $sessionCookie = $cookies[session_name()] ?? $cookies['sessionKeyApi'] ?? null;
            $rawToken = !empty($sessionHeader) ? $sessionHeader : $sessionCookie;

            if (!empty($rawToken)) {
                // Dekripsi token untuk mendapatkan prefix key cache
                $prefixKey = strlen($rawToken) > 100 ? decryptData($rawToken) : $rawToken;

                if (!empty($prefixKey)) {
                    $cachedSession = (new \App\Core\Support\CacheSwoole())->get($prefixKey);

                    if (is_array($cachedSession) && !empty($cachedSession)) {

                        $_SESSION = $cachedSession;
                        if (class_exists('\OpenSwoole\Coroutine') && \OpenSwoole\Coroutine::getCid() > 0) {
                            \OpenSwoole\Coroutine::getContext()['session'] = $cachedSession;
                        }

                        if (class_exists('\App\Core\Support\Session')) {
                            foreach ($cachedSession as $sKey => $sVal) {
                                \App\Core\Support\Session::set($sKey, $sVal);
                            }
                        }
                    }
                }
            }

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
                        ob_end_clean();
                        return new Response('Not found', 404, '', ['Content-Type' => 'text/plain']);
                    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                        ob_end_clean();
                        return new Response('Method not allowed', 405, '', ['Content-Type' => 'text/plain']);
                    case \FastRoute\Dispatcher::FOUND:
                        foreach ($routeInfo[2] as $key => $value) {
                            $request = $request->withAttribute($key, $value);
                        }
                        return $routeInfo[1]($request);
                }

            } else {
                ob_end_clean();
                return new Response('Not Acceptable content type.', 406, '', ['Content-Type' => 'text/plain']);
            }
        } catch (\OpenSwoole\ExitException | \App\Core\Exceptions\SwooleExitException $e) {
            // Handler dd() || customExit()
            $bufferedOutput = '';
            if (ob_get_level() > 0) {
                $bufferedOutput = ob_get_clean();
            }

            $status = 200;
            if (method_exists($e, 'getStatus')) {
                $status = $e->getStatus();
            } elseif (property_exists($e, 'status')) {
                $status = $e->status;
            }

            $contentType = str_contains($bufferedOutput, '<pre>')
                ? 'text/html; charset=utf-8'
                : 'application/json; charset=utf-8';

            return (new Response($bufferedOutput))->withHeaders(["Content-Type" => $contentType])->withStatus(200);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $statusCode = 500;
            $errorMessage = config('app.debug')
                ? $e->getMessage() . " in " . str_replace(BASE_PATH, '', $e->getFile()) . ":" . $e->getLine()
                : 'Internal Server Error';
            $json = [
                        'status' => false,
                        'statusCode' => $statusCode,
                        'message' => $errorMessage,
                    ];

            return (new Response(\json_encode($json)))->withHeaders(["Content-Type" => "application/json"])->withStatus($statusCode);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Menggunakan OpenSwoole defer agar $_SESSION pasti dibersihkan setelah Response dikirim
            defer(function () {
                $_SESSION = [];
            });
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

// WORKER PROCESS: Dijalankan 4 kali (sekali untuk setiap worker)
$server->on('WorkerStart', function (Server $server, int $workerId) {
    // SANGAT AMAN UNTUK AUTO-REFRESH:
    // File di bawah ini akan dimuat ulang setiap kali worker di-reload
    if (config('app.env') === 'local') {
        require_once __DIR__ . '/bootstrap.php';
        require_once __DIR__ . '/../routes/api-server.php';
    }

    echo "Worker #{$workerId} is ready.\n";

    // Jalankan Inisialisasi Pool DI DALAM Coroutine Context
    Coroutine::create(function () use ($workerId) {
        try {
            // DatabasePoolManager::init();

            $defaultDb = config('default_db') ?? 'pgsql';
            // 1. Set default connection secara eksplisit (opsional tapi bagus untuk kepastian)
            DatabasePoolManager::setDefaultConnection($defaultDb);
            // 2. Inisialisasi Pool khusus untuk Worker ini
            DatabasePoolManager::init($defaultDb);

            // Inisialisasi pool CacheSwoole
            CacheSwoole::initPool();

            echo "[" . date('Y-m-d H:i:s') . "] [OK] Connection Pool initialized for Worker #{$workerId}\n";
        } catch (\Throwable $e) {
            echo "[" . date('Y-m-d H:i:s') . "] [ERROR] Failed to initialize Database Pool on Worker #{$workerId}: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
        }
    });
});

// PENTING: Bersihkan Pool ketika Worker Berhenti (Worker Stop/Reload)
$server->on('WorkerStop', function ($server, int $workerId) {
    if (class_exists('DatabasePoolManager') && method_exists('DatabasePoolManager', 'close')) {
        DatabasePoolManager::close();
    }
    echo "[" . date('Y-m-d H:i:s') . "] [INFO] Worker #{$workerId} stopped and pool cleaned up.\n";
});

$server->start();
