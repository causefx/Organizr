<?php

namespace Buzz\Client;

use Buzz\Converter\RequestConverter;
use Buzz\Converter\ResponseConverter;
use Buzz\Exception\RequestException;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Psr\Http\Message\RequestInterface as PSR7RequestInterface;
use Buzz\Exception\ClientException;

class FileGetContents extends AbstractStream
{
    /**
     * @see ClientInterface
     *
     * @throws ClientException If file_get_contents() fires an error
     *
     * @deprecated Will be removed in 1.0. Use sendRequest instead.
     */
    public function send(RequestInterface $request, MessageInterface $response)
    {
        @trigger_error('FileGetContents::send() is deprecated. FileGetContents Curl::sendRequest instead.', E_USER_DEPRECATED);
        $request = RequestConverter::psr7($request);
        ResponseConverter::copy(ResponseConverter::buzz($this->sendRequest($request)), $response);
    }

    /**
     * @param PSR7RequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest(PSR7RequestInterface $request)
    {
        $context = stream_context_create($this->getStreamContextArray($request));

        $level = error_reporting(0);
        $content = file_get_contents($request->getUri()->__toString(), 0, $context);
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            $e = new RequestException($error['message']);
            $e->setRequest($request);

            throw $e;
        }

        // TODO rewrite to not use Buzz reponse
        $response = new \Buzz\Message\Response();
        $response->setHeaders($this->filterHeaders((array) $http_response_header));
        $response->setContent($content);

        return ResponseConverter::psr7($response);
    }

    private function filterHeaders(array $headers)
    {
        $filtered = array();
        foreach ($headers as $header) {
            if (0 === stripos($header, 'http/')) {
                $filtered = array();
            }

            $filtered[] = $header;
        }

        return $filtered;
    }
}
