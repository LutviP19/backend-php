<?php

/**
 * Global helpers.
 */
use App\Core\Http\Request;
use App\Core\Security\{Hash,CSRF};
use App\Core\Validation\MessageBag;
use App\Core\Support\{Session,App};
use App\Core\Support\Config;

/**
 * get environment varible.
 * 
 * @param array $data
 * @return void
 */
function env($key, $alt='') 
{
    return isset($_ENV[$key]) ? $_ENV[$key] : $alt;
}

function config($key) 
{
    return Config::get($key);
}

function database_path($db_name)
{
    return BASE_PATH . 'storage/database/'.$db_name;
}

/**
 * dump the data and kill the page.
 * 
 * @param array $data
 * @return void
 */
function dd($data = [])
{
    echo "<pre>",var_dump($data),"</pre>";
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
    if(strpos($uri,'/') == 0) $uri = ltrim($uri,'/');
    
    return filter_var(
        $uri, FILTER_SANITIZE_URL
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
function e($str)
{
    return htmlentities($str,ENT_QUOTES,'UTF-8');
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
function flash($key,$value = null)
{
    return Session::flash($key,$value);
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

function clientIP()
{
    return  (new \App\Core\Security\Middleware\EnsureIpIsValid)->ip();
}

function matchEncryptedData($value, $encryptedData, $key = null) 
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->match($value, $encryptedData);
    }
    catch(Throwable $ex) {
        if(config('app.debug')) {
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

function encryptData($value, $key = null) 
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->encrypt($value);
    }
    catch(Throwable $ex) {
        if(config('app.debug')) {
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

function decryptData($value, $key = null) 
{
    try {
        $encryption = new \App\Core\Security\Encryption($key);
        return $encryption->decrypt($value);
    }
    catch(Throwable $ex) {
        if(config('app.debug')) {
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

function generateRandomString($len = 60)
{
    return \App\Core\Security\Hash::randomString($len);
}

function isJson($value)
{
    if (! is_string($value)) {
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

