<?php

/**
 * Init the Application
 * @author Lutvi <lutvip19@gmail.com>
 */

use App\Core\Support\App;

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../..');
}

/* ----------------------------- Default settings START -------------------------------- */

// prettify the errors.
ini_set("html_errors", 1);
ini_set("error_prepend_string", "<pre style='color: #333; font-face:monospace; font-size:14px;'>");
ini_set("error_append_string ", "</pre>");

// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();

//register configuration to the app.
App::register('config', require __DIR__ . '/../../config/app.php');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// dd(config('app.ignore_port'), true);
// if ($_SERVER['SERVER_PORT'] !== 9501) { // Ignore OpenSwoole Server
if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // Ignore OpenSwoole Server

    // if (session_status() == PHP_SESSION_NONE) {
    //     //Starting the session will be the first we do.
    //     ini_set('session.save_handler', env('SESSION_DRIVER', 'file'));
    //     if (env('SESSION_DRIVER') === "redis") {
    //         ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
    //         ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
    //     } else {
    //         ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
    //     }

    //     session_name('BACKENDPHPSESSID'); // Set a custom session name

    //     session_start();
    // }

    // if (session_status() == PHP_SESSION_NONE) {
    //     $sessionDriver = env('SESSION_DRIVER', 'file');
    
    //     // Check Redis Connection if the driver is redis
    //     if ($sessionDriver === "redis") {
    //         try {
    //             $redis = new Redis();
    //             $connected = $redis->connect(env('REDIS_HOST'), env('REDIS_PORT'), 2.0); // timeout 2 seconds
                
    //             if ($connected) {
    //                 $redis->auth(env('REDIS_PASSWORD'));
    //                 $redis->ping(); // Check whether the server responds
                    
    //                 // If successful, set Redis configuration
    //                 ini_set('session.save_handler', 'redis');
    //                 ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth=" . env('REDIS_PASSWORD'));
    //             } else {
    //                 throw new Exception("Redis Connection Failed");
    //             }
    //         } catch (Exception $e) {
    //             // FALLBACK KE FILE
    //             // error_log("Redis Session Error, falling back to file: " . $e->getMessage());
    //             ini_set('session.save_handler', 'files');
    //             ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
    //         }
    //     } else {
    //         // Default ke file
    //         ini_set('session.save_handler', 'files');
    //         ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
    //     }
    
    //     // Set a custom session name
    //     session_name('BACKENDPHPSESSID');
    //     session_start();
    // }

    if (session_status() == PHP_SESSION_NONE) {
        $driver = env('SESSION_DRIVER', 'files');
        $useFallback = false;
    
        if ($driver === "redis") {
            try {
                $redis = new \Redis();
                // Short timeout (2 seconds) so the application doesn't hang if Redis dies
                $connected = @$redis->connect(env('REDIS_HOST'), env('REDIS_PORT'), 2);
                
                if ($connected) {
                    if (env('REDIS_PASSWORD')) {
                        $redis->auth(env('REDIS_PASSWORD'));
                    }
                    // If the connection is successful, set Redis as the handler
                    ini_set('session.save_handler', 'redis');
                    ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth=" . env('REDIS_PASSWORD'));
                } else {
                    $useFallback = true;
                }
            } catch (\Exception $e) {
                $useFallback = true;
            }
        } else {
            $useFallback = true;
        }
    
        // If the driver is not redis OR redis fails to connect
        if ($useFallback) {
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
        }
    
        ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
        session_name('BACKENDPHPSESSID'); // Set a custom session name
        
        // Start the session with error control so as not to disrupt the output
        @session_start();
    }

} else {

    // Set session from cache
    if (isset($_SESSION['uid'])) {
        $_SESSION = array_merge($_SESSION, cacheContent('get', $_SESSION['uid'] .'-'. $_COOKIE[session_name()]) ?: []);
    }
}



/* ----------------------------- Default settings END -------------------------------- */

/**
 * Bootstrap the Application
 */
use App\Core\Http\Request;
use App\Core\Http\Router;
use App\Core\Support\Session;
use App\Core\Validation\MessageBag;


/**
 * Register MessageBag with all the validation errors
 * from session to the App container/registry so we
 * can use them later.
 */
$messageBag = new MessageBag(new Session());
$messageBag->setMessages(Session::set('errors', null));
App::register('errors', $messageBag);

//Call the appropriate route.
$output = Router::load(__DIR__ . '/../../routes/routes.php')
            ->dispatch(Request::uri(), Request::method());

//For requests that expect json results.
if (Request::isJsonRequest() && is_string($output)) {
    echo $output;
}

/**
 * We need to call this method after we return the output
 * and that way we can save the current uri and use it in
 * the next request as the previous uri.
 */
if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { 
    Session::setPreviousUri(Request::uri());
}