<?php

/**
 * Init the Application
 * @author Lutvi <lutvip19@gmail.com>
 */

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

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

if ($_SERVER['SERVER_PORT'] !== 9501) { // Ignore OpenSwoole Server

    if (session_status() == PHP_SESSION_NONE) {
        //Starting the session will be the first we do.
        ini_set('session.save_handler', env('SESSION_DRIVER', 'file'));
        if (env('SESSION_DRIVER') === "redis") {
            ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
            ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
        } else {
            ini_set('session.save_path', __DIR__ . '/../../storage/framework/sessions');
        }

        session_name('BACKENDPHPSESSID'); // Set a custom session name

        session_start();
    }

    // if(isset($_COOKIE['BACKENDPHPSESSID'])){
    //     session_id($_COOKIE['BACKENDPHPSESSID']);
    //     // var_dump($_COOKIE);
    // }

    // \App\Core\Support\Log::debug($_SERVER, 'init.$_SERVER');
    // \App\Core\Support\Log::debug(session_id(), 'init.session_id()');
} else {

    // if ((session_status()) == PHP_SESSION_NONE  && $_SERVER["REMOTE_ADDR"] != 'host.docker.internal') {
    //     // You might call session_start() here if needed
    //     session_start();
    //     // session_regenerate_id(true);
    // }

}



/* ----------------------------- Default settings END -------------------------------- */

/**
 * Bootstrap the Application
 */

use App\Core\Http\Request;
use App\Core\Http\Router;
use App\Core\Support\App;
use App\Core\Support\Session;
use App\Core\Validation\MessageBag;

//register configuration to the app.
App::register('config', require __DIR__ . '/../../config/app.php');

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
Session::setPreviousUri(Request::uri());
