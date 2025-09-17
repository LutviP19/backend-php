<?php

namespace App\Core\Security\Middleware;

use App\Core\Http\{Request, Response};
use App\Core\Support\Config;

class EnsureHeaderIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  (\App\Core\Http\Request): (\App\Core\Http\Response)
     */
    public function handle(Request $request, Response $response)
    {
        $headers = $request->headers();

        $status = false;
        foreach ($headers as $header => $value) {
            // Check all valid headers
            if (in_array($header, Config::get('valid_headers'))) {
                $status = true;
            }
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

        // Specific Header
        if (!isset($headers['X-Api-Token'])) {
            die($response->json(
                    [
                        'status' => false,
                        'statusCode' => 403,
                        'message' => 'missing token!',
                    ],
                    403
                ));
        }
    }
}
