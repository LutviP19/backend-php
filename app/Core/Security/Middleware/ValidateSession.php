<?php

namespace App\Core\Security\Middleware;

use App\Core\Support\Session;
use App\Core\Http\{Request, Response};

/**
 * ValidateSession class
 * @author Lutvi <lutvip19@gmail.com>
 */
class ValidateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  (\App\Core\Http\Request): (\App\Core\Http\Response)
     *
     * @return \App\Core\Http\Response
     */
    public function handle(Request $request, Response $response): Response
    {
        $authenticatedKey = ['uid', 'email', 'client_token', 'current_team_id'];
        $status = array_keys_exists($authenticatedKey, Session::all());

        // echo "Custom Session ID: " . session_id();
        // dd($status);

        // Invalid session data
        if ($status === false) {
            die($response->json(
                [
                        'status' => false,
                        'statusCode' => 401,
                        'message' => 'invalid credentials!',
                    ],
                401
            ));
        }

        return $response;
    }
}
