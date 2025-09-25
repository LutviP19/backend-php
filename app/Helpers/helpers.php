<?php

/**
 * Global helpers.
 */

use App\Core\Http\Request;
use App\Core\Security\CSRF;
use App\Core\Support\App;
use App\Core\Support\Config;
use App\Core\Support\Session;
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
 * default database path for sqlite
 *
 * @param  [string] $key
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
    return "http://{$_SERVER['HTTP_HOST']}/{$uri}";
}

function assets($uri = '')
{
    $uri = sanitizeUri($uri);
    if ($_SERVER['SERVER_PORT'] === 9501) { // OpenSwoole Server
        return "http://{$_SERVER['HTTP_HOST']}/{$uri}";
    }

    return "http://{$_SERVER['HTTP_HOST']}/{$uri}";
}

/**
 * Get the current url.
 *
 * @return string
 */
function currentUrl()
{
    return url(Request::uri());
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
    return CSRF::generate();
}

/**
 * get the csrf hidden field
 *
 * @return string
 */
function csrfField()
{
    return CSRF::csrfField();
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
    return Session::get($key);
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
    return Session::flash($key, $value);
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
    return Session::getOldInput($key);
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
