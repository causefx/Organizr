<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\UriIntegrationTest;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\UriFactory;

class UriTest extends UriIntegrationTest
{
    use BaseTestFactories;

    /**
     * @param string $uri
     *
     * @return UriInterface
     */
    public function createUri($uri): UriInterface
    {
        return (new UriFactory())->createUri($uri);
    }
}
