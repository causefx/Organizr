<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\ResponseIntegrationTest;
use Slim\Psr7\Response;

class ResponseTest extends ResponseIntegrationTest
{
    use BaseTestFactories;

    /**
     * @return Response that is used in the tests
     */
    public function createSubject(): Response
    {
        return new Response();
    }
}
