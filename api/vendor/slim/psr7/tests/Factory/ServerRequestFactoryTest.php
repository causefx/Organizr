<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Factory;

use Interop\Http\Factory\ServerRequestFactoryTestCase;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Slim\Psr7\Environment;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\UploadedFile;
use stdClass;

use function microtime;
use function time;

class ServerRequestFactoryTest extends ServerRequestFactoryTestCase
{
    protected function setAccessible(ReflectionProperty|ReflectionMethod $property, bool $accessible = true): void
    {
        // only if PHP version < 8.1
        if (PHP_VERSION_ID > 80100) {
            return;
        }
        $property->setAccessible($accessible);
    }

    protected function createServerRequestFactory(): ServerRequestFactory
    {
        return new ServerRequestFactory();
    }

    protected function createUri($uri): UriInterface
    {
        return (new UriFactory())->createUri($uri);
    }

    public function testGetProtocolVersion()
    {
        $env = Environment::mock(['SERVER_PROTOCOL' => 'HTTP/1.0']);
        $request = $this->createServerRequestFactory()->createServerRequest('GET', '', $env);

        $this->assertEquals('1.0', $request->getProtocolVersion());
    }

    public function testCreateFromGlobals()
    {
        $GLOBALS['getallheaders_return'] = [
            'ACCEPT' => 'application/json',
            'ACCEPT-CHARSET' => 'utf-8',
            'ACCEPT-LANGUAGE' => 'en-US',
            'CONTENT-TYPE' => 'multipart/form-data',
            'HOST' => 'example.com',
            'USER-AGENT' => 'Slim Framework',
        ];

        $_SERVER = Environment::mock([
            'HTTP_HOST' => 'example.com',
            'PHP_AUTH_PW' => 'sekrit',
            'PHP_AUTH_USER' => 'josh',
            'QUERY_STRING' => 'abc=123',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => '/foo/bar',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 8080,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        $request = ServerRequestFactory::createFromGlobals();

        unset($GLOBALS['getallheaders_return']);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('1.1', $request->getProtocolVersion());

        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
        $this->assertEquals('utf-8', $request->getHeaderLine('Accept-Charset'));
        $this->assertEquals('en-US', $request->getHeaderLine('Accept-Language'));
        $this->assertEquals('multipart/form-data', $request->getHeaderLine('Content-Type'));

        $uri = $request->getUri();
        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromGlobalsWithParsedBody()
    {
        $_SERVER = Environment::mock([
            'HTTP_CONTENT_TYPE' => 'multipart/form-data',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_POST = [
            'def' => '456',
        ];

        $request = ServerRequestFactory::createFromGlobals();

        // $_POST should be placed into the parsed body
        $this->assertEquals($_POST, $request->getParsedBody());
    }

    public function testCreateFromGlobalsBodyPointsToPhpInput()
    {
        $request = ServerRequestFactory::createFromGlobals();

        $this->assertEquals('php://input', $request->getBody()->getMetadata('uri'));
    }

    public function testCreateFromGlobalsSetsACache()
    {
        $request = ServerRequestFactory::createFromGlobals();

        // ensure that the Stream's $cache property has been set for this php://input stream
        $stream = $request->getBody();
        $class = new ReflectionClass($stream);
        $property = $class->getProperty('cache');
        $this->setAccessible($property);
        $cacheStreamValue = $property->getValue($stream);
        $this->assertNotNull($cacheStreamValue);
    }

    public function testCreateFromGlobalsWithUploadedFiles()
    {
        $_SERVER = Environment::mock([
            'HTTP_CONTENT_TYPE' => 'multipart/form-data',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_FILES = [
            'uploaded_file' => [
                'name' => [
                    0 => 'foo.jpg',
                    1 => 'bar.jpg',
                ],

                'type' => [
                    0 => 'image/jpeg',
                    1 => 'image/jpeg',
                ],

                'tmp_name' => [
                    0 => '/tmp/phpUA3XUw',
                    1 => '/tmp/phpXUFS0x',
                ],

                'error' => [
                    0 => 0,
                    1 => 0,
                ],

                'size' => [
                    0 => 358708,
                    1 => 236162,
                ],
            ]
        ];

        $request = ServerRequestFactory::createFromGlobals();

        // $_FILES should be mapped to an array of UploadedFile objects
        $uploadedFiles = $request->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);
        $this->assertArrayHasKey('uploaded_file', $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['uploaded_file'][0]);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['uploaded_file'][1]);
    }

    public function testCreateFromGlobalsParsesBodyWithFragmentedContentType()
    {
        $_SERVER = Environment::mock([
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=utf-8',
            'REQUEST_METHOD' => 'POST',
        ]);

        $_POST = [
            'def' => '456',
        ];

        $request = ServerRequestFactory::createFromGlobals();

        $this->assertEquals($_POST, $request->getParsedBody());
    }

    public function testCreateServerRequestWithNullAsUri()
    {
        $this->expectException(InvalidArgumentException::class);

        $env = Environment::mock();
        $this->createServerRequestFactory()->createServerRequest('GET', null, $env);
    }

    public function testCreateServerRequestWithInvalidUriObject()
    {
        $this->expectException(InvalidArgumentException::class);

        $env = Environment::mock();
        $this->createServerRequestFactory()->createServerRequest('GET', new stdClass(), $env);
    }
}
