<?php

declare (strict_types=1);
/**
 * This file is part of OpenSwoole.
 * @link     https://openswoole.com
 * @contact  hello@openswoole.com
 */

$serverip = "127.0.0.1";
$serverport = 9501;

/**
 * initializeServerConstant function
 *
 * @param  \OpenSwoole\Http\Request $request
 *
 * @return void
 */
function initializeServerConstant(\OpenSwoole\Http\Request $request): void
{
    // Setup
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

$http = new OpenSwoole\Http\Server($serverip, $serverport, OpenSwoole\Server::SIMPLE_MODE);


$http->set([
    // 'enable_static_handler' => true,
    // 'http_autoindex' => true,
    'document_root' => realpath(__DIR__ . '/../public/'),
]);


$http->on('request', function ($req, $resp) {

    $uri = $request->server["request_uri"] ?? "/";

    initializeServerConstant($req);
    ob_start();
    include __DIR__ . '/../public/index.php';
    $content = ob_get_clean();
    $resp->end($content);
});

$http->start();
