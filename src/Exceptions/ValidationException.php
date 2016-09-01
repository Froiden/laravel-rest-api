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

        $this->errors = $errors;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $jsonArray = [
            "error" => [
                "message" => $this->getMessage(),
                "code" => $this->getCode(),
                "details" => $this->errors
            ]
        ];
    }
}