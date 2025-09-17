<?php

namespace App\Core\Security\Middleware;

use App\Core\Http\{Request,Response};
use App\Core\Support\Config;

class EnsureIpIsValid
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
        // dd(clientIP());
        if(!in_array(clientIP(), Config::get('trusted_ips'))) {
            die($response->json([], 500));
        }

        return $response;
    }
}
