<?php

/**
 * Here you can register all of your application routes.
 *
 * Syntax GET: $router->get('uri','Controller@method');
 * Dynamic route: $router->method('uri/with/{dynamicvalue}','Controller@method');
 * Available router methods : get(), post(), put(), delete().
 */

// Auth
require_once(__DIR__ . '/auth.php');

// API
require_once(__DIR__ . '/api.php');

// WEB
require_once(__DIR__ . '/web.php');
