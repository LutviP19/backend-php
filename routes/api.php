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

// Testing
$router->post('/api/index', 'Api\TestingController@index');
$router->post('/api/queue', 'Api\TestingController@queue');
// Testing - FCM
$router->post('/api/save-fcm-token/{regId}', 'Api\TestingController@saveFcmToken');
$router->post('/api/test-fcm-token/{regId}', 'Api\TestingController@testFcmToken');
// Testing - AI
$router->post('/api/test-neuronai/prompt', 'Api\TestingController@neuronAi');

// // FCM - Setup Notification
// $router->post('/api/save-fcm-token/{regId}', 'Api\v1\Registration\FcmController@saveFcmToken');
// $router->post('/api/test-fcm-token/{regId}', 'Api\v1\Registration\FcmController@testFcmToken');

// // Registration Customer
// $router->post('/api/v1/reg/customer/step1', 'Api\v1\Registration\RegCustomerController@step1');
// $router->post('/api/v1/reg/customer/step2/{regId}', 'Api\v1\Registration\RegCustomerController@step2');
// $router->post('/api/v1/reg/customer/step3/{regId}', 'Api\v1\Registration\RegCustomerController@step3');
// $router->post('/api/v1/reg/customer/step4/{regId}', 'Api\v1\Registration\RegCustomerController@step4');
// $router->post('/api/v1/reg/customer/step5/{regId}', 'Api\v1\Registration\RegCustomerController@step5');
// $router->post('/api/v1/reg/customer/finish/{regId}', 'Api\v1\Registration\RegCustomerController@regFinish');

// // Registration Driver
// $router->post('/api/v1/reg/driver/step1', 'Api\v1\Registration\RegDriverController@step1');
// $router->post('/api/v1/reg/driver/step2/{regId}', 'Api\v1\Registration\RegDriverController@step2');
// $router->post('/api/v1/reg/driver/step3/{regId}', 'Api\v1\Registration\RegDriverController@step3');
// $router->post('/api/v1/reg/driver/step4/{regId}', 'Api\v1\Registration\RegDriverController@step4');
// $router->post('/api/v1/reg/driver/step5/{regId}', 'Api\v1\Registration\RegDriverController@step5');
// $router->post('/api/v1/reg/driver/finish/{regId}', 'Api\v1\Registration\RegDriverController@regFinish');


//========== END API - v1
