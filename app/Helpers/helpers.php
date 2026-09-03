<?php

/**
 * Global helpers.
 * @author Lutvi <lutvip19@gmail.com>
 */

// use App\Core\Http\Request;
// use App\Core\Security\CSRF;
// use App\Core\Support\Session;
use App\Core\Support\App;
use App\Core\Support\Config;

/** ===== Utils ===== */
if (!function_exists('b64url')) {
    function b64url($data)
    {
        return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
    }
}

/**
 * get config
 *
 * @param  [string] $key
 *
 * @return string
 */
function config($key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * Converts selected environment variables to JSON format for Frontend.
 */
function env_to_json(array $keys)
{
    $output = [];
    foreach ($keys as $key) {
        $output[$key] = env($key);
    }
    return json_encode($output);
}

// To handle CORS (Cross-Origin Resource Sharing)
// Specify allowed origins (from .env file)
function handle_cors()
{
    if (!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_REFERER'])) {
        return;
    }

    // 1. Take string from .env, defaults to '*' if empty
    $envOrigins = env('ALLOWED_ORIGINS', '*');

    $allowedOrigins = ($envOrigins !== '*')
        ? explode(',', $envOrigins)
        : ['*'];

    $currentOrigin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];
    // dd($currentOrigin);

    $cleanOrigin = parse_url($currentOrigin, PHP_URL_HOST);
    // dd($cleanOrigin);

    if (in_array('*', $allowedOrigins) || in_array($cleanOrigin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $currentOrigin");
    }

    // Set output header ke browser
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, hx-request, hx-target, hx-current-url, hx-trigger, hx-trigger-name");
    header("Access-Control-Allow-Credentials: true");

    // Handle "Preflight" requests (browser sends OPTIONS method before POST/PUT)
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(204); // No Content
        // exit();
        customExit();
    }
}

/**
 * Get all REQUEST headers
 * @return array
 */
