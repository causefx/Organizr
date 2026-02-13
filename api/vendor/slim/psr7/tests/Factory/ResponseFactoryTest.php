<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Factory;

use Interop\Http\Factory\ResponseFactoryTestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;

class ResponseFactoryTest extends ResponseFactoryTestCase
{
    protected function createResponseFactory(): ResponseFactory
    {
        return new ResponseFactory();
    }

    protected function assertResponseCodeAndReasonPhrase(ResponseInterface $response, int $code, string $reasonPhrase)
    {
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame($reasonPhrase, $response->getReasonPhrase());
    }

    /**
     * @dataProvider dataCodes
     *
     * @param int $code
     */
    public function testCreateResponseWithReasonPhrase(int $code)
    {
        $response = $this->factory->createResponse($code, 'Reason');
        $this->assertResponse($response, $code);
        $this->assertResponseCodeAndReasonPhrase($response, $code, 'Reason');
    }
}
