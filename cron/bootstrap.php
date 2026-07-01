<?php 

if (!defined('APP_START')) {
    define('APP_START', microtime(true));
}

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/..');
}

if (!defined("BASEPATH_FFI")) {
    define("BASEPATH_FFI", BASEPATH . "/bin/ffi");
}

// only level Deprecated & User Deprecated
error_reporting(E_DEPRECATED | E_USER_DEPRECATED);
// error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/**
 * Require the composer autoload File.
 */
require_once BASEPATH . '/vendor/autoload.php';

/**
 * Bootstrap the Application.
 * @author Lutvi <lutvip19@gmail.com>
 */
/* ----------------------------- Default settings START -------------------------------- */
// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/..');
$dotenv->load();

// Register the configuration to the application.
use App\Core\Support\App;
App::register('config', require BASEPATH . '/config/app.php');
App::register("routing_external_api", require BASEPATH . "/routes/external-api.php");

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