if (! function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            // if (substr($name, 0, 5) == 'HTTP_') {
            if (str_starts_with((string) $name, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * setHeaders function, to add header response
 *
 * @param  array $headers
 *
 * @return void
 */
function setHeaders($headers = [])
{
    if (count($headers)) {
        foreach ($headers as $header) {
            if (is_array($header)) {
                foreach ($header as $key => $value) {
                    header("{$key}: {$value}");
                }
            }
        }
    }
}

/**
 * Simple helper log system with category levels.
 */
function write_log($level = 'info', $logs = '', $moduleName = '', $single = true)
{

    \App\Core\Support\Log::saveLog($level, $logs, $moduleName, $single);
}


/**
 * sort request function
 *
 * @return \App\Core\Http\Request()
 */
function request()
{
    return new \App\Core\Http\Request();
}

/**
 * sort response function
 *
 * @return \App\Core\Http\Response()
 */
function response()
{
    return new \App\Core\Http\Response();
}

/**
 * endResponse function, stop to response with conditional isSwoole()
 *
 * @param  json_response $response
 *
 * @return void
 */
function endResponse($response, $status = 200, $headers = [])
{
    if (!headers_sent()) {
        // noindex instructs crawlers not to index the resource
        // nofollow instructs crawlers not to follow links on the resource
        header('X-Robots-Tag: noindex, nofollow');
    }

    // CSRF-TOKEN
    $csrfToken = \App\Core\Security\CSRF::generate();
    $expired_seconds = time() + (60 * 60 * 24 * 1);
    $domain = env('APP_ENV') === 'local' ? 'localhost' : 'happyfew.org';
    $path = '/';
    $isSecure = env('APP_ENV') !== 'local'; // Flexible secure flag based on environment

    $csrfHeader[] = ['Set-Cookie' => "XSRF-TOKEN={$csrfToken}; Max-Age={$expired_seconds}; Path={$path}; Domain={$domain}; HttpOnly; SameSite=Lax; Secure;"];
    $headers = array_merge($csrfHeader, $headers);

    if (! isSwoole()) {
        if (count($headers)) {
            foreach ($headers as $header) {
                if (!is_string($header)) {
                    foreach ($header as $key => $value) {
                        header("{$key}: {$value}");
                    }
                }
            }
        }

        // die(response()->json($response, $status));
        response()->json($response, $status);
    } else {
        // --- OPENSWOOLE ---
        if (isset($GLOBALS['swoole_response']) && $GLOBALS['swoole_response'] instanceof \OpenSwoole\Http\Response) {
            $swooleResp = $GLOBALS['swoole_response'];

            if ($swooleResp->isWritable()) {
                // Set CSRF Cookie Using Native Swoole Method
                // Signature: cookie(name, value, expires, path, domain, secure, httponly, samesite)
                $swooleResp->cookie(
                    'XSRF-TOKEN',
                    $csrfToken,
                    $expired_seconds, // Swoole automatically converts these timestamps to Max-Age /Expires
                    $path,
                    $domain,
                    $isSecure,
                    true,  // HttpOnly
                    'Lax'  // SameSite
                );

                // Iterate & Set Custom Headers
                $swooleResp->header('X-Robots-Tag', 'noindex, nofollow');
                if (!empty($headers)) {
                    foreach ($headers as $header) {
                        if (is_array($header)) {
                            foreach ($header as $key => $value) {
                                // Method header() OpenSwoole Received (string $key, string $value)
                                $swooleResp->header((string)$key, (string)$value);
                            }
                        }
                    }
                }

                // Set HTTP Status Code
                $swooleResp->status($status);
            }
        }
    }

    $response = array_merge($response, config('app.debug') ? ['sessionName' => session_name()] : []);

    session_write_close();
    exit_response($response, $status);
    return;
}

function sessionKeyFormat($userId, $sessionId = '*'): string
{
    return "session:" . ($userId ?? 'guest-'.\clientIP()) .":{$sessionId}";
}

if (!function_exists('array_except')) {
    /**
     * Hide certain keys/properties from Array or stdClass.
     *
     * @param array|object $data Input is an Array or stdClass
     * @param array|string $keys Key/Property to hide
     * @return array|object Returns the same data type as the input
     */
    function array_except(array|object $data, array|string $keys): array|object
    {
        $isObject = is_object($data);
        $array = $isObject ? (array) $data : $data;
        $keys = (array) $keys;

        $filtered = array_diff_key($array, array_flip($keys));

        return $isObject ? (object) $filtered : $filtered;
    }
}

/**
 * Check whether the request expects or sends JSON content.
 * Accurate for Global Superglobals ($_SERVER) and OpenSwoole / PSR Requests.
 *
 * @param mixed|null $request Optional OpenSwoole\Http\Request or PSR-7 Requests instance
 * @return bool
 */
function is_json_request($request = null): bool
{
    // 1. Special OpenSwoole Request /PSR-7 Support
    if ($request !== null) {
        if (method_exists($request, 'getHeaderLine')) {
            // PSR-7 Request Object
            $contentType = $request->getHeaderLine('content-type');
            $accept = $request->getHeaderLine('accept');
        } elseif (isset($request->header) && is_array($request->header)) {
            // OpenSwoole\Http\Request
            $headers = array_change_key_case($request->header, CASE_LOWER);
            $contentType = $headers['content-type'] ?? '';
            $accept = $headers['accept'] ?? '';
        } else {
            $contentType = '';
            $accept = '';
        }

        return (
            str_contains(strtolower($contentType), 'json') ||
            str_contains(strtolower($accept), 'json')
        );
    }

    // 2. Check Content-Type (Body Payload: e.g., application/json, application/problem+json)
    $contentType = $_SERVER['CONTENT_TYPE']
        ?? $_SERVER['HTTP_CONTENT_TYPE']
        ?? $_SERVER['REDIRECT_HTTP_CONTENT_TYPE']
        ?? '';

    // 3. Check Accept Header (Respons Expectation: e.g., application/json, */*)
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

    // 4. Fallback: Checking via getallheaders() (Case-Insensitive)
    if (empty($contentType) && function_exists('getallheaders')) {
        $allHeaders = array_change_key_case(getallheaders(), CASE_LOWER);
        $contentType = $allHeaders['content-type'] ?? '';
        if (empty($accept)) {
            $accept = $allHeaders['accept'] ?? '';
        }
    }

    // Normalize to lowercase
    $contentType = strtolower((string) $contentType);
    $accept = strtolower((string) $accept);

    // 5. Evaluate the 'json' substring (Includes 'application/json', 'application/vnd.api+json', etc)
    return str_contains($contentType, 'json') || str_contains($accept, 'json');
}


/**
 * Sends a standard JSON response and stops script execution.
 */
function json_response($data, $status = 200, $message = "", $errors = [])
{
    header("Content-Type: application/json");
    http_response_code($status);

    // Format output JSON
    if ($message !== "") {
        $data = [
            "statusCode" => $status,
            "message" => $message,
            "data" => $data,
        ];
    } else {
        $data = [
            "statusCode" => $status,
            "data" => $data,
        ];
    }
    // Remove errors if any
    if (!empty($errors)) {
        unset($data["data"]);
        $data["errors"] = $errors;
    }
    // Unset data if status >= 300
    if ($status >= 300) {
        unset($data["data"]);
    }

    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    customExit();
}

// Alias exit_response for open swoole /FPM
function exit_response(mixed $data, int $statusCode = 200, array $extraHeaders = []): void
{
    htmx_response($data, $statusCode, $extraHeaders);
}

/**
 * Send custom String | JSON response to HTMX /API client
 * with dynamic HTTP Status Code (default: 422 Unprocessable Entity).
 *
 * Compatible for OpenSwoole & PHP-FPM.
 *
 * @param mixed $data Data payload / errors array
 * @param int $statusCode HTTP Status Code (Example: 422, 400, 500)
 * @param array $extraHeaders Additional HTTP Headers (e.g. ['HX-Retarget' => '#error-box'])
 * @return void
 */
function htmx_response(mixed $data, int $statusCode = 422, array $extraHeaders = []): void
{
    $payload = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if (\isSwoole()) {
        /** @var \OpenSwoole\Http\Response|null $swooleResponse */
        $swooleResponse = $GLOBALS['swoole_response'] ?? (function_exists('app') && app()->has('swoole_response') ? app('swoole_response') : null);

        if ($swooleResponse && method_exists($swooleResponse, 'end')) {
            if ($swooleResponse->isWritable()) {
                $swooleResponse->status($statusCode);
                $swooleResponse->header('Content-Type', 'application/json; charset=UTF-8');

                foreach ($extraHeaders as $key => $value) {
                    $swooleResponse->header((string)$key, (string)$value);
                }

                $swooleResponse->end($payload);
            }

            // Terminate the Router flow with a Swoole specific Exception
            if (class_exists('\App\Core\Exceptions\SwooleExitException')) {
                throw new \App\Core\Exceptions\SwooleExitException($statusCode);
            }

            return;
        }
    }

    // -------------------------------------------------------------
    // STANDARD PHP-FPM /CGI PATH
    // -------------------------------------------------------------
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        foreach ($extraHeaders as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    customExit($payload, $statusCode);
}


/**
 * checkValidJSON function, to Check valid JSON format
 *
 * @param  string $rawBody
 *
 * @return bool
 */
function checkValidJSON($rawBody): bool
{
    if ($rawBody === '') {
        return false;
    }

    $validBody = json_decode(trim($rawBody), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return true;
}

/**
 * default database path for sqlite
 *
 * @param  string $key
 *
 * @return string
 */
function database_path($db_name)
{
    return BASEPATH . 'storage/database/' . $db_name;
}

/**
 * default database path for sqlite
 *
 * @param  string $key
 *
 * @return string
 */
function storage_path($filePath)
{
    return BASEPATH . 'storage/' . $filePath;
}

/**
 * default database path for public/assets/
 *
 * @param  string $key
 *
 * @return string
 */
function assets_path($filePath)
{
    return BASEPATH . 'public/assets/' . $filePath;
}

/**
 * Helpers (alias - old version) to call files in the public/folder
 */
if (!function_exists("asset")) {
    function asset($path)
    {
        return assets($path);
    }
}

function assets(string $uri = ''): string
{
    $uri = sanitizeUri($uri);

    if (\isSwoole()) { // OpenSwoole Server
        // Use null coalescing (??) to be safe from undefined index
        $host = $_SERVER['HTTP_HOST'] ?? request()->header('host') ?? 'localhost';
        return "//{$host}/{$uri}";
    }

    $baseUrl = rtrim(config('app.url') ?? '', '/');
    return "{$baseUrl}/{$uri}";
}

/**
 * default log path
 *
 * @param  string $log_name
 *
 * @return string
 */
function logs_path($log_name)
{
    return BASEPATH . 'storage/logs/' . $log_name;
}

function isHtmx()
{
    return isset($_SERVER["HTTP_HX_REQUEST"]) && $_SERVER["HTTP_HX_REQUEST"] === "true";
}


/**
 * dump the data and kill the page.
 *
 * @param array $data
 * @return void
 */
function dd($data = [], $json = false)
{
    if ($json) {
        exit_response($data);
    }

    exit_response("<pre>". var_dump($data) . "</pre>");
}

/**
 * Create a url from a given uri.
 *
 * @param string $uri
 * @return string
 */
function url($uri = '')
{
    $uri = sanitizeUri($uri);
    return config('app.url')."/{$uri}";
}

function cacheContent($method, $id, $content = null, $expiry = 3600)
{
    if (isSwoole()) {
        if ($method === 'set') {
            (new \App\Core\Support\CacheSwoole())->set($id, $content, $expiry);
        }

        if ($method === 'get') {
            return (new \App\Core\Support\CacheSwoole())->get($id);
        }
    } else {
        if ($method === 'set') {
            (new \App\Core\Support\Cache())->set($id, $content, $expiry);
        }

        if ($method === 'get') {
            return (new \App\Core\Support\Cache())->get($id);
        }
    }

    return $content;
}

function delCache($id)
{
    if (isSwoole()) {
        (new \App\Core\Support\CacheSwoole())->flush($id);
    } else {
        (new \App\Core\Support\Cache())->flush($id);
    }

}

/**
 * Setup Redis Connection Helper
 *
 * @return \Predis\Client|null Mengembalikan objek Predis jika sukses, atau null/exception jika gagal
 * @throws \Exception
 */
function setupRedisConnection()
{
    $host     = (string) Config::get('redis.cache.host');
    $port     = (int)    Config::get('redis.cache.port');
    $username = Config::get('redis.cache.username');
    $password = Config::get('redis.cache.password');
    $database = (int)    Config::get('redis.cache.database');

    $parameters = [
        'host'     => $host,
        'port'     => $port,
        'database' => $database,
    ];

    if (!is_null($username) && $username !== '') {
        $parameters['username'] = (string)$username;
    }
    if (!is_null($password) && $password !== '') {
        $parameters['password'] = (string)$password;
    }

    // // 2. Muted E_DEPRECATED if your Predis library is not the latest version
    // $oldErrorReporting = error_reporting();
    // error_reporting($oldErrorReporting & ~E_DEPRECATED);

    try {
        $redis = new \Predis\Client($parameters);

        // 3. REAL CONNECTION TEST: Since Predis is 'lazy',
        // we call ping() inside try-catch to make sure the server is up.
        // $redis->ping();

        return $redis;

    } catch (\Throwable $e) {
        // Write an internal error log to make debugging easier on your Docker/Ubuntu
        if (class_exists('\App\Core\Support\Log')) {
            \App\Core\Support\Log::error("Redis Connection Failed: " . $e->getMessage(), "Helpers.setupRedisConnection");
        }

        throw new \Exception("Could not connect to Redis server: " . $e->getMessage(), 0, $e);
        // return null; // Enable this if you want fallback without crashes

    } finally {
        // // Restore the application's original error reporting
        // error_reporting($oldErrorReporting);

        return $redis;
    }
}

function getDataFromRedis($id, $prefix = null)
{
    if (empty($id)) {
        return;
    }

    // Connect to Redis
    $redis = setupRedisConnection();

    // $prefix = $prefix ?? 'bp_data';
    $prefix ??= 'bp_data';

    $data = $redis->get($prefix.':'.$id);

    if (! is_null($data) && isset($data[0])) {
        return unserialize(base64_decode($data[0]));
    }

    return [];
}

function delDataFromRedis($id, $prefix = null, $db = null, $force = false)
{
    if (empty($id)) {
        return;
    }

    // Connect to Redis
    $redis = setupRedisConnection();

    // $prefix = $prefix ?? 'bp_data';
    $prefix ??= 'bp_data';

    $data = $redis->get($prefix.':'.$id);

    if ((! is_null($data) && isset($data[0])) || $force) {
        $redis->del($prefix.':'.$id);
    }
}

function clearRedisDataByPrefix($prefix = null)
{
    // Connect to Redis
    $redis = setupRedisConnection();

    $prefix = $prefix ?: 'bp';
    $pattern = $prefix.':*'; // The pattern to match keys (e.g., all keys starting with 'my_prefix:')
    $cursor = 0;
    $keysToDelete = [];

    do {
        // Perform a SCAN operation to find keys matching the pattern
        // The 'MATCH' option specifies the pattern, and 'COUNT' suggests how many keys to return per iteration
        $scanResult = $redis->scan($cursor, 'MATCH');

        $cursor = $scanResult[0]; // Update the cursor for the next iteration
        $keysFound = $scanResult[1]; // Get the keys found in this iteration

        if (!empty($keysFound)) {
            // Add the found keys to the list of keys to delete
            $keysToDelete = array_merge($keysToDelete, $keysFound);
        }

    } while ($cursor !== 0); // Continue scanning until the cursor is 0 (all keys scanned)

    // Delete the collected keys if any were found
    if (!empty($keysToDelete)) {
        // You can use DEL to delete multiple keys at once
        $redis->del($keysToDelete);
    }
}

function clearCacheFileByPrefix($directory = null, $pattern = null)
{
    $directory = $directory ?: storage_path('framework/cache/'); // Specify the directory where files are located
    $pattern = $pattern ?: '*.cache'; // Default: Delete all files ending with .cache

    // Combine directory and pattern to form the full pattern for glob()
    $fullPattern = $directory . $pattern;

    // Use glob() to find files matching the pattern
    $filesToDelete = glob($fullPattern);

    // Check if any files were found
    if ($filesToDelete !== false && !empty($filesToDelete)) {
        foreach ($filesToDelete as $file) {
            if (is_file($file)) { // Ensure it's a file and not a directory
                unlink($file);
            }
        }
    }
}

/**
 * Get the current url.
 *
 * @return string
 */
function currentUrl()
{
    return url(\App\Core\Http\Request::uri());
}

/**
 * Sanitize the given uri.
 *
 * @param string $uri
 * @return string
 */
function sanitizeUri($uri)
{
    // if (strpos($uri, '/') == 0) {
    if (str_starts_with($uri, '/')) {
        $uri = ltrim($uri, '/');
    }

    return filter_var(
        $uri,
        FILTER_SANITIZE_URL
    );
}

/**
 * get the csrf token.
 *
 * @return string
 */
function token()
{
    return \App\Core\Security\CSRF::generate();
}

/**
 * get the csrf hidden field
 *
 * @return string
 */
function csrfField()
{
    return \App\Core\Security\CSRF::csrfField();
}

/**
 * Convert specialchars to html entities.
 *
 * @param string $str
 * @return string
 */
function e($str, $doubleEncode = true)
{
    if (is_array($str)) {
        return json_encode($str);
    }

    // // if (is_numeric($str) && !is_float($str)) {
    // //     return (int)$str;
    // // }

    // // if (is_numeric($str) && is_float($str)) {
    // //     return floattostr(floatval($str));
    // // }

    // // // if (is_float($str) && is_numeric($str) === true && is_decimal($str) === false) {
    // // if (is_float_string($str) && is_numeric($str) && is_decimal($str) === false) {
    // //     dd($str);
    // //     //sprintf("%.2f", $str);
    // //     return round($str, 2);
    // // }

    // // if (is_decimal($str) && is_numeric($str) === true && is_float($str) === false) {
    // //     // number_format($latitude, 6);
    // //     return $str;
    // // }

    // return htmlentities($str, ENT_QUOTES, 'UTF-8');
    // // return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);

    // The Best and Most Compatible Choice
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
}

function remove_php_comments($code)
{
    // Remove single-line comments that start with # or //
    // The pattern accounts for potential URLs (http://) and ensures the comment starts correctly.
    $patterns = [
        '/[ \t]*\/\/.*$/m', // For `//` style comments
        '/[ \t]*#.*$/m',   // For `#` style comments
    ];
    $code = preg_replace($patterns, '', (string) $code);

    // Remove multi-line comments `/* ... */`
    // The 's' modifier allows the dot (.) to match newlines.
    // $code = preg_replace('/\/\*.*?\*\//s', '', $code);
    $code = preg_replace('/\/\*.*?\*\//s', '', (string) $code);


    return $code;
}

/**
 * Get a session value.
 *
 * @param string $key
 * @return string|bool
 */
function session($key)
{
    return \App\Core\Support\Session::get($key);
}

/**
 * Set/Get a flash message.
 *
 * @param string $key
 * @param string|int $value
 * @return string|bool
 */
function flash($key, $value = null)
{
    return \App\Core\Support\Session::flash($key, $value);
}

/**
 * Errors from messagebag (Validation errors).
 *
 * @return \App\Core\Validation\MessageBag
 */
function errors()
{
    return App::get('errors');
}

/**
 * Get the input value from the previous request.
 *
 * @return mixed
 */
function old($key)
{
    return \App\Core\Support\Session::getOldInput($key);
}

/**
 * Get client IP
 *
 * @return string
 */
function clientIP()
{
    // Get real visitor IP behind CDN such as Cloudflare
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }

    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip == '::1' ? '127.0.0.1' : $ip;
}

/**
 * Match encryption data
 *
 * @param  [string] $value
 * @param  [string] $encryptedData
 * @param  [string] $key
 *
 * @return mixed
 */
function matchEncryptedData($value, $encryptedData, $key = null)
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->match($value, $encryptedData);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \App\Core\Support\Log::error([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'Helper.matchEncryptedData');
        }

        return false;
    }
}

/**
 * sort function to encypt data
 *
 * @param  [string] $value
 * @param  [string] $key
 *
 * @return string|null
 */
function encryptData($value, $key = null)
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->encrypt($value);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \App\Core\Support\Log::error([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'Helper.encryptData');
        }

        return null;
    }
}

