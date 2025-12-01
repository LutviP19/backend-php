<?php

// Blocked boot agent
$blocked_agents = [
    'python-httpx',
    'go-http-client',
];

$user_agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
// die($user_agent);

foreach ($blocked_agents as $agent) { 
    if (str_contains(strtolower($user_agent), strtolower($agent))) {
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied.');
    }
}
// END Blocked boot agent


if (!defined('APP_START')) {
    define('APP_START', microtime(true));
}

/**
 * Require the composer autoload File.
 */
require_once __DIR__.'/../vendor/autoload.php';

/**
 * Bootstrap the Application.
 */
require_once __DIR__.'/../app/Core/init.php';
