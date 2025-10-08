<?php


// use OpenSwoole\Coroutine\run;
// use OpenSwoole\Coroutine\go;
// use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Http\Client;

test('example', function () {
    expect(true)->toBeTrue();
});

test('the api server is running', function () {
    // Start a coroutine for the client to interact with the Swoole server

    $statusCode;
    co::run(function () use (&$statusCode) {

        $client = new Client('127.0.0.1', 8080);
        $client->get('/health');

        $statusCode  = $client->getStatusCode();

        // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.ApiServer.$statusCode');
        $client->close();

        return;
    });

    // Assertions for the HTTP response
    // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.ApiServer.$statusCode');
    expect($statusCode)->toBe(200);
});

test('the web server is running', function () {
    // Start a coroutine for the client to interact with the Swoole server

    $statusCode;
    co::run(function () use (&$statusCode) {

        $client = new Client('127.0.0.1', 8008);
        $client->get('/');

        $statusCode  = $client->getStatusCode();

        // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.WebServer.$statusCode');
        $client->close();

        return;
    });

    // Assertions for the HTTP response
    // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.WebServer.$statusCode');
    expect($statusCode)->toBe(200);
});

test('the http server is running', function () {
    // Start a coroutine for the client to interact with the Swoole server

    $statusCode;
    co::run(function () use (&$statusCode) {

        $client = new Client('127.0.0.1', 9501);
        $client->get('/health');

        $statusCode  = $client->getStatusCode();

        // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.HttpServer.$statusCode');
        $client->close();

        return;
    });

    // Assertions for the HTTP response
    // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.HttpServer.$statusCode');
    expect($statusCode)->toBe(200);
});

// test('the web socket server is running', function () {
//     // Start a coroutine for the client to interact with the Swoole server

//     $statusCode;
//     co::run(function () use (&$statusCode) {

//         $client = new Client('127.0.0.1', 9502);
//         $client->get('/');

//         $statusCode  = $client->getStatusCode();

//         // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.WebSocketServer.$statusCode');
//         $client->close();

//         return;
//     });

//     // Assertions for the HTTP response
//     // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.WebSocketServer.$statusCode');
//     expect($statusCode)->toBe(200);
// });
