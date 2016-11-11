<?php

namespace Froiden\RestAPI\Middleware;

use Closure;

class ApiMiddleware
{

    public function handle($request, Closure $next)
    {
        // Add CORS headers
        $response = $next($request);

        if (config("api.cors")) {
            $response->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-SNAPHRM-HOST');
        }

        return $response;
    }
}