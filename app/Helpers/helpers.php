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
use App\Core\Validation\MessageBag;

/**
 * get environment variable.
 *
 * @param array $data
 * @return void
 */
function env($key, $alt = '')
{
    return isset($_ENV[$key]) ? $_ENV[$key] : $alt;
}

/**
 * get config
 *
 * @param  [string] $key
 *
 * @return string
 */
function config($key)
{
    return Config::get($key);
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
 * endResponse function, stop to response with conditional SERVER_PORT
 *
 * @param  json_response $response
 *
 * @return void
 */
function endResponse($response, $status = 200, $headers = [])
{

    if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // non OpenSwoole Server
        die(response()->json($response, $status));
    }

    // get sessionId, then merged it to response
    global $sessionId;
    if(empty($sessionId) || empty(session_id()))
        $sessionId = $_COOKIE[session_name()] ?: session_create_id('bpw-');

    $response = array_merge($response, ['sessionId' => session_id() ?: $sessionId]);

    $responseX = [];
    $responseArr = response()->json($response, $status);
    // \App\Core\Support\Log::debug($responseArr, 'Helper.endResponse.$responseArr');

    // \App\Core\Support\Log::debug(count($headers), 'Helper.endResponse.count($headers)');
    if (count($headers)) {
        $responseArr['headers'] = $headers;
    }

    // \App\Core\Support\Log::debug($responseArr, 'Helper.endResponse.$responseArr');
    while (true) {
        if (isset($responseArr['code']) && ! in_array($responseArr['code'], [200, 201])) {
            $responseX[] = array_merge(response()->json($response, $status), $headers);
        }
        break;
    }

    if (count($responseX)) {
        print json_encode($responseX[0]).'@|@';
    } else {
        print json_encode($responseArr);
    }

    return;
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
    return BASE_PATH . 'storage/database/' . $db_name;
}

/**
 * dump the data and kill the page.
 *
 * @param array $data
 * @return void
 */
function dd($data = [])
{
    echo "<pre>", var_dump($data), "</pre>";
    die();
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
    return "//{$_SERVER['HTTP_HOST']}/{$uri}";
}

function assets($uri = '')
{
    $uri = sanitizeUri($uri);
    if ($_SERVER['SERVER_PORT'] === 9501) { // OpenSwoole Server
        return "//{$_SERVER['HTTP_HOST']}/{$uri}";
    }

    return "//{$_SERVER['HTTP_HOST']}/{$uri}";
}

function getRedisContent($id, $prefix = '', $db = null)
{
    $redis = new \Predis\Client([
        'host' => Config::get('redis.cache.host'),
        'port' => Config::get('redis.cache.port'),
        'database' => $db ?? Config::get('redis.cache.database')
    ]);

    $path = $prefix.':'.$id;
    \App\Core\Support\Log::debug($path, 'Helper.getRedisContent.$path');

    $data = $redis->get($path);
    \App\Core\Support\Log::debug($data, 'Helper.getRedisContent.$data');

    if ($data === false)
        return null;
    
    return $data;
}

function cacheContent($method, $id, $content = null)
{
    if ($method === 'set') {
        (new \App\Core\Support\Cache())->saveData($id, $content);
    }
    if ($method === 'get') {
        return (new \App\Core\Support\Cache())->getData($id);
    }

    return $content;
}

function clearRedisDataByPrefix($prefix = null)
{
    // Connect to Redis
    $redis = new \Predis\Client([
        'host' => Config::get('redis.cache.host'),
        'port' => Config::get('redis.cache.port'),
        'database' => Config::get('redis.cache.database')
    ]);

    $prefix = $prefix ?: 'bp';
    $pattern = $prefix.':*'; // The pattern to match keys (e.g., all keys starting with 'my_prefix:')
    $cursor = 0;
    $keysToDelete = [];

    do {
        // Perform a SCAN operation to find keys matching the pattern
        // The 'MATCH' option specifies the pattern, and 'COUNT' suggests how many keys to return per iteration
        $scanResult = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 1000);

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
    $directory = $directory ?: realpath(__DIR__ . '/../../storage/framework/cache/'); // Specify the directory where files are located
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
    if (strpos($uri, '/') == 0) {
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

    return htmlentities($str, ENT_QUOTES, 'UTF-8');
    // return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
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
    // return (new \App\Core\Security\Middleware\EnsureIpIsValid)->ip();

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
function generateRandomString($len = 64, $base64 = false): string
{
    if ($base64) {
        return base64_encode(\App\Core\Security\Hash::randomString($len));
    }

    return \App\Core\Security\Hash::randomString($len);
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
    if (!is_null($timestamp)) {
        return (string) \Ulid\Ulid::fromTimestamp($timestamp, $lowercased);
    }

    return (string) \Ulid\Ulid::generate($lowercased);
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
 * @return void
 */
function sendEmail(string $from = '', string $to, string $subject, $bodyText = '', $bodyHtml = '', array $attachment = [], array $image = []): void
{
    $email = (new \App\Core\Mailer\Email());
    $email->prepareData($from, $to, $subject, $bodyText, $bodyHtml, $attachment, $image);
    $email->send();
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
    } catch (JsonException) {
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

    $keys = explode('.', $key);
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
 * slug function
 *
 * @param  [string] $title
 * @param  string $separator
 * @param  string $language
 * @param  array  $dictionary
 *
 * @return void
 */
function slug($title, $separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
{
    // Convert all dashes/underscores into separator
    $flip = $separator === '-' ? '_' : '-';

    $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

    // Replace dictionary words
    foreach ($dictionary as $key => $value) {
        $dictionary[$key] = $separator . $value . $separator;
    }

    $title = str_replace(array_keys($dictionary), array_values($dictionary), $title);

    // Remove all characters that are not the separator, letters, numbers, or whitespace
    $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', strtolower($title));

    // Replace all separator characters and whitespace by a single separator
    $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

    return trim($title, $separator);
}
