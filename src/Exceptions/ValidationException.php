<?php

namespace Froiden\RestAPI\Exceptions;

class ValidationException extends ApiException
{

    protected $statusCode = 422;

    protected $code = ErrorCodes::VALIDATION_EXCEPTION;

    protected $message = "Request could not be validated";

    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];

    public function __construct($errors = [])
    {
        parent::__construct();

        $this->details = $errors;
    }
}