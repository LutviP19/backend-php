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

    // if (session_status() == PHP_SESSION_NONE) {
    //     try {
    //         //Starting the session will be the first we do.
    //         ini_set('session.save_handler', env('SESSION_DRIVER', 'files'));
            
    //         if (env('SESSION_DRIVER') === "redis") {
    //             // ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
    //             // ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours

    //             ini_set(
    //                 "session.save_path",
    //                 "tcp://" .
    //                     config("redis.default.host") .
    //                     ":" .
    //                     config("redis.default.port") .
    //                     "?auth" .
    //                     config("redis.default.password"),
    //             );
    //             ini_set("session.gc_maxlifetime", (int) (config("session.lifetime") * 60)); // Set default to 2 hours                
    //         } else {
    //             ini_set('session.save_handler', 'files');
    //             ini_set('session.save_path', storage_path('framework/sessions'));
    //         }            
    //     } catch (\Exception $e) {
    //         $errLog = "An unexpected error occurred: " . $e->getMessage();
    //         write_log('error', $errLog, 'session.save_path.Redis');

    //         // Fallback to default driver
    //         ini_set('session.save_handler', 'files');
    //         ini_set('session.save_path', storage_path('framework/sessions'));
    //     }

    //     session_name('BACKENDPHPSESSID'); // Set a custom session name
    //     // @session_start();
    //     bp_session_start();
    // }

    
    // if (session_status() === PHP_SESSION_ACTIVE) {
    //     // Set Client Identity
    //     \App\Core\Support\Session::set("IPaddress", clientIP());
    //     \App\Core\Support\Session::set("userAgent", $_SERVER["HTTP_USER_AGENT"] ?? "Unknown");

    //      // 1. Jalankan proses regenerasi ID session lama ke baru
    //     if (isset($_SESSION["destroyed"])) {
    //         // $ttl = (int)env('SESSION_REGENERATE', 300);
    //         $ttl = config("session.regenerate");

    //         // $valid = (bool)($_SESSION['destroyed'] < time() - $ttl);
    //         // // dd($ttl);
    //         // dd($valid);

    //         // Do not allow to use too old session ID
    //         if (!empty($_SESSION["destroyed"]) && $_SESSION["destroyed"] < time() - $ttl) {
    //             // Regenerate SessioId
    //             $oldSessionId = session_id();
    //             $headers = bp_session_regenerate_id($oldSessionId);
    //             setHeaders($headers);
    //         }
    //     }
    // }


    if (session_status() === PHP_SESSION_NONE) {
        try {
            $driver = env('SESSION_DRIVER', 'files');
            ini_set('session.save_handler', $driver);
            
            if ($driver === "redis") {
                $redisHost = config("redis.default.host", "127.0.0.1");
                $redisPort = config("redis.default.port", 6379);
                $redisPass = config("redis.default.password");
                $lifetime  = (int) config("session.lifetime", 120) * 60;

                // PERBAIKAN: Format query string redis harus menggunakan "?auth=password" 
                // dan hanya ditambahkan JIKA password tidak kosong.
                $redisPath = "tcp://{$redisHost}:{$redisPort}";
                if (!is_null($redisPass) && $redisPass !== '') {
                    $redisPath .= "?auth=" . urlencode((string)$redisPass);
                }

                ini_set("session.save_path", $redisPath);
                ini_set("session.gc_maxlifetime", $lifetime);               
            } else {
                ini_set('session.save_handler', 'files');
                ini_set('session.save_path', storage_path('framework/sessions'));
            }            
        } catch (\Exception $e) {
            $errLog = "An unexpected error occurred during session init: " . $e->getMessage();
            if (function_exists('write_log')) {
                write_log('error', $errLog, 'session.save_path.Setup');
            }

            // Fallback aman ke driver files jika Redis bermasalah
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', storage_path('framework/sessions'));
        }

        // Set nama session custom
        session_name('BACKENDPHPSESSID'); 
        
        // Jalankan fungsi pembuka session bawaan helper yang sudah dioptimasi
        if (function_exists('bp_session_start')) {
            bp_session_start();
        } else {
            ini_set('session.use_strict_mode', 1);
            @session_start();
        }
    }

    // Handler setelah session berhasil aktif
    if (session_status() === PHP_SESSION_ACTIVE) {
        
        // OPTIMASI: Hanya set IP dan User Agent jika datanya BELUM ADA di session, 
        // untuk menghemat operasi I/O baca/tulis ke Files/Redis.
        if (!\App\Core\Support\Session::has("IPaddress")) {
            \App\Core\Support\Session::set("IPaddress", clientIP());
        }
        
        if (!\App\Core\Support\Session::has("userAgent")) {
            $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "Unknown";
            \App\Core\Support\Session::set("userAgent", $userAgent);
        }

        /* |--------------------------------------------------------------------------
        | LOGIKA REGENERASI DIAPUS / DIKOMENTARI DI SINI
        |--------------------------------------------------------------------------
        | Mengapa? Karena logika pengecekan `$_SESSION['destroyed']` dan penembakan 
        | `bp_session_regenerate_id()` SUDAH otomatis dijalankan di dalam fungsi 
        | `bp_session_start()` di atas. Memasang ulang di sini akan memicu bug ganda.
        |
        */
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