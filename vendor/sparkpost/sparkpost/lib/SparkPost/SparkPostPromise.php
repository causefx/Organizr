<?php

namespace SparkPost;

use Http\Promise\Promise as HttpPromise;

class SparkPostPromise implements HttpPromise
{
    /**
     * HttpPromise to be wrapped by SparkPostPromise.
     */
    private $promise;

    /**
     * Array with the request values sent.
     */
    private $request;

    /**
     * set the promise to be wrapped.
     *
     * @param HttpPromise $promise
     */
    public function __construct(HttpPromise $promise, $request = null)
    {
        $this->promise = $promise;
        $this->request = $request;
    }

    /**
     * Hand off the response functions to the original promise and return a custom response or exception.
     *
     * @param callable $onFulfilled - function to be called if the promise is fulfilled
     * @param callable $onRejected  - function to be called if the promise is rejected
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        $request = $this->request;

        return $this->promise->then(function ($response) use ($onFulfilled, $request) {
            if (isset($onFulfilled)) {
                $onFulfilled(new SparkPostResponse($response, $request));
            }
        }, function ($exception) use ($onRejected, $request) {
            if (isset($onRejected)) {
                $onRejected(new SparkPostException($exception, $request));
            }
        });
    }

    /**
     * Hand back the state.
     *
     * @return $state - returns the state of the promise
     */
    public function getState()
    {
        return $this->promise->getState();
    }

    /**
     * Wraps the wait function and returns a custom response or throws a custom exception.
     *
     * @param bool $unwrap
     *
     * @return SparkPostResponse
     *
     * @throws SparkPostException
     */
    public function wait($unwrap = true)
    {
        try {
            $response = $this->promise->wait($unwrap);

            return $response ? new SparkPostResponse($response, $this->request) : $response;
        } catch (\Exception $exception) {
            throw new SparkPostException($exception, $this->request);
        }
    }
}