/**
 * sort function to decypt data
 *
 * @param  [string] $value
 * @param  [string] $key
 *
 * @return string|null
 */
function decryptData($value, $key = null)
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->decrypt($value);
    } catch (Throwable $ex) {
        if (config('app.debug')) {
            \App\Core\Support\Log::error([
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                // 'trace' => $ex->getTraceAsString(),
            ], 'Helper.decryptData');
        }

        return null;
    }
}

/**
 * sort function to generate random string
 *
 * @param  integer $len
 * @param  boolean $base64
 *
 * @return string
 */
function generateRandomString($len = 64, $base64 = false, $special = true): string
{
    if ($base64) {
        return base64_encode(\App\Core\Security\Hash::randomString($len, $special));
    }

    return \App\Core\Security\Hash::randomString($len, $special);
}

/**
 * sort function to generate ulid
 *
 * @param  boolean $lowercased
 * @param  int  $timestamp
 *
 * @return string
 */
function generateUlid($lowercased = false, $timestamp = null): string
{
    $ulid = \App\Core\Support\UlidGenerator::generate();
    if ($lowercased) {
        return strtolower($ulid);
    }

    return $ulid;
}

/**
 * sort function to send queue message to rabbitmq
 *
 * @param  [string] $message
 *
 * @return void
 */
