<?php

namespace Froiden\RestAPI\Exceptions;

class ResourceNotFoundException extends ApiException
{
    protected $statusCode = 404;

    protected $code = ErrorCodes::RESOURCE_NOT_FOUND_EXCEPTION;

    protected $message = "Requested resource not found";
}