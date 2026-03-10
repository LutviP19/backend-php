<?php

declare(strict_types=1);

// This is a RouteCollector for Api Server, separated file to dispact FastRoute dispacher
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
            return  (new \App\Controllers\ServerApi\Auth\AuthController())->loginAction($request, getRequestData($request));
        });
    });

    // User Controller
    $r->addGroup('/user', function (RouteCollector $r) {

        $r->addRoute(['GET','POST'], '/index', function ($request) {
            return  (new \App\Controllers\ServerApi\User\UserController())->indexAction($request, getRequestData($request));
        });

        $r->addRoute(['POST'], '/uptoken', function ($request) {
            return  (new \App\Controllers\ServerApi\User\UserController())->updateTokenAction($request, getRequestData($request));
        });

        $r->addRoute(['GET','POST'], '/logout', function ($request) {
            return  (new \App\Controllers\ServerApi\User\UserController())->logoutAction($request, getRequestData($request));
        });
    });

    // Version 1.0
    $r->addGroup('/api/v1.0', function (RouteCollector $r) {

        // Client
        $r->addGroup('/client', function (RouteCollector $r) {

            $r->addRoute(['GET','POST'], '/index', function ($request) {
                return  (new \App\Controllers\ServerApi\v1\ClientController())->indexAction($request, getRequestData($request));
            });
        });
    });
});
