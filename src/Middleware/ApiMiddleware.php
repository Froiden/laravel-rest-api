<?php

namespace Froiden\RestAPI\Middleware;

use Closure;
use Froiden\RestAPI\ApiResponse;
use Froiden\RestAPI\Exceptions\UnauthorizedException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiMiddleware
{

    public function handle($request, Closure $next)
    {
        // Add CORS headers
        $response = $next($request);

        if ($response->getStatusCode() == 403 && ($response->getContent() == "Forbidden" || Str::contains($response->getContent(), ['HttpException', 'authorized']))) {
            $response = ApiResponse::exception(new UnauthorizedException());
        }

        if (config("api.cors") && !$response instanceof StreamedResponse) {
            $response->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', implode(',', config('api.cors_headers')));
        }


        return $response;
    }
}