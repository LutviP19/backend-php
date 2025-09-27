<?php

declare(strict_types=1);

// // Disabled Log Errors
// ini_set('log_errors', 0);
// // ini_set('display_errors', 0);
// // ini_set('display_startup_errors', 0);
error_reporting(~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);

require_once __DIR__ . '/../vendor/autoload.php';


use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;

$server = new Server("0.0.0.0", 9502);

// $server->set([
// ]);

$server->on("Start", function (Server $server) {
    echo "OpenSwoole WebSocket Server is started at http://127.0.0.1:9502\n";
});

$server->on('Open', function (Server $server, OpenSwoole\Http\Request $request) {
    $clientInfo = $server->getClientInfo($request->fd);

    echo "connection open: {$request->fd}\n";

    $server->push($request->fd, json_encode(["hello", "Welcome, {$clientInfo['remote_ip']}"]));

    // $server->tick(1000, function () use ($server, $request) {
    //     $server->push($request->fd, json_encode(["hello", time()]));
    // });
});

$server->on('Message', function (Server $server, Frame $frame) {
    echo "received message: {$frame->data}\n";
    // $server->push($frame->fd, json_encode(["hello", time()]));
});

$server->on('Close', function (Server $server, int $fd) {
    echo "connection close: {$fd}\n";
});

$server->on('Disconnect', function (Server $server, int $fd) {
    echo "connection disconnect: {$fd}\n";
});

$server->start();
