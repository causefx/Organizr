<?php

namespace Buzz\Converter;

use Buzz\Exception\LogicException;
use Buzz\Message\Request;
use Buzz\Message\RequestInterface;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;

/**
 *
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RequestConverter
{
    /**
     * @param Psr7RequestInterface|RequestInterface $request
     * @return Psr7RequestInterface
     */
    public static function psr7($request)
    {
        if ($request instanceof Psr7RequestInterface) {
            return $request;
        } elseif (!$request instanceof RequestInterface) {
            throw new LogicException('Only instances of PSR7 Request and Buzz requests are allowed');
        }

        // Convert the response to psr7
        return new \GuzzleHttp\Psr7\Request(
            $request->getMethod(),
            sprintf('%s%s', $request->getHost(), $request->getResource()),
            HeaderConverter::toPsrHeaders($request->getHeaders()),
            $request->getContent(),
            $request->getProtocolVersion()
        );
    }
    /**
     * @param Psr7RequestInterface|RequestInterface $request
     * @return RequestInterface
     */
    public static function buzz($request)
    {
        if ($request instanceof RequestInterface) {
            return $request;
        } elseif (!$request instanceof Psr7RequestInterface) {
            throw new LogicException('Only instances of PSR7 Request and Buzz requests are allowed');
        }

        // Convert the response to buzz response
        $uri = $request->getUri();
        $buzzRequest = new Request(
            $request->getMethod(),
            sprintf('%s?%s', $uri->getPath(), $uri->getQuery()),
            $uri->getScheme().'://'.$uri->getHost().($uri->getPort() !== null ? $uri->getPort() : '')
        );

        $buzzRequest->addHeaders(HeaderConverter::toBuzzHeaders($request->getHeaders()));
        $buzzRequest->setContent($request->getBody()->__toString());
        $buzzRequest->setProtocolVersion($request->getProtocolVersion());

        return $buzzRequest;
    }
}
