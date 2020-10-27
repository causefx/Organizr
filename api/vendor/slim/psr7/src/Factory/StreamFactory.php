<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7\Factory;

use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Slim\Psr7\Stream;

use function fopen;
use function fwrite;
use function is_resource;
use function restore_error_handler;
use function rewind;
use function set_error_handler;
use function sprintf;

class StreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $resource = fopen('php://temp', 'rw+');

        if (!is_resource($resource)) {
            throw new RuntimeException('StreamFactory::createStream() could not open temporary file stream.');
        }

        fwrite($resource, $content);
        rewind($resource);

        return $this->createStreamFromResource($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(
        string $filename,
        string $mode = 'r',
        StreamInterface $cache = null
    ): StreamInterface {
        // When fopen fails, PHP normally raises a warning. Add an error
        // handler to check for errors and throw an exception instead.
        $exc = null;

        set_error_handler(function (int $errno, string $errstr) use ($filename, $mode, &$exc) {
            $exc = new RuntimeException(sprintf(
                'Unable to open %s using mode %s: %s',
                $filename,
                $mode,
                $errstr
            ));
        });

        $resource = fopen($filename, $mode);
        restore_error_handler();

        if ($exc) {
            /** @var RuntimeException $exc */
            throw $exc;
        }

        if (!is_resource($resource)) {
            throw new RuntimeException(
                "StreamFactory::createStreamFromFile() could not create resource from file `$filename`"
            );
        }

        return new Stream($resource, $cache);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource, StreamInterface $cache = null): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(
                'Parameter 1 of StreamFactory::createStreamFromResource() must be a resource.'
            );
        }

        return new Stream($resource, $cache);
    }
}
