<?php

define('APP_START', microtime(true));
define('BASEPATH', __DIR__ . '/..');

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Bootstrap the Application.
 * @author Lutvi <lutvip19@gmail.com>
 */
/* ----------------------------- Default settings START -------------------------------- */
// Looking for .env at the root directory
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__.'/..');
$dotenv->load();

//register configuration to the app.
\App\Core\Support\App::register('config', require __DIR__ . '/../config/app.php');

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

//Starting the session will be the first we do.
ini_set('session.save_handler', env('SESSION_DRIVER', 'file'));
if (env('SESSION_DRIVER') === "redis") {
    ini_set('session.save_path', "tcp://" . env('REDIS_HOST') . ":" . env('REDIS_PORT') . "?auth" . env('REDIS_PASSWORD'));
    ini_set('session.gc_maxlifetime', (env('SESSION_LIFETIME', 120) * 60)); // Set default to 2 hours
} else {
    ini_set('session.save_path', __DIR__ . '/../storage/framework/sessions');
}

session_name('BACKENDPHPSESSID'); // Set a custom session name


// Make sure use_strict_mode is enabled.
// use_strict_mode is mandatory for security reasons.
ini_set('session.use_strict_mode', 1);

// if (session_status() == PHP_SESSION_NONE) {
//     custom_session_start();
// }

// $sessID = custom_session_regenerate_id();
// Write useful codes
/* ----------------------------- Default settings END -------------------------------- */

$serverip = "127.0.0.1";
$serverport = 8080;
$sessID = '';

// Modified from Upscale\Swoole\Session\SessionDecorator;
class SessionDecorator
{
    /**
     * @var callable
     */
    protected $middleware;

    /**
     * @var callable
     */
    protected $idGenerator;

    protected bool $useCookies;

    protected bool $useOnlyCookies;

    /**
     * Inject dependencies
     *
     * @param callable $middleware function (\OpenSwoole\Http\Request $request, \OpenSwoole\Http\Response)
     * @param callable $idGenerator
     * @param bool|null $useCookies
     * @param bool|null $useOnlyCookies
     */
    public function __construct(
        callable $middleware,
        $idGenerator = 'session_create_id',
        ?bool $useCookies = null,
        ?bool $useOnlyCookies = null
    ) {
        $this->middleware = $middleware;
        $this->idGenerator = $idGenerator;
        $this->useCookies = is_null($useCookies) ? (bool)ini_get('session.use_cookies') : $useCookies;
        $this->useOnlyCookies = is_null($useOnlyCookies) ? (bool)ini_get('session.use_only_cookies') : $useOnlyCookies;
    }

    /**
     * Delegate execution to the underlying middleware wrapping it into the session start/stop calls
     */
    public function __invoke(\OpenSwoole\Http\Request $request, \OpenSwoole\Http\Response $response)
    {
        $sessionName = session_name();
        if ($this->useCookies && isset($request->cookie[$sessionName])) {
            $sessionId = $request->cookie[$sessionName];
        } else if (!$this->useOnlyCookies && isset($request->get[$sessionName])) {
            $sessionId = $request->get[$sessionName];
        } else {
            $sessionId = call_user_func($this->idGenerator);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id($sessionId);
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($this->useCookies) {
            $cookie = session_get_cookie_params();
            $response->cookie(
                $sessionName,
                $sessionId,
                $cookie['lifetime'] ? time() + $cookie['lifetime'] : 0,
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        try {
            call_user_func($this->middleware, $request, $response);
        } finally {
            session_write_close();
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_id('');
            }
            $_SESSION = [];
            unset($_SESSION);
        }
    }
}

function initializeServerConstant(\OpenSwoole\Http\Request $request): void
{
    global $serverip, $serverport;

    $_SERVER = [];
    $uri = $request->server["request_uri"] ?? "/";
    $requestip = $request->server["remote_addr"] ?? clientIP();

    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    $_SERVER['SERVER_NAME'] = $serverip;
    $_SERVER['SERVER_PORT'] = $serverport;
    $_SERVER['SERVER_SOFTWARE'] = "Backend PHP";
    $_SERVER['SERVER_PROTOCOL'] = isset($request->server['server_protocol']) ? $request->server['server_protocol'] : null;
    $_SERVER['HTTP_HOST'] = $serverip.":".$serverport;
    $_SERVER['HTTP_ACCEPT'] = isset($request->header['accept']) ? $request->header['accept'] : "*";
    $_SERVER['HTTP_USER_AGENT'] = $request->header['user-agent'] ?? null;
    $_SERVER['HTTP_ACCEPT_ENCODING'] = $request->header['accept-encoding'] ?? null;
    $_SERVER['QUERY_STRING'] = isset($request->server['query_string']) ? $request->server['query_string'] : null;
    $_SERVER['PHP_SELF'] = isset($request->server['php_self']) ? $request->server['php_self'] : null;
    $_SERVER['SCRIPT_NAME'] = isset($request->server['script_name']) ? $request->server['script_name'] : null;
    $_SERVER['SCRIPT_FILENAME'] = isset($request->server['script_filename']) ? $request->server['script_filename'] : null;
    $_SERVER['REMOTE_ADDR'] = $requestip;
    $_SERVER['REMOTE_PORT'] = isset($request->server['remote_port']) ? $request->server['remote_port'] : null;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['REQUEST_METHOD'] = $request->server["request_method"];
    $_SERVER['REQUEST_TIME'] = isset($request->server['request_time']) ? $request->server['request_time'] : null;
    $_SERVER['REQUEST_TIME_FLOAT'] = isset($request->server['request_time_float']) ? $request->server['request_time_float'] : null;

    foreach ($request->server as $key => $value) {
        $_SERVER[strtoupper($key)] = $value;
    }

    $_REQUEST = [];
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    $_FILES = $request->files ?? [];
    $_COOKIE = $request->cookie ?? [];

    $_REQUEST = array_merge($_GET, $_POST);

    foreach ($request->header as $key => $value) {
        $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
    }

    if (isset($request->header['host'])) {
        $_SERVER['HTTP_HOST'] = $request->header['host'];
    }

    if (isset($request->cookie)) {
        foreach ($request->cookie as $key => $value) {
            $_COOKIE[$key] = $value;
        }
    }
}