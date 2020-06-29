<?php

namespace Froiden\RestAPI\Handlers;

use App\Exceptions\Handler;
use Froiden\RestAPI\ApiResponse;
use Froiden\RestAPI\Exceptions\ApiException;
use Froiden\RestAPI\Exceptions\Parse\UnknownFieldException;
use Froiden\RestAPI\Exceptions\UnauthorizedException;
use Froiden\RestAPI\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler extends Handler
{

    public function render($request, Throwable $e)
    {
        $debug = config('app.debug');
        $prefix = config("api.prefix");
        // Check if prefix is set and use that debug
        // This is done to prevent default error message show in otherwise application
        // which are not using the api
        if (!$debug && $request->is($prefix.'/*')) {
            if ($e instanceof HttpResponseException || $e instanceof \Illuminate\Validation\ValidationException) {
                if ($e->status == 403) {
                    return ApiResponse::exception(new UnauthorizedException());
                }
                else {
                    return ApiResponse::exception(new ValidationException($e->errors()));
                }
            }
            else if ($e instanceof NotFoundHttpException) {
                return ApiResponse::exception(new ApiException('This api endpoint does not exist', null, 404, 404, 2005, [
                        'url' => request()->url()
                    ]));
            }
            else if ($e instanceof ModelNotFoundException) {
                return ApiResponse::exception(new ApiException('Requested resource not found', null, 404, 404, null, [
                        'url' => request()->url()
                    ]));
            }
            else if ($e instanceof ApiException) {
                return ApiResponse::exception($e);
            }
            else if ($e instanceof QueryException) {
                if ($e->getCode() == "422") {
                    preg_match("/Unknown column \\'([^']+)\\'/", $e->getMessage(), $result);

                    if (!isset($result[1])) {
                        return ApiResponse::exception(new UnknownFieldException(null, $e));
                    }
                    else {
                        $parts = explode(".", $result[1]);

                        if (count($parts) > 1) {
                            return ApiResponse::exception(new UnknownFieldException("Field '" . $parts[1] . "' does not exist", $e));
                        }
                        else {
                            return ApiResponse::exception(new UnknownFieldException("Field '" . $result . "' does not exist", $e));
                        }
                    }
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
