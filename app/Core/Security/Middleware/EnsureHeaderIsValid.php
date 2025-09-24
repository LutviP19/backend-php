<?php

namespace App\Core\Security\Middleware;

use App\Core\Http\{Request, Response};
use App\Core\Support\Config;

/**
 * EnsureHeaderIsValid class
 * @author Lutvi <lutvip19@gmail.com>
 */
class EnsureHeaderIsValid
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
        $headers = $request->headers();

        $status = array_keys_exists(Config::get('valid_headers'), $headers);

        // Specific Header
        if (!isset($headers['X-Api-Token'])) {
            die($response->json(
                [
                        'status' => false,
                        'statusCode' => 403,
                        'message' => 'missing token header!',
                    ],
                403
            ));
        }

        // Invalid header
        if ($status === false) {
            die($response->json(
                [
                        'status' => false,
                        'statusCode' => 500,
                        'message' => 'invalid header!',
                    ],
                500
            ));
        }

        return $response;
    }
}