function sendMessageQueue($message): void
{
    (new \App\Core\Message\Broker())->sendMessage($message);
}

/**
 * sort function to sending email
 *
 * @param  string $from
 * @param  string $to
 * @param  string $subject
 * @param  string $bodyText
 * @param  string $bodyHtml
 * @param  array  $attachment
 * @param  array  $image
 *
 * @return bool
 */
function sendEmailPhp(string $from, string $to, string $subject, $bodyText = '', $bodyHtml = '', array $attachment = [], array $image = []): bool
{
    $from = explode(",", $from);
    $to = explode(",", $to);

    if (count($from)) {
        $from = $from[0];
    }

    if (count($to)) {
        $to = $to[0];
    }

    $headers = 'From: '. $from . "\r\n" .
               'Reply-To: '. $from . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    if (mail($to, $subject, $bodyText, $headers)) {
        $status = true;
    } else {
        $status = false;
        $msg = 'Gagal mengirim email.';
        \App\Core\Support\Log::error([
            'msg' => $msg,
            'to' => $to,
            'subject' => $subject,
            'headers' => $headers,
        ], 'Helpers.sendEmail.mail');
    }

    return $status;
}

/**
 * sort function to sending email
 *
 * @param  string $from
 * @param  string $to
 * @param  string $subject
 * @param  string $bodyText
 * @param  string $bodyHtml
 * @param  array  $attachment
 * @param  array  $image
 *
 * @return bool
 */
