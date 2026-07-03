<?php

namespace Ddys\ThinkPHP\Exception;

use RuntimeException;

class DdysException extends RuntimeException
{
    protected $status;
    protected $method;
    protected $endpoint;
    protected $response;

    public function __construct($message, $status = 0, $method = 'GET', $endpoint = '', $response = null)
    {
        parent::__construct((string) $message, (int) $status);
        $this->status = (int) $status;
        $this->method = strtoupper((string) $method);
        $this->endpoint = (string) $endpoint;
        $this->response = $response;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
