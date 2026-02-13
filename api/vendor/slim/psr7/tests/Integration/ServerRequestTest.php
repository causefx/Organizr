<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;

class ServerRequestTest extends ServerRequestIntegrationTest
{
    use BaseTestFactories;

    /**
     * @return Request
     */
    public function createSubject(): Request
    {
        return new Request(
            'GET',
            $this->buildUri('/'),
            new Headers(),
            $_COOKIE,
            $_SERVER,
            $this->buildStream('')
        );
    }
}
