<?php

namespace Buzz\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A middleware gets called twice per request. One time before we send the request
 * and once after the response is received. A middleware may modify/change the
 * request and the response. Just be aware that they are immutable.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface MiddlewareInterface
{
    /**
     * Handle a request.
     *
     * End this function by calling:
     *   <code>
     *      return $next($request);
     *   </code
     *
     * @param RequestInterface $request
     * @param callable $next Next middleware.
     */
    public function handleRequest(RequestInterface $request, callable $next);


    /**
     * Handle a response.
     *
     * End this function by calling:
     *   <code>
     *      return $next($request, $response);
     *   </code
     *
     * @param RequestInterface $request
     * @param callable $next Next middleware.
     */
    public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next);
}