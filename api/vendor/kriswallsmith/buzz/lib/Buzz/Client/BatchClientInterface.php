<?php

namespace Buzz\Client;

use Buzz\Exception\ClientException;

/**
 * A client capable of running batches of requests.
 *
 * The Countable implementation should return the number of queued requests.
 */
interface BatchClientInterface extends ClientInterface, \Countable
{
    /**
     * Processes all queued requests.
     *
     * @throws ClientException If something goes wrong
     */
    public function flush();

    /**
     * Processes zero or more queued requests.
     *
     * @throws ClientException If something goes wrong
     */
    public function proceed();
}
