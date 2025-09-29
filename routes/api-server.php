<?php

// This a router for Api Server, separated file to describe Fast Router dispacher

use FastRoute\RouteCollector;

return \FastRoute\simpleDispatcher(function (RouteCollector $r) {

    // Sample dynamin attribute
    $r->addRoute('GET', '/hello/{name}', function ($request) {
        $name = $request->getAttribute('name');
        $json = json_encode(
            [
                        'message' => $name,
                        'data' => ['users' => [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']]]
                    ]
        );

        return (new Response($json))->withHeaders(["Content-Type" => "application/json"])->withStatus(200);
    });

    // Testing Call Controller
    $r->addRoute(['GET','POST'], '/webhook/{event}', function ($request) {
        // return  (new \App\Controllers\Api\v1\WebhookController())->bpIndex($request, getRequestData($request));
        return  (new \App\Controllers\ServerApi\WebhookController())->indexAction($request, getRequestData($request));
    });

    // Auth Controller
    $r->addGroup('/auth', function (RouteCollector $r) {
        $r->addRoute(['POST'], '/login', function ($request) {
            return  (new \App\Controllers\ServerApi\Auth\AuthController())->login($request, getRequestData($request));
        });
        $r->addRoute(['POST'], '/uptoken', function ($request) {
            return  (new \App\Controllers\ServerApi\Auth\AuthController())->updateToken($request, getRequestData($request));
        });
        $r->addRoute(['GET','POST'], '/logout', function ($request) {
            return  (new \App\Controllers\ServerApi\Auth\AuthController())->logout($request, getRequestData($request));
        });
    });
});