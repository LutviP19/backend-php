<?php

/**
 * Here you can register all of your application routes.
 * 
 * Syntax GET: $router->get('uri','Controller@method');
 * Dynamic route: $router->method('uri/with/{dynamicvalue}','Controller@method');
 * Available router methods : get(), post(), put(), delete().
 */

//========== API - v1
$router->get('/webhook', 'Api\v1\WebhookController@index');
$router->post('/webhook', 'Api\v1\WebhookController@index');

$router->post('/auth/login', 'Api\v1\Auth\AuthController@login');
$router->post('/auth/uptoken', 'Api\v1\Auth\AuthController@updateToken');
$router->post('/auth/logout', 'Api\v1\Auth\AuthController@logout');

$router->get('/client/profile', 'Api\v1\ClientController@profile');
//========== END API - v1


// WEB
$router->get('/', 'PagesController@index');
$router->get('/contact', 'PagesController@contact');
$router->get('/about', 'PagesController@about');
$router->get('/extra', 'PagesController@extra');
