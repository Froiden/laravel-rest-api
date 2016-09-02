<?php

namespace Froiden\RestAPI\Handlers;

use App\Exceptions\Handler;
use Froiden\RestAPI\ApiResponse;
use Froiden\RestAPI\Exceptions\ApiException;
use Froiden\RestAPI\Exceptions\Parse\UnknownFieldException;
use Froiden\RestAPI\Exceptions\UnauthorizedException;
use Froiden\RestAPI\Exceptions\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exception\HttpResponseException;

class ApiExceptionHandler extends Handler
{

    public function render($request, \Exception $e)
    {
        $debug = env("APP_DEBUG");

        if (!$debug) {
            if ($e instanceof HttpResponseException) {
                if ($e->getResponse()->getStatusCode() == 403) {
                    return ApiResponse::exception(new UnauthorizedException());
                }
                else {
                    return ApiResponse::exception(new ValidationException(json_decode($e->getResponse()->getContent(), true)));
                }
            }
            else if ($e instanceof ApiException) {
                return ApiResponse::exception($e);
            }
            else if ($e instanceof QueryException) {
                if ($e->getCode() == "42S22") {
                    return ApiResponse::exception(new UnknownFieldException(null, $e));
                }
                else {
                    return ApiResponse::exception(new ApiException(null, $e));
                }
            }
            else {
                return ApiResponse::exception(new ApiException(null, $e));
            }
        }
        else {
            return parent::render($request, $e);
        }

    }
}