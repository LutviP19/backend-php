<?php

declare(strict_types=1);


// // only level Deprecated & User Deprecated
// error_reporting(E_DEPRECATED | E_USER_DEPRECATED);

// Dev - Display All Errors
error_reporting(E_ALL);
ini_set("display_errors", 1);


use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;
use OpenSwoole\Http\Server;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\Support\App;
use App\Core\Database\DatabasePoolManager;

// 1. APPLICATION BOOTSTRAP (Only executed once when the server is turned on)
require_once __DIR__ . '/../app/Core/swoole_init.php';

$server = new Server("127.0.0.1", 8009);

$server->set([
    'worker_num'            => 2,
    'document_root'         => realpath(__DIR__ . '/../public'), // path related folders public
    'enable_static_handler' => true,                             // <--- LOAD ASSETS
    'static_handler_locations' => ['/css', '/js', '/assets', '/images', '/favicon.ico', '/backend-php-sw.js'], // <--- (Opsional) Folder/File asset
    'enable_coroutine' => true,
]);

// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport, $sessionId, $sessionName;

    echo "Swoole web server is started at http://" . $serverip . ":" . $serverport . "\n";
});

// PENTING: Inisialisasi Pool DI DALAM WorkerStart (Per Worker Process)
$server->on('WorkerStart', function ($server, int $workerId) {
    try {
        // Setiap worker akan membuat ClientPool-nya sendiri
        DatabasePoolManager::init(10);
        echo "[" . date('Y-m-d H:i:s') . "] [OK] Connection Pool initialized for Worker #{$workerId}\n";
    } catch (\Throwable $e) {
        // Tangkap fatal error agar worker tidak exit status 255
        echo "[" . date('Y-m-d H:i:s') . "] [ERROR] Failed to initialize Database Pool on Worker #{$workerId}: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
});

// Pre-load router ke memory 1x
$router = Router::load(BASEPATH . '/routes/routes.php');

$server->on('Request', function (OpenSwooleRequest $request, OpenSwooleResponse $response) use ($router) {
    global $server, $clientInfo, $ignoredUri, $sessionId, $sessionName;

    // Clear Output Buffer if any
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Init Server constants
    initializeServerConstant($request, $response);

    // Get header metadata
    $headers = getallheaders();

    $uri = $request->server['request_uri'] ?? '/';
    $publicDir = realpath(__DIR__ . '/../public');
    $filePath  = $publicDir . $uri;

    // -------------------------------------------------------------
    // 1. HANDLER ASSETS
    // -------------------------------------------------------------
    if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $mimeTypes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];

        if (isset($mimeTypes[$extension])) {
            $response->header('Content-Type', $mimeTypes[$extension]);
            // sendfile() sends files directly from the OS kernel without entering the PHP RAM buffer
            $response->sendfile($filePath);
            return; // Stop execution, do not proceed to PHP Router
        }
    }

    // -------------------------------------------------------------
    // 2.CHECK BLOCKED USER AGENT
    // -------------------------------------------------------------
    $userAgent = $request->header['user-agent'] ?? '';
    $blockedAgents = ['python-httpx', 'go-http-client'];
    foreach ($blockedAgents as $agent) {
        if (str_contains(strtolower($userAgent), strtolower($agent))) {
            $response->status(403);
            $response->end('Access denied.');
            return;
        }
    }

    // --- FASE CHECK: Block User Agent (Replacement for index.php) ---
    $userAgent = $request->header['user-agent'] ?? '';
    $blockedAgents = ['python-httpx', 'go-http-client'];

    foreach ($blockedAgents as $agent) {
        if (str_contains(strtolower($userAgent), strtolower($agent))) {
            $response->status(403);
            $response->end('Access denied.');
            return; // Use RETURN, NOT exit() /die()!
        }
    }

    // --- FASE POPULATE: Environment Synchronization ---
    $_GET     = $request->get ?? [];
    $_POST    = $request->post ?? [];
    $_REQUEST = array_merge($_GET, $_POST);
    $_COOKIE  = $request->cookie ?? [];
    $_SERVER['REQUEST_URI']    = $request->server['request_uri'] ?? '/';
    $_SERVER['REQUEST_METHOD'] = $request->server['request_method'] ?? 'GET';
    $_SERVER['HTTP_USER_AGENT'] = $userAgent;

    // --- FASE EXECUTION: Capture Router Output---
    try {
        ob_start();

        // Dispatch route
        $router->setSwooleResponse($response);
        $output = $router->dispatch(Request::uri(), Request::method());
        $bufferedOutput = ob_get_clean();
        $finalOutput = !empty($output) ? $output : $bufferedOutput;

        if ($finalOutput instanceof Response) {
            $statusCode = $finalOutput->getStatusCode() ?: 200;
            $content = $finalOutput->getContent() ?? '';

            // Copy semua Header dari Response Instance ke OpenSwoole
            foreach ($finalOutput->getHeaders() as $name => $value) {
                $response->header($name, $value);
            }

            // Jika belum ada header Content-Type dan bodynya JSON valid
            if (is_string($content) && isJson($content)) {
                $response->header('Content-Type', 'application/json; charset=UTF-8');
            }

            $response->status($statusCode);
            $response->end((string)$content);
            return;
        }

        // -------------------------------------------------------------
        // HANDLER RESPONSE JSON
        // -------------------------------------------------------------
        
        // 1. If the output is an Array or Object (Automatically encode to JSON)
        if (is_array($finalOutput) || is_object($finalOutput)) {
            // echo "1. If the output is an Array or Object (Automatically encode to JSON)";
            $response->header('Content-Type', 'application/json; charset=UTF-8');
            $response->end(json_encode($finalOutput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        if (is_string($finalOutput)) {
            
            // Clean string jika ada delimiter @|@
            $contents = explode('@|@', $finalOutput);
            $rawContent = $contents[0] ?? $finalOutput;

            // CEK APAKAH STRING MERUPAKAN JSON VALID (Sangat Kunci!)
            if (isJson($rawContent)) {
                $convertArr = json_decode($rawContent, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($convertArr)) {
                    // Cleanup key '0' jika ada bekas Set-Cookie/Buffer
                    unset($convertArr['0']);

                    // Ambil HTTP StatusCode dinamis dari JSON jika ada (misal: 422, 400, 500)
                    $rawStatus = $convertArr['statusCode'] ?? $convertArr['status'] ?? $convertArr['code'] ?? 200;
                    
                    $statusCode = (is_numeric($rawStatus) && (int)$rawStatus >= 100 && (int)$rawStatus <= 599)
                        ? (int)$rawStatus
                        : 200;

                    $response->status($statusCode);
                    $rawContent = json_encode($convertArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                // Wajib paksa Content-Type ke application/json
                $response->header('Content-Type', 'application/json; charset=UTF-8');
                $response->end($rawContent);
                return;
            }
        }

        // 3. Fallback to regular HTML Response
        if ($response->isWritable()) {
            $response->header('Content-Type', 'text/html; charset=UTF-8');
            $response->end((string)$finalOutput);
        }

    } catch (\PDOException $e) {
        // Log detail error untuk internal debugging
        if (config('app.debug')) {
            write_log('error', '[Database Error] ' . $e->getMessage(), '/servers/api-server');
        }

        // Set HTTP Status 503
        if ($response->isWritable()) {
            $statusCode = 503;
            $errorMessage = 'Layanan database sedang tidak merespons. Silakan coba lagi.';

            $response->status($statusCode);
            
            if(is_json_request($request)) {
                $json = [
                        'status' => false,
                        'statusCode' => $statusCode,
                        'message' => "Exception",
                        'errors' => [$errorMessage],
                    ];
                $response->header('Content-Type', 'application/json; charset=UTF-8');
                $response->end(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $response->status($statusCode);

                // Kirim HX-Trigger khusus untuk toast HTMX Alpine.js Anda
                $response->header('HX-Trigger', json_encode([
                    'dbError' => $errorMessage
                ]));

                $response->end('Database Connection Error');
            }
        }

    } catch (\App\Core\Exceptions\SwooleExitException $e) {

        // Ambil semua isi buffer dari ob_start()
        $bufferedOutput = '';
        while (ob_get_level() > 0) {
            $bufferedOutput = ob_get_clean() . $bufferedOutput;
        }

        $bufferedOutput = trim($bufferedOutput);

        // Cek jika buffer berisi JSON - Validator Errors
        if (!empty($bufferedOutput) && isJson($bufferedOutput)) {
            $jsonArr = json_decode($bufferedOutput, true);

            // Tentukan status code dari Exception atau dari payload JSON (misal 422)
            $statusCode = $e->getCode();
            if (!$statusCode || $statusCode === 200) {
                $statusCode = $jsonArr['statusCode'] ?? $jsonArr['status'] ?? $jsonArr['code'] ?? 200;
            }

            if (!is_numeric($statusCode) || (int)$statusCode < 100 || (int)$statusCode > 599) {
                $statusCode = 422; // Fallback jika validasi error
            }

            // PERINTAH KRUSIAL OPENSWOOLE:
            // Wajib set status & header SEBELUM memanggil end()
            $response->status((int)$statusCode);
            $response->header('Content-Type', 'application/json; charset=UTF-8');
            $response->end($bufferedOutput);
            return;
        }

        // Default non-JSON exit
        if ($response->isWritable()) {
            $code = $e->getCode() ?: 200;
            $response->status((int)$code);
            $response->end($bufferedOutput);
        }
        return;

    } catch (\Throwable $e) {
        // Catch Exception WITHOUT shutting down the server
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if(config('app.debug')) {
            // Print error to Swoole terminal for debugging
            echo "=== FATAL ERROR AT " . Request::uri() . " ===\n";
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            echo "=========================================\n";

            // Write log manual
            if (function_exists('write_log')) {
                write_log("error", $e->getMessage() . "\n" . $e->getTraceAsString(), "Swoole.Request");
            }
        }

        if ($response->isWritable()) {
            $statusCode = 500;
            $errorMessage = config('app.debug') 
                ? $e->getMessage() . " in " . str_replace(BASE_PATH, '', $e->getFile()) . ":" . $e->getLine()
                : 'Internal Server Error';

            $response->status($statusCode);

            if(is_json_request($request)) {
                $json = [
                        'status' => false,
                        'statusCode' => $statusCode,
                        'message' => "Exception",
                        'errors' => [$errorMessage],
                    ];
                $response->header('Content-Type', 'application/json; charset=UTF-8');
                $response->end(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {                
                $response->end($errorMessage);
            }
        }
    }
});

$server->start();
