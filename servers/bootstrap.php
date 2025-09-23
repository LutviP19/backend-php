<?php
define('APP_START', microtime(true));
define('BASEPATH', __DIR__ . '/..');

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Bootstrap the Application.
 */
/* ----------------------------- Default settings START -------------------------------- */
// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/..');
$dotenv->load();

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

//Starting the session will be the first we do.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_handler', env('SESSION_DRIVER', 'file'));
    if (env('SESSION_DRIVER') === "redis") {
        ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
        ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
    } else {
        ini_set('session.save_path', __DIR__ . '/../storage/framework/sessions');
    }

    session_name('BACKENDPHPSESSID'); // Set a custom session name
    session_start();
}
/* ----------------------------- Default settings END -------------------------------- */
