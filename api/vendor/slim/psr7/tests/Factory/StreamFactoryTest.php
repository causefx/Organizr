<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Factory;

use Interop\Http\Factory\StreamFactoryTestCase;
use InvalidArgumentException;
use RuntimeException;
use Slim\Psr7\Factory\StreamFactory;

class StreamFactoryTest extends StreamFactoryTestCase
{
    public function tearDown(): void
    {
        if (isset($GLOBALS['fopen_return'])) {
            unset($GLOBALS['fopen_return']);
        }
    }

    protected function createStreamFactory(): StreamFactory
    {
        return new StreamFactory();
    }

    public function testCreateStreamThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('StreamFactory::createStream() could not open temporary file stream.');

        $GLOBALS['fopen_return'] = false;

        $factory = $this->createStreamFactory();

        $factory->createStream();
    }

    public function testCreateStreamFromFileThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('StreamFactory::createStreamFromFile() could not create resource'
                                      . ' from file `non-readable`');

        $GLOBALS['fopen_return'] = false;

        $factory = $this->createStreamFactory();

        $factory->createStreamFromFile('non-readable');
    }

    public function testCreateStreamFromResourceThrowsRuntimeException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter 1 of StreamFactory::createStreamFromResource() must be a resource.');

        $factory = $this->createStreamFactory();

        $factory->createStreamFromResource('not-resource');
    }
}
