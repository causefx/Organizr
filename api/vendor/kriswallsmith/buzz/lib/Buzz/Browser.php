<?php

namespace Buzz;

use Buzz\Client\ClientInterface;
use Buzz\Client\FileGetContents;
use Buzz\Listener\ListenerChain;
use Buzz\Listener\ListenerInterface;
use Buzz\Message\Factory\Factory;
use Buzz\Message\Factory\FactoryInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Middleware\MiddlewareInterface;
use Buzz\Util\Url;
use Psr\Http\Message\RequestInterface as Psr7RequestInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class Browser
{
    /** @var ClientInterface */
    private $client;

    /** @var FactoryInterface */
    private $factory;

    /** @var ListenerInterface */
    private $listener;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /** @var RequestInterface */
    private $lastRequest;

    /** @var MessageInterface */
    private $lastResponse;

    public function __construct(ClientInterface $client = null, FactoryInterface $factory = null)
    {
        $this->client = $client ?: new FileGetContents();
        $this->factory = $factory ?: new Factory();
    }

    public function get($url, $headers = array())
    {
        return $this->call($url, RequestInterface::METHOD_GET, $headers);
    }

    public function post($url, $headers = array(), $content = '')
    {
        return $this->call($url, RequestInterface::METHOD_POST, $headers, $content);
    }

    public function head($url, $headers = array())
    {
        return $this->call($url, RequestInterface::METHOD_HEAD, $headers);
    }

    public function patch($url, $headers = array(), $content = '')
    {
        return $this->call($url, RequestInterface::METHOD_PATCH, $headers, $content);
    }

    public function put($url, $headers = array(), $content = '')
    {
        return $this->call($url, RequestInterface::METHOD_PUT, $headers, $content);
    }

    public function delete($url, $headers = array(), $content = '')
    {
        return $this->call($url, RequestInterface::METHOD_DELETE, $headers, $content);
    }

    /**
     * Sends a request.
     *
     * @param string $url     The URL to call
     * @param string $method  The request method to use
     * @param array  $headers An array of request headers
     * @param string $content The request content
     *
     * @return MessageInterface The response object
     */
    public function call($url, $method, $headers = array(), $content = '')
    {
        $request = $this->factory->createRequest($method);

        if (!$url instanceof Url) {
            $url = new Url($url);
        }

        $url->applyToRequest($request);

        $request->addHeaders($headers);
        $request->setContent($content);

        return $this->send($request);
    }

    /**
     * Sends a form request.
     *
     * @param string $url     The URL to submit to
     * @param array  $fields  An array of fields
     * @param string $method  The request method to use
     * @param array  $headers An array of request headers
     *
     * @return MessageInterface The response object
     */
    public function submit($url, array $fields, $method = RequestInterface::METHOD_POST, $headers = array())
    {
        $request = $this->factory->createFormRequest();

        if (!$url instanceof Url) {
            $url = new Url($url);
        }

        $url->applyToRequest($request);

        $request->addHeaders($headers);
        $request->setMethod($method);
        $request->setFields($fields);

        return $this->send($request);
    }

    /**
     * Sends a request.
     *
     * @param RequestInterface $request  A request object
     * @param MessageInterface $response A response object
     *
     * @return MessageInterface The response
     */
    public function send(RequestInterface $request, MessageInterface $response = null)
    {
        if (null === $response) {
            $response = $this->factory->createResponse();
        }

        if ($this->listener) {
            $this->listener->preSend($request);
        }

        $this->client->send($request, $response);

        $this->lastRequest = $request;
        $this->lastResponse = $response;

        if ($this->listener) {
            $this->listener->postSend($request, $response);
        }

        return $response;
    }

    /**
     * Send a PSR7 request.
     *
     * @param Psr7RequestInterface $request
     * @return Psr7ResponseInterface
     */
    public function sendRequest(Psr7RequestInterface $request)
    {
        $chain = $this->createMiddlewareChain($this->middlewares, function(Psr7RequestInterface $request) {
            return $this->client->sendRequest($request);
        }, function (Psr7RequestInterface $request, Psr7ResponseInterface $response) {
            $this->lastRequest = $request;
            $this->lastResponse = $response;
        });

        // Call the chain
        $chain($request);

        return $this->lastResponse;
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     * @param callable $requestChainLast
     * @param callable $responseChainLast
     *
     * @return callable
     */
    private function createMiddlewareChain(array $middlewares, callable $requestChainLast, callable $responseChainLast)
    {
        $responseChainNext = $responseChainLast;

        // Build response chain
        /** @var MiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $lastCallable = function (Psr7RequestInterface $request, Psr7ResponseInterface $response) use ($middleware, $responseChainNext) {
                return $middleware->handleResponse($request, $response, $responseChainNext);
            };

            $responseChainNext = $lastCallable;
        }

        $requestChainLast = function (Psr7RequestInterface $request) use ($requestChainLast, $responseChainNext) {
            // Send the actual request and get the response
            $response = $requestChainLast($request);
            $responseChainNext($request, $response);
        };

        $middlewares = array_reverse($middlewares);

        // Build request chain
        $requestChainNext = $requestChainLast;
        /** @var MiddlewareInterface $middleware */
        foreach ($middlewares as $middleware) {
            $lastCallable = function (Psr7RequestInterface $request) use ($middleware, $requestChainNext) {
                return $middleware->handleRequest($request, $requestChainNext);
            };

            $requestChainNext = $lastCallable;
        }

        return $requestChainNext;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setMessageFactory(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function getMessageFactory()
    {
        return $this->factory;
    }

    public function setListener(ListenerInterface $listener)
    {
        $this->listener = $listener;
    }

    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Add a new middleware to the stack.
     *
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[] = $middleware;
    }



    public function addListener(ListenerInterface $listener)
    {
        if (!$this->listener) {
            $this->listener = $listener;
        } elseif ($this->listener instanceof ListenerChain) {
            $this->listener->addListener($listener);
        } else {
            $this->listener = new ListenerChain(array(
                $this->listener,
                $listener,
            ));
        }
    }
}
