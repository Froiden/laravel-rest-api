<?php

namespace Froiden\RestAPI\Exceptions;

use Illuminate\Contracts\Support\Jsonable;

class ApiException extends \Exception implements \JsonSerializable, Jsonable
{
    /**
     * Response status code
     *
     * @var int
     */
    protected $statusCode = 400;

    /**
     * Error code
     *
     * @var int
     */
    protected $code = ErrorCodes::UNKNOWN_EXCEPTION;

    /**
     * Error message
     *
     * @var string
     */
    protected $message = "An unknown error occurred";

    public function __construct($message = null, $previous = null, $code = null, $statusCode = null, $innerError = null, $details = [])
    {
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        if ($code !== null) {
            $this->code = $code;
        }

        if ($innerError !== null) {
            $this->innerError = $innerError;
        }

        if (!empty($details)) {
            $this->details = $details;
        }

        if ($message == null) {
            parent::__construct($this->message, $this->code, $previous);
        }
        else {
            parent::__construct($message, $this->code, $previous);
        }
    }

    public function __toString()
    {
        return "ApiException (#{$this->getCode()}): {$this->getMessage()}";
    }

    /**
     * Return the status code the response should be sent with
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Convert the exception to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $jsonArray = [
            "error" => [
                "message" => $this->getMessage(),
                "code" => $this->getCode()
            ]
        ];

        if (isset($this->details)) {
            $jsonArray["error"]["details"] = $this->details;
        }

        if (isset($this->innerError)) {
            $jsonArray["error"]["innererror"] = [
                "code" => $this->innerError
            ];
        }

        return $jsonArray;
    }
}