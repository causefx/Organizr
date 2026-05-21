<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Slim\Psr7\Stream;

use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function ftell;
use function fwrite;
use function is_array;
use function is_resource;
use function mb_strlen;
use function rewind;
use function substr;

class BodyTest extends TestCase
{
    // @codingStandardsIgnoreStart
    protected string $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    // @codingStandardsIgnoreEnd

    /**
     * @var resource
     */
    protected $stream;

    protected function tearDown(): void
    {
        if (is_resource($this->stream) === true) {
            fclose($this->stream);
        }
    }

    protected function setAccessible(ReflectionProperty $property, bool $accessible = true): void
    {
        // only if PHP version < 8.1
        if (PHP_VERSION_ID > 80100) {
            return;
        }
        $property->setAccessible($accessible);
    }

    /**
     * @param string $mode
     *
     * @return resource
     */
    public function resourceFactory(string $mode = 'r+')
    {
        $stream = fopen('php://temp', $mode);
        fwrite($stream, $this->text);
        rewind($stream);

        return $stream;
    }

    public function testConstructorAttachesStream()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);

        $this->assertSame($this->stream, $bodyStream->getValue($body));
    }

    public function testConstructorInvalidStream()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->stream = 'foo';
        $body = new Stream($this->stream);
    }

    public function testGetMetadata()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertTrue(is_array($body->getMetadata()));
    }

    public function testGetMetadataKey()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertEquals('php://temp', $body->getMetadata('uri'));
    }

    public function testGetMetadataKeyNotFound()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertNull($body->getMetadata('foo'));
    }

    public function testDetach()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);

        $bodyMetadata = new ReflectionProperty($body, 'meta');
        $this->setAccessible($bodyMetadata);

        $bodyReadable = new ReflectionProperty($body, 'readable');
        $this->setAccessible($bodyReadable);

        $bodyWritable = new ReflectionProperty($body, 'writable');
        $this->setAccessible($bodyWritable);

        $bodySeekable = new ReflectionProperty($body, 'seekable');
        $this->setAccessible($bodySeekable);

        $result = $body->detach();

        $this->assertSame($this->stream, $result);
        $this->assertNull($bodyStream->getValue($body));
        $this->assertNull($bodyMetadata->getValue($body));
        $this->assertNull($bodyReadable->getValue($body));
        $this->assertNull($bodyWritable->getValue($body));
        $this->assertNull($bodySeekable->getValue($body));
    }

    public function testToStringAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertEquals($this->text, (string) $body);
    }

    public function testToStringAttachedRewindsFirst()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertEquals($this->text, (string) $body);
        $this->assertEquals($this->text, (string) $body);
        $this->assertEquals($this->text, (string) $body);
    }

    public function testToStringDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);
        $bodyStream->setValue($body, null);

        $this->assertEquals('', (string) $body);
    }

    public function testClose()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->close();

        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);

        $this->assertNull($bodyStream->getValue($body));
    }

    public function testGetSizeAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertEquals(mb_strlen($this->text), $body->getSize());
    }

    public function testGetSizeDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);
        $bodyStream->setValue($body, null);

        $this->assertNull($body->getSize());
    }

    public function testTellAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(10, $body->tell());
    }

    public function testTellDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);
        $bodyStream->setValue($body, null);

        $body->tell();
    }

    public function testEofAttachedFalse()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        fseek($this->stream, 10);

        $this->assertFalse($body->eof());
    }

    public function testEofAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }

        $this->assertTrue($body->eof());
    }

    public function testEofDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $this->setAccessible($bodyStream);
        $bodyStream->setValue($body, null);

        $this->assertTrue($body->eof());
    }

    public function isReadableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertTrue($body->isReadable());
    }

    public function isReadableAttachedFalse()
    {
        $stream = fopen('php://temp', 'w');
        $body = new Stream($this->stream);

        $this->assertFalse($body->isReadable());
        fclose($stream);
    }

    public function testIsReadableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $this->assertFalse($body->isReadable());
    }

    public function isWritableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertTrue($body->isWritable());
    }

    public function isWritableAttachedFalse()
    {
        $stream = fopen('php://temp', 'r');
        $body = new Stream($this->stream);

        $this->assertFalse($body->isWritable());
        fclose($stream);
    }

    public function testIsWritableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $this->assertFalse($body->isWritable());
    }

    public function isSeekableAttachedTrue()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertTrue($body->isSeekable());
    }

    // TODO: Is seekable is false when attached... how?

    public function testIsSeekableDetached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $this->assertFalse($body->isSeekable());
    }

    public function testSeekAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->seek(10);

        $this->assertEquals(10, ftell($this->stream));
    }

    public function testSeekDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $body->seek(10);
    }

    public function testRewindAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        fseek($this->stream, 10);
        $body->rewind();

        $this->assertEquals(0, ftell($this->stream));
    }

    public function testRewindDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $body->rewind();
    }

    public function testReadAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);

        $this->assertEquals(substr($this->text, 0, 10), $body->read(10));
    }

    public function testReadDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $body->read(10);
    }

    public function testWriteAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }
        $body->write('foo');

        $this->assertEquals($this->text . 'foo', (string) $body);
    }

    public function testWriteDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $body->write('foo');
    }

    public function testGetContentsAttached()
    {
        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(substr($this->text, 10), $body->getContents());
    }

    public function testGetContentsDetachedThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        $this->stream = $this->resourceFactory();
        $body = new Stream($this->stream);
        $body->detach();

        $body->getContents();
    }
}
