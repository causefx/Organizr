<?php

namespace Buzz\Exception;

use Buzz\Message\RequestInterface;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;

class RequestException extends ClientException
{
    /**
     * Request object
     *
     * @var RequestInterface|Psr7RequestInterface
     */
    private $request;

    /**
     * @return RequestInterface|Psr7RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RequestInterface|Psr7RequestInterface $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

}