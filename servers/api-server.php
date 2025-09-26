<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/bootstrap.php';


use OpenSwoole\HTTP\Server;
use OpenSwoole\Http\Request as OpenSwooleRequest;
use OpenSwoole\Http\Response as OpenSwooleResponse;

$server = new Server($serverip, $serverport);
$server->set([
    // Worker
    'worker_num' => 4,      // The number of worker processes to start
    'task_worker_num' => 4,  // The amount of task workers to start
    'backlog' => 128,       // TCP backlog connection number

    // Disable coroutines to safely access $_SESSION
    'enable_coroutine' => false,
]);

// Triggered when new worker processes starts
$server->on("WorkerStart", function ($server, $workerId) {
    // ...
});

// Start Server
$server->on("Start", function (Server $server) {
    global $serverip, $serverport;

    echo "Swoole http server is started at http://" . $serverip . ":" . $serverport . "\n";
});

$server->on("Connect", function (Server $server, int $fd) {
    global $sessID;

    // $clientInfo = $server->getClientInfo($fd);
    // // $sessID = custom_session_regenerate_id();

    // if(session_status() == PHP_SESSION_ACTIVE)
    //     session_destroy();

    if ($clientInfo) {
        echo "Client connected: " . $clientInfo['remote_ip'] . "\n";
        echo "Client FD: " . $fd . "\n";
        echo "Http sessStatus: " . session_status() . "\n";
        echo "Http sessID: " . $sessID . "\n";
    }
});

$server->on('request', new SessionDecorator(function (OpenSwooleRequest $request, OpenSwooleResponse $response) {
    global $sessID;

    $sessID = session_id();
    $sessionName = session_name();

    $session_id = isset($request->cookie[$sessionName]) ? $request->cookie[$sessionName] : $sessID;
    if (isset($request->cookie[$sessionName]) && $session_id !== $sessID) {
        // $response->status(204); // 204 No Content
        // $response->end('204 No Content');
        // return;
        $cookie = session_get_cookie_params();
        $response->cookie(
            $sessionName,
            $sessID,
            $cookie['lifetime'] ? time() + $cookie['lifetime'] : 0,
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
    }

    // Init Server constants
    initializeServerConstant($request);

    // Get header metadata
    $headers = getallheaders();

    $userAgent = $request->header['user-agent'];
    echo "Client sessID: " . session_id() . "\n";
    echo  "Client User-Agent: " . $userAgent . PHP_EOL . "---------" . PHP_EOL;

    $_SESSION['data'] = rand();
    $_SESSION['fd'] = $request->fd;
    $_SESSION['id'] = generateUlid();

    $response->write($request->cookie[$sessionName] ?? $sessionName.'-'.$sessID.PHP_EOL);
    $response->write(json_encode($_COOKIE).PHP_EOL);
    $response->write(json_encode($_SESSION));
    $response->end();

    // // Handle API logic based on $request->server['request_uri'] and $request->getMethod()
    // if ($request->server['request_uri'] === '/api/users' && $request->getMethod() === 'GET') {
    //     $response->header("Content-Type", "application/json");
    //     $response->end(json_encode(['users' => [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']]]));
    // } else {
    //     $response->status(404);
    //     $response->end("Not Found");
    // }

    // session_write_close();
}, 'session_create_id', true));

// Triggered when the server is shutting down
$server->on("Shutdown", function ($server, $workerId) {
    // ...
});

// Triggered when worker processes are being stopped
$server->on("WorkerStop", function ($server, $workerId) {
    // ...
});

$server->start();
