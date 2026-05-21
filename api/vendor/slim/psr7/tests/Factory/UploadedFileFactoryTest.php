<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Factory;

use Interop\Http\Factory\UploadedFileFactoryTestCase;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;

use function fopen;
use function fwrite;
use function rewind;
use function sys_get_temp_dir;
use function tempnam;

class UploadedFileFactoryTest extends UploadedFileFactoryTestCase
{
    protected function createUploadedFileFactory(): UploadedFileFactory
    {
        return new UploadedFileFactory();
    }

    protected function createStream($content): StreamInterface
    {
        $file = tempnam(sys_get_temp_dir(), 'Slim_Http_UploadedFileTest_');
        $resource = fopen($file, 'r+');
        fwrite($resource, $content);
        rewind($resource);

        return (new StreamFactory())->createStreamFromResource($resource);
    }

    /**
     * Create a `\Psr\Http\Message\StreamInterface` mock with a `getMetadata` method expectation.
     *
     * @param string $argKey Argument for the method expectation.
     * @param mixed $returnValue Return value of the `getMetadata` method.
     *
     * @return StreamInterface
     */
    protected function prophesizeStreamInterfaceWithGetMetadataMethod(string $argKey, $returnValue): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getMetadata')
            ->with($argKey)
            ->willReturn($returnValue);

        return $stream;
    }

    public function testCreateUploadedFileWithInvalidUri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable.');

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getMetadata')
            ->with('uri')
            ->willReturn(null);

        $this->factory->createUploadedFile($stream);
    }

    public function testCreateUploadedFileWithNonReadableFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable.');

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getMetadata')
            ->with('uri')
            ->willReturn('non-readable');
        $stream->expects($this->once())
            ->method('isReadable')
            ->willReturn(false);

        $this->factory->createUploadedFile($stream);
    }
}
