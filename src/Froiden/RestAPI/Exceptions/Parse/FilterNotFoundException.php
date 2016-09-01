<?php

namespace Froiden\RestAPI\Exceptions\Parse;

use Froiden\RestAPI\Exceptions\ApiException;
use Froiden\RestAPI\Exceptions\ErrorCodes;

class FilterNotFoundException extends ApiException
{

    protected $code = ErrorCodes::REQUEST_PARSE_EXCEPTION;

    protected $innerError = ErrorCodes::INNER_FILTER_NOT_FOUND;

    protected $message = "Requested filter not found";

}