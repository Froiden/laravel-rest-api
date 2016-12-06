<?php

namespace Froiden\RestAPI\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiMiddleware
{

    public function handle($request, Closure $next)
    {
        // Add CORS headers
        $response = $next($request);

        if (config("api.cors") && !$response instanceof StreamedResponse) {
            $response->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', implode(',', config('api.cors_headers')));
        }

        return $response;
    }
}