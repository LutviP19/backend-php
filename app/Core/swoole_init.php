<?php

/**
 * Init Open Swoole Application
 * @author Lutvi <lutvip19@gmail.com>
 * @package Backend-PHP
 */
// app/Core/swoole_init.php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../..');
}

if (!defined("BASEPATH_FFI")) {
    define("BASEPATH_FFI", BASEPATH . "/bin/ffi");
}


require_once BASEPATH . '/vendor/autoload.php';

// Load .env only once when the server is on
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(BASEPATH);
$dotenv->load();

use App\Core\Support\App;

// Register Config & Routes cukup 1x di RAM
App::register('config', require BASEPATH . '/config/app.php');
App::register("routing_external_api", require BASEPATH . "/routes/external-api.php");

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// Starting the session will be the first we do.
ini_set('session.save_handler', env('SESSION_DRIVER', 'files'));
if (env('SESSION_DRIVER') === "redis") {
    // ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
    // ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours

    $redisHost = config("redis.default.host", "127.0.0.1");
    $redisPort = config("redis.default.port", 6379);
    $redisPass = config("redis.default.password");
    $lifetime  = (int) config("session.lifetime", 120) * 60;


    $redisPath = "tcp://{$redisHost}:{$redisPort}";
    if (!is_null($redisPass) && $redisPass !== '') {
        $redisPath .= "?auth=" . urlencode((string)$redisPass);
    }

    ini_set("session.save_path", $redisPath);
    ini_set("session.gc_maxlifetime", $lifetime);
} else {
    ini_set('session.save_path', BASEPATH . '/storage/framework/sessions');
}


$serverip = "127.0.0.1";
// $serverport = 8008;
$serverport = 8009;
$sessionName = 'WEBBACKENDPHPSESSID';
$sessionId = '';

// Set a custom session name
ini_set('session.use_strict_mode', 0);
session_name($sessionName);
ini_set('session.use_strict_mode', 1);


/**
 * Helper to check whether a string is a valid JSON format
 */
function is_json(mixed $string): bool
{
    if (!is_string($string) || trim($string) === '') {
        return false;
    }
    
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

function initializeServerConstant($request, $response): void
{
    global $serverip, $serverport;

    // --- SYNCHRONIZE HEADER FROM NATIVE PHP (If there is still a header() function that escapes) ---
    if (function_exists('headers_list')) {
        foreach (headers_list() as $headerLine) {
            if (str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $response->header(trim($name), trim($value));
            }
        }
        // Clean the native PHP header list so that it doesn't pile up in subsequent requests
        if(! headers_sent()) {
            header_remove();
        }
    }

    // Inject into GLOBALS to be detected by isSwoole() & ApiController
    $GLOBALS['requestServer'] = $request;
    $GLOBALS['swoole_response'] = $response;

    // \App\Core\Support\Log::debug(gettype($request), 'Bootstrap.initializeServerConstant.$request.gettype');
    // \App\Core\Support\Log::debug($request, 'Bootstrap.initializeServerConstant.$request');

    $_SERVER = [];
    $_SESSION = [];
    // Clean up $_SERVER dari request sebelumnya
    $_SERVER = array_filter($_SERVER, function($key) {
        return !str_starts_with($key, 'HTTP_');
    }, ARRAY_FILTER_USE_KEY);

    $uri = $request->server["request_uri"] ?? $request["request_uri"];
    $requestip = $request->server["remote_addr"] ?? $request["remote_addr"];

    $_REQUEST = [];    
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    $_FILES = $request->files ?? [];
    $_COOKIE = $request->cookie ?? [];

    $_REQUEST = array_merge($_GET, $_POST);

    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REQUEST_METHOD'] = $request->server['request_method'] ?? 'GET';
    $_SERVER['SERVER_NAME'] = $serverip;
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public/';
    $_SERVER['SERVER_SOFTWARE'] = "Backend PHP";
    $_SERVER['PHP_SELF'] = $request->server['php_self'] ?? 'index';
    $_SERVER['SCRIPT_NAME'] = $request->server['script_name'] ?? 'php';
    $_SERVER['SCRIPT_FILENAME'] = $request->server['script_filename'] ?? 'index.php';

    $reqData = is_array($request) ? $request : ($request->server ?? []);
    $servers = array_merge($reqData, (new \Swoole\Http\Request)->server ?? [], $request->server ?? []);
    foreach ($servers as $key => $value) {
        $_SERVER[strtoupper((string) $key)] = $value;
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

    foreach ($request->header as $key => $value) {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        $_SERVER[$serverKey] = $value;
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