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
     *@author Lutvi <lutvip19@gmail.com>
     *
     * @return void
     */
    public function handle()
    {
        $authenticatedKey = ['uid', 'email', 'client_token', 'current_team_id'];
        $status = array_keys_exists($authenticatedKey, Session::all());

        // Invalid session data
        if ($status === false) {
            return stopHere(
                [
                    'status' => false,
                    'statusCode' => 401,
                    'message' => 'Please login!',
                    'errors' => ['auth' => 'Session expired!', 'ip' => clientIP()]
                ],
                401);
        }
    }
}
