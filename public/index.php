<?php

if (!defined('APP_START')) define('APP_START', microtime(true));

/**
 * Require the composer autoload File.
 */
require_once __DIR__.'/../vendor/autoload.php';
ob_start();
/**
 * Bootstrap the Application.
 */
require_once __DIR__.'/../app/Core/init.php';
ob_end_flush();