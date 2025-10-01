<?php

define('APP_START', microtime(true));
define('BASEPATH', __DIR__ . '/..');

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Bootstrap the Application.
 * @author Lutvi <lutvip19@gmail.com>
 */
/* ----------------------------- Default settings START -------------------------------- */
// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/..');
$dotenv->load();

//register configuration to the app.
\App\Core\Support\App::register('config', require __DIR__ . '/../config/app.php');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

//Starting the session will be the first we do.
ini_set('session.save_handler', env('SESSION_DRIVER', 'file'));
if (env('SESSION_DRIVER') === "redis") {
    ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
    ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
} else {
    ini_set('session.save_path', __DIR__ . '/../storage/framework/sessions');
}

session_name('BACKENDPHPSESSID'); // Set a custom session name


// Make sure use_strict_mode is enabled.
// use_strict_mode is mandatory for security reasons.
ini_set('session.use_strict_mode', 1);

// if (session_status() == PHP_SESSION_NONE) {
//     custom_session_start();
// }

// $sessID = custom_session_regenerate_id();
// Write useful codes
/* ----------------------------- Default settings END -------------------------------- */

$serverip = "127.0.0.1";
$serverport = 8080;
$sessID = '';

function initializeServerConstant($request): void
{
    global $serverip, $serverport;

    // \App\Core\Support\Log::debug(gettype($request), 'Bootstrap.initializeServerConstant.$request.gettype');
    // \App\Core\Support\Log::debug($request, 'Bootstrap.initializeServerConstant.$request');

    $_SERVER = [];
    $uri = $request->server["request_uri"] ?? $request["request_uri"];
    $requestip = $request->server["remote_addr"] ?? $request["remote_addr"];

    $_REQUEST = [];
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    $_FILES = $request->files ?? [];
    $_COOKIE = $request->cookie ?? [];

    $_REQUEST = array_merge($_GET, $_POST);

    $_SERVER['SERVER_NAME'] = $serverip;
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../public/');
    $_SERVER['SERVER_SOFTWARE'] = "Backend PHP";
    $_SERVER['PHP_SELF'] = isset($request->server['php_self']) ? $request->server['php_self'] : 'index';
    $_SERVER['SCRIPT_NAME'] = isset($request->server['script_name']) ? $request->server['script_name'] : 'php';
    $_SERVER['SCRIPT_FILENAME'] = isset($request->server['script_filename']) ? $request->server['script_filename'] : 'index.php';

    $reqData = is_array($request) ? $request : ($request->server ?? []);
    $servers = array_merge($reqData, (new \Swoole\Http\Request)->server ?? [], $request->server ?? []);
    foreach ($servers as $key => $value) {
        $_SERVER[strtoupper($key)] = $value;
    }

    $headers = array_merge((new \Swoole\Http\Request)->header ?? [], $request->header ?? [], getallheaders() ?? [], $reqData);
    foreach ($headers as $key => $value) {
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

function getRequestData(\OpenSwoole\Core\Psr\ServerRequest $request, ): array
{
    // Get uri atrributes
    $attributes = $request->getAttributes();
    // Get parameters from a Query string
    $requestQuery = $request->getQueryParams() ?? [];

    // Get the raw body stream
    $body = $request->getBody();
    $body->rewind();
    $rawBody = $body->getContents();
    // Decode the JSON data
    $jsonData = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Handle JSON decoding error
        $error = json_last_error_msg();
        // Log or display the error message
        // \App\Core\Support\Log::debug($error, 'Servers.bootstrap.getRequestData.json_last_error_msg');
        return new \OpenSwoole\Core\Psr\Response('Invalid Json data!,'.$error, 406, '', ['Content-Type' => 'text/plain']);
    }

    // \App\Core\Support\Log::debug($attributes, 'ApiServer.RouteMiddleware.addRoute.$attributes');
    // \App\Core\Support\Log::debug($requestQuery, 'ApiServer.RouteMiddleware.addRoute.$requestQuery');
    // \App\Core\Support\Log::debug($jsonData, 'ApiServer.RouteMiddleware.addRoute.$jsonData');

    return [
        'attributes' => $attributes,
        'requestQuery' => $requestQuery,
        'jsonData' => $jsonData,
    ];
}