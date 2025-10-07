<?php


// use OpenSwoole\Coroutine\run;
// use OpenSwoole\Coroutine\go;
// use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Http\Client;

test('example', function () {
    expect(true)->toBeTrue();
});

test('the web server is running', function () {
    // Start a coroutine for the client to interact with the Swoole server

   $statusCode; 
   co::run(function() use(&$statusCode) { 

        $client = new Client('127.0.0.1', 8008);
        $client->get('/');

        $statusCode  = $client->getStatusCode();

        // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.php.$statusCode');
        $client->close();

        return;
    });

    // Assertions for the HTTP response
    // \App\Core\Support\Log::debug($statusCode, 'tests/Feature/ExampleTest.php.$statusCode');
    expect($statusCode)->toBe(200);
});
