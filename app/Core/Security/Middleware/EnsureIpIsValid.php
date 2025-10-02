<?php

namespace App\Core\Security\Middleware;

use App\Core\Http\{Request,Response};
use App\Core\Support\Config;

/**
 * EnsureIpIsValid class
 * @author Lutvi <lutvip19@gmail.com>
 */
class EnsureIpIsValid
{
    /**
     * Handle an incoming request.
     *@author Lutvi <lutvip19@gmail.com>
     *
     * @return void
     */
    public function handle()
    {
        // dd(clientIP());
        if (! in_array(clientIP(), Config::get('trusted_ips'))) {
            return stopHere([], 500);
        }

        return;
    }
}
