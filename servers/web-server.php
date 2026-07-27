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

// 1. APPLICATION BOOTSTRAP (Only executed once when the server is turned on)
require_once __DIR__ . '/../app/Core/swoole_init.php';

$server = new Server("127.0.0.1", 8009);

$server->set([
    'worker_num'            => 2,
    'document_root'         => realpath(__DIR__ . '/../public'), // Path mutlak ke folder public
    'enable_static_handler' => true,                             // <--- TAMBAHKAN INI
    'static_handler_locations' => ['/css', '/js', '/assets', '/images', '/favicon.ico'], // <--- (Opsional) Folder asset kamu
]);

// Pre-load router ke memory 1x
$router = Router::load(BASEPATH . '/routes/routes.php');

$server->on('request', function (OpenSwooleRequest $request, OpenSwooleResponse $response) use ($router) {

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
    $_COOKIE  = $request->cookie ?? [];
    $_SERVER['REQUEST_URI']    = $request->server['request_uri'] ?? '/';
    $_SERVER['REQUEST_METHOD'] = $request->server['request_method'] ?? 'GET';
    $_SERVER['HTTP_USER_AGENT'] = $userAgent;

    // --- FASE EXECUTION: Capture Router Output---
    try {
        ob_start();

        // Dispatch route
        $output = $router->dispatch(Request::uri(), Request::method());
        $bufferedOutput = ob_get_clean();
        $finalOutput = !empty($output) ? $output : $bufferedOutput;

        // If the controller returns your custom Response instance
        if ($output instanceof Response) {
            // Apply HTTP Status Code (IMPORTANT! This is what changes 200 to 302)
            $response->status($output->getStatusCode());

            // Transfer header dari custom Response ke OpenSwoole Response
            foreach ($output->getHeaders() as $name => $value) {
                $response->header($name, $value);
            }
            $response->status($output->getStatusCode() ?: 302);
            $response->end($output->getContent() ?? '');
            return;
        }

        // -------------------------------------------------------------
        // HANDLER RESPONSE JSON
        // -------------------------------------------------------------
        $isJsonRequest = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

        // 1. If the output is an Array or Object (Automatically encode to JSON)
        if (is_array($finalOutput) || is_object($finalOutput)) {
            // echo "1. If the output is an Array or Object (Automatically encode to JSON)";
            $response->header('Content-Type', 'application/json; charset=UTF-8');
            $response->end(json_encode($finalOutput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

        // 2. If the output is a valid JSON string or the request requests JSON
        if (is_string($finalOutput) && (is_json($finalOutput) || $isJsonRequest)) {
            // echo "2. If the output is a valid JSON string or the request requests JSON";

            $contents = explode('@|@', $finalOutput);
            if ($response->isWritable() && !empty($contents[0])) {
                // 1. Take the first part (Index 0)
                $content = $contents[0];
                $convertArr = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // 2. If there is a key "0" (containing Set-Cookie) included, delete it to clean the JSON
                    unset($convertArr['0']);

                    // Set statusCode
                    // Retrieve statusCode safely using the Null Coalescing Operator (??)
                    $rawStatus = $convertArr['data']['statusCode'] ?? $convertArr['code'] ?? $convertArr['statusCode'] ?? 200;

                    // Make sure the status code value is a valid integer type (between 100 and 599)
                    $statusCode = (is_numeric($rawStatus) && (int)$rawStatus >= 100 && (int)$rawStatus <= 599)
                        ? (int)$rawStatus
                        : 200;

                    $response->status($statusCode);

                    // 3. Encode it back as final output
                    $finalOutput = json_encode($convertArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            $response->header('Content-Type', 'application/json; charset=UTF-8');
            $response->end($finalOutput);
            return;
        }

        // 3. Fallback to regular HTML Response
        $response->header('Content-Type', 'text/html; charset=UTF-8');
        $response->end((string)$finalOutput);

    } catch (\Throwable $e) {
        // Catch Exception WITHOUT shutting down the server
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Print error to Swoole terminal for debugging
        echo "=== FATAL ERROR AT " . Request::uri() . " ===\n";
        echo $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        echo "=========================================\n";

        // Write log manual
        if (function_exists('write_log')) {
            write_log("error", $e->getMessage() . "\n" . $e->getTraceAsString(), "Swoole.Request");
        }

        $response->status(500);
        $response->end("500 Internal Server Error");
    }
});

$server->start();
