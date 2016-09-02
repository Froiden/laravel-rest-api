<?php

namespace Froiden\RestAPI;

use Froiden\RestAPI\Exceptions\ApiException;

class ApiResponse
{

    /**
     * Response message
     *
     * @var string
     */
    private $message = null;

    /**
     * Data to send in response
     *
     * @var array
     */
    private $data = null;

    /**
     * Get response message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set response message
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get response data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set response data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Make new success response
     * @param string $message
     * @param array $data
     * @return \Response
     */
    public static function make($message = null, $data = null, $meta = null)
    {
        $response = [];

        if (!empty($message)) {
            $response["message"] = $message;
        }

        if ($data !== null && is_array($data)){
            $response["data"] = $data;
        }

        if ($meta !== null && is_array($meta)){
            $response["meta"] = $meta;
        }

        $returnResponse = \Response::make($response);

        return $returnResponse;
    }

    /**
     * Handle api exception an return proper error response
     * @param ApiException $exception
     * @return \Illuminate\Http\Response
     * @throws ApiException
     */
    public static function exception(ApiException $exception)
    {
        $returnResponse = \Response::make($exception->jsonSerialize());

        $returnResponse->setStatusCode($exception->getStatusCode());

        return $returnResponse;
    }
}