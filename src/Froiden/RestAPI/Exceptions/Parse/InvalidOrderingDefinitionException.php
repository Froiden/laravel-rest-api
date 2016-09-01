<?php

namespace Froiden\RestAPI\Exceptions\Parse;

use Froiden\RestAPI\Exceptions\ApiException;
use Froiden\RestAPI\Exceptions\ErrorCodes;

class InvalidOrderingDefinitionException extends ApiException
{
    protected $code = ErrorCodes::REQUEST_PARSE_EXCEPTION;

    protected $innerError = ErrorCodes::INNER_ORDERING_INVALID;

    protected $message = "Ordering defined incorrectly";
}