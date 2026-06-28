<?php

/**
 * Init the Application
 * @author Lutvi <lutvip19@gmail.com>
 */

use App\Core\Support\App;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionException;

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../..');
}

/* ----------------------------- Default settings START -------------------------------- */

// only level Deprecated & User Deprecated
// error_reporting(E_DEPRECATED | E_USER_DEPRECATED);
error_reporting(E_ALL);
ini_set("display_errors", 1);

// prettify the errors.
ini_set("html_errors", 1);
ini_set("error_prepend_string", "<pre style='color: #333; font-face:monospace; font-size:14px;'>");
ini_set("error_append_string ", "</pre>");

// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();

//register configuration to the app.
App::register('config', require __DIR__ . '/../../config/app.php');

// Min php version
bp_minimum_php_version(config('app.php_version'));

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// dd(config('app.ignore_port'), true);
if (! \in_array($_SERVER['SERVER_PORT'], config('app.ignore_port'))) { // Ignore OpenSwoole Server

    if (session_status() == PHP_SESSION_NONE) {
        try {
            //Starting the session will be the first we do.
            ini_set('session.save_handler', env('SESSION_DRIVER', 'files'));
            
            if (env('SESSION_DRIVER') === "redis") {
                // ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
                // ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours

                ini_set(
                    "session.save_path",
                    "tcp://" .
                        config("redis.default.host") .
                        ":" .
                        config("redis.default.port") .
                        "?auth" .
                        config("redis.default.password"),
                );
                ini_set("session.gc_maxlifetime", (int) (config("session.lifetime") * 60)); // Set default to 2 hours                
            } else {
                ini_set('session.save_handler', 'files');
                ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
            }            
        } catch (\Exception $e) {
            $errLog = "An unexpected error occurred: " . $e->getMessage();
            write_log('error', $errLog, 'session.save_path.Redis');

            // Fallback to default driver
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
        }

        session_name('BACKENDPHPSESSID'); // Set a custom session name
        // @session_start();
        bp_session_start();
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