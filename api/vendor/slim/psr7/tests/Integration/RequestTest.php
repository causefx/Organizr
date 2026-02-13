<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\RequestIntegrationTest;
use Psr\Http\Message\RequestInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;

class RequestTest extends RequestIntegrationTest
{
    use BaseTestFactories;

    /**
     * @return RequestInterface that is used in the tests
     */
    public function createSubject(): RequestInterface
    {
        return new Request(
            'GET',
            $this->buildUri('/'),
            new Headers(),
            [],
            [],
            $this->buildStream('')
        );
    }
}