function sendEmail(string $from, string $to, string $subject, $bodyText = '', $bodyHtml = '', array $attachment = [], array $image = []): bool
{
    if (config('app.env') === 'local') {
        try {
            $email = (new \App\Core\Mailer\Email());
            $email->prepareData($from, $to, $subject, $bodyText, $bodyHtml, $attachment, $image);
            $email->send();
            return true;
        } catch (\Exception $e) {
            // throw new Exception('Error send email: ' . $e->getMessage());
            if (config('app.debug')) {
                \App\Core\Support\Log::error('Error send email: ' . $e->getMessage(), 'Helpers.sendEmail');
            }
            return false;
        }
    } else {
        $from = explode(",", $from);
        $to = explode(",", $to);

        if (count($from)) {
            $from = $from[0];
        }

        if (count($to)) {
            $to = $to[0];
        }

        $headers = 'From: '. $from . "\r\n" .
                   'Reply-To: '. $from . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        if (mail($to, $subject, $bodyText, $headers)) {
            $status = true;
        } else {
            $status = false;
            $msg = 'Gagal mengirim email.';
            if (config('app.debug')) {
                \App\Core\Support\Log::error([
                    'msg' => $msg,
                    'to' => $to,
                    'subject' => $subject,
                    'headers' => $headers,
                ], 'Helpers.sendEmail.mail');
            }
        }

        return $status;
    }
}

