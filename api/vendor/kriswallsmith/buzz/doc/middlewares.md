# Buzz middlewares

If you want to modify the request or response somehow, a middleware is the way to
go. Every time you send a request with the `Browser` it will run through all the
middlewares. The order of the middlewares is important. The first middleware added
to the `Browser` will be the first one that is executed when handling the request and
the last one to be executed when handling the response. 

```
Request  ---> Middleware1 ---> Middleware2 ---> HttpClient ----
                                                               | (processing call)
Response <--- Middleware1 <--- Middleware2 <--- HttpClient <---
```
## Creating a middleware

You are free to create any custom middleware you want. It is super simple to do so. 
Let's look at the example when we create a middleware for adding the User-Agent 
request header. 

First we need to create a class that implements `Buzz\Middleware\MiddlewareInterface`

```php
<?php

use Buzz\Middleware\MiddlewareInterface;

class UserAgentMiddleware extends MiddlewareInterface 
{
  // ...
``` 

The interface has two functions; `handleRequest` and `handleResponse`. The last
parameter to these functions is a `callable`. That callable is actually the next
middleware in the chain. It is **very important** that you end your function by
returning the the result when calling that callable. If you forget about that,
then no request will be sent.

Let's look at an example implementation of `handleRequest`:

```php
public function handleRequest(RequestInterface $request, callable $next)
{
    $request = $request->withAddedHeader('User-Agent', 'Buzz');
    
    return $next($request);
}
```

Note that PSR-7 requests and responses are immutable. That is why we do 
`$request = $request->with...`. 

Sine this middleware does not need to modify the response we just let the 
`handleResponse` function to be empty like: 

 ```php
 public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next)
 {     
     return $next($request, $response);
 }
 ```

### The full example

```php
<?php

use Buzz\Middleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class UserAgentMiddleware extends MiddlewareInterface 
{
  public function handleRequest(RequestInterface $request, callable $next)
  {
      $request = $request->withAddedHeader('User-Agent', 'Buzz');
      
      return $next($request);
  }
  
   public function handleResponse(RequestInterface $request, ResponseInterface $response, callable $next)
   {     
       return $next($request, $response);
   }
}
``` 