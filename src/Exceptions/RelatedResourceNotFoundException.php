<?php

namespace Froiden\RestAPI\Exceptions;

class RelatedResourceNotFoundException extends ApiException
{
    protected $statusCode = 422;

    protected $code = ErrorCodes::VALIDATION_EXCEPTION;

    protected $innercode = ErrorCodes::INNER_RELATED_RESOURCE_NOT_EXISTS;

    protected $message = "Related resource not found";
}