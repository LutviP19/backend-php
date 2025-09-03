<?php

/**
 * Here you can register all of your application routes.
 * 
 * Syntax GET: $router->get('uri','Controller@method');
 * Dynamic route: $router->method('uri/with/{dynamicvalue}','Controller@method');
 * Available router methods : get(), post(), put(), delete().
 */

 // API - v1
 $router->get('/webhook','Api\v1\WebhookController@index');

 // WEB
$router->get('/','PagesController@index');
$router->get('/contact','PagesController@contact');
$router->get('/about','PagesController@about');
$router->get('/extra','PagesController@extra');