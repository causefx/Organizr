<?php

namespace Buzz\Client;

use Buzz\Converter\RequestConverter;
use Buzz\Converter\ResponseConverter;
use Buzz\Exception\RequestException;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Exception\LogicException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface as PSR7RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Curl extends AbstractCurl
{
    private $lastCurl;

    /**
     * @param RequestInterface $request
     * @param MessageInterface $response
     * @param array $options
     *
     * @deprecated Will be removed in 1.0. Use sendRequest instead.
     */
    public function send(RequestInterface $request, MessageInterface $response, array $options = array())
    {
        @trigger_error('Curl::send() is deprecated. Use Curl::sendRequest instead.', E_USER_DEPRECATED);
        $request = RequestConverter::psr7($request);
        ResponseConverter::copy(ResponseConverter::buzz($this->sendRequest($request, $options)), $response);
    }

    /**
     * @param PSR7RequestInterface $request
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function sendRequest(PSR7RequestInterface $request, $options = [])
    {
        if (is_resource($this->lastCurl)) {
            curl_close($this->lastCurl);
        }

        $this->lastCurl = static::createCurlHandle();
        $this->prepare($this->lastCurl, $request, $options);

        $data = curl_exec($this->lastCurl);

        if (false === $data) {
            $errorMsg = curl_error($this->lastCurl);
            $errorNo  = curl_errno($this->lastCurl);

            $e = new RequestException($errorMsg, $errorNo);
            $e->setRequest($request);

            throw $e;
        }

        return $this->createResponse($this->lastCurl, $data);
    }

    /**
     * Introspects the last cURL request.
     *
     * @param int $opt
     *
     * @return string|array
     * @throws LogicException
     *
     * @see curl_getinfo()
     *
     * @throws LogicException If there is no cURL resource
     */
    public function getInfo($opt = 0)
    {
        if (!is_resource($this->lastCurl)) {
            throw new LogicException('There is no cURL resource');
        }

        return 0 === $opt ? curl_getinfo($this->lastCurl) : curl_getinfo($this->lastCurl, $opt);
    }

    public function __destruct()
    {
        if (is_resource($this->lastCurl)) {
            curl_close($this->lastCurl);
        }
    }
}