/**
 * sort function to validate json
 *
 * @param  [string]  $value
 *
 * @return boolean
 */
function isJson($value): bool
{
    if (!is_string($value)) {
        return false;
    }

    if (function_exists('json_validate')) {
        return json_validate($value, 512);
    }

    try {
        json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return false;
    }

    return true;
}

/**
 * sort function to read json node
 *
 * @param  [string] $key
 * @param  [string] $payload
 * @param  [string] $default
 *
 * @return void
 */
function readJson($key = null, $payload = null, $default = null)
{
    if (empty($key) || empty($payload)) {
        return null;
    }

    $keys = explode('.', (string) $key);
    foreach ($keys as $key) {
        if (isset($payload[$key])) {
            $payload = $payload[$key];
        } else {
            return $default;
        }
    }

    return $payload;
}

/**
 * Get short version of User Agent (Browser + OS + Version).
 * @param bool $is_hash If true, returns the MD5 hash (32 char).
 * @return string
 */
function get_short_ua(bool $is_hash = false): string
{
    $ua = $_SERVER["HTTP_USER_AGENT"] ?? "Unknown";
    
    // Make sure $ua is actually a pure string, not an array of other global modifications
    $uaString = is_array($ua) ? implode(" ", $ua) : (string) $ua;

    // 1. Identify Platform/OS and Version
    $os = "Unknown";
    if (preg_match("/Windows NT ([\d\.]+)/i", $uaString, $m)) {
        $os = "Win" . $m[1];
    } elseif (preg_match("/Android ([\d\.]+)/i", $uaString, $m)) {
        $os = "Android" . (int) $m[1];
    } elseif (preg_match("/iPhone OS ([\d_]+)/i", $uaString, $m)) {
        $os = "iOS" . (int) str_replace("_", "", $m[1]);
    } elseif (preg_match("/Mac OS X ([\d_]+)/i", $uaString, $m)) {
        $os = "MacOS" . (int) str_replace("_", "", $m[1]);
    } elseif (stripos($uaString, "linux") !== false) {
        $os = "Linux";
    }

    // 2. Identify Major Browsers & Versions
    $browser = "Unknown";
    
    // Reset the contents of the $m variable before the browser regex for data security
    $m = [];
    
    if (preg_match("/(Edg|Edge)\/([\d\.]+)/i", $uaString, $m)) {
        $browser = "Edge" . (int) $m[2];
    } elseif (preg_match("/OPR\/([\d\.]+)/i", $uaString, $m)) {
        $browser = "Opera" . (int) $m[1];
    } elseif (preg_match("/Chrome\/([\d\.]+)/i", $uaString, $m)) {
        $browser = "Chrome" . (int) $m[1];
    } elseif (preg_match("/Firefox\/([\d\.]+)/i", $uaString, $m)) {
        $browser = "Firefox" . (int) $m[1];
    } elseif (preg_match("/Version\/([\d\.]+).*Safari/i", $uaString, $m)) {
        $browser = "Safari" . (int) $m[1];
    }

    $shortUa = $os . "_" . $browser;

    // If detection fails, use the original string cleaned up slightly
    if ($shortUa === "Unknown_Unknown") {
        $sanitized = preg_replace("/[^a-zA-Z0-9]/", "", $uaString);
        $shortUa = substr((string) $sanitized, 0, 20);
    }

    return $is_hash ? md5($shortUa) : $shortUa;
}


/**
 * Get stable device fingerprinting.
 * @param bool $is hash If true, returns MD5 hash (32 characters).
 * @return string
 */
function get_device_fingerprint(bool $is_hash = true, string $extra = ''): string
{
    // Combine Platform + UA + IP (Optional: add IP to make it tighter)
    $fingerprint = get_short_ua() . "_" . clientIP();
    if($extra !== '') {
        $fingerprint = $fingerprint . "_" . $extra;
    }

    return $is_hash ? md5($fingerprint) : $fingerprint;
}