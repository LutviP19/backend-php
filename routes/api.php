<?php

/**
 * Here you can register all of your application routes.
 *
 * Syntax GET: $router->get('uri','Controller@method');
 * Dynamic route: $router->method('uri/with/{dynamicvalue}','Controller@method');
 * Available router methods : get(), post(), put(), delete().
 */

//========== API - v1
$router->get('/api/v1/webhook', 'Api\v1\WebhookController@index');
$router->post('/api/v1/webhook', 'Api\v1\WebhookController@index');

$router->get('/api/v1/client/profile', 'Api\v1\ClientController@profile');
//========== END API - v1
