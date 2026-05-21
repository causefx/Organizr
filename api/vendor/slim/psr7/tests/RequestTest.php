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
use Slim\Psr7\Environment;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\UploadedFile;
use Slim\Psr7\Uri;

use function property_exists;
use function sprintf;

class RequestTest extends TestCase
{
    protected function setAccessible(ReflectionProperty|ReflectionMethod $property, bool $accessible = true): void
    {
        // only if PHP version < 8.1
        if (PHP_VERSION_ID > 80100) {
            return;
        }
        $property->setAccessible($accessible);
    }

    public function requestFactory($envData = []): Request
    {
        $env = Environment::mock($envData);

        $uri = (new UriFactory())->createUri('https://example.com:443/foo/bar?abc=123');
        $headers = Headers::createFromGlobals($env);
        $cookies = [
            'user' => 'john',
            'id' => '123',
        ];
        $serverParams = $env;
        $body = (new StreamFactory())->createStream();
        $uploadedFiles = UploadedFile::createFromGlobals($env);
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        return $request;
    }

    public function testDisableSetter()
    {
        $request = $this->requestFactory();
        $request->foo = 'bar';

        $this->assertFalse(property_exists($request, 'foo'));
    }

    public function testAddsHostHeaderFromUri()
    {
        $request = $this->requestFactory();
        $this->assertEquals('example.com', $request->getHeaderLine('Host'));
    }

    public function testGetMethod()
    {
        $this->assertEquals('GET', $this->requestFactory()->getMethod());
    }

    public function testWithMethod()
    {
        $request = $this->requestFactory()->withMethod('PUT');

        $this->assertEquals('PUT', $request->getMethod());
    }

    public function testWithMethodCaseSensitive()
    {
        $request = $this->requestFactory()->withMethod('pOsT');

        $this->assertEquals('pOsT', $request->getMethod());
    }

    public function testWithAllAllowedCharactersMethod()
    {
        $request = $this->requestFactory()->withMethod("!#$%&'*+.^_`|~09AZ-");

        $this->assertEquals("!#$%&'*+.^_`|~09AZ-", $request->getMethod());
    }

    public function testWithMethodInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->requestFactory()->withMethod('B@R');
    }

    public function testCreateRequestWithInvalidMethodString()
    {
        $this->expectException(InvalidArgumentException::class);

        $uri = (new UriFactory())->createUri('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();

        new Request('B@R', $uri, $headers, $cookies, $serverParams, $body);
    }

    public function testCreateRequestWithInvalidMethodOther()
    {
        $this->expectException(InvalidArgumentException::class);

        $uri = (new UriFactory())->createUri('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();

        new Request(10, $uri, $headers, $cookies, $serverParams, $body);
    }

    public function testGetRequestTarget()
    {
        $this->assertEquals('/foo/bar?abc=123', $this->requestFactory()->getRequestTarget());
    }

    public function testGetRequestTargetAlreadySet()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'requestTarget');
        $this->setAccessible($prop);
        $prop->setValue($request, '/foo/bar?abc=123');

        $this->assertEquals('/foo/bar?abc=123', $request->getRequestTarget());
    }

    public function testGetRequestTargetIfNoUri()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'uri');
        $this->setAccessible($prop);
        $prop->setValue($request, null);

        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testWithRequestTarget()
    {
        $clone = $this->requestFactory()->withRequestTarget('/test?user=1');

        $this->assertEquals('/test?user=1', $clone->getRequestTarget());
    }

    public function testWithRequestTargetThatHasSpaces()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->requestFactory()->withRequestTarget('/test/m ore/stuff?user=1');
    }

    public function testGetUri()
    {
        $uri = (new UriFactory())->createUri('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUri()
    {
        // Uris
        $uri1 = (new UriFactory())->createUri('https://example.com:443/foo/bar?abc=123');
        $uri2 = (new UriFactory())->createUri('https://example2.com:443/test?xyz=123');

        // Request
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();
        $request = new Request('GET', $uri1, $headers, $cookies, $serverParams, $body);
        $clone = $request->withUri($uri2);

        $this->assertSame($uri2, $clone->getUri());
    }

    public function testWithUriPreservesHost()
    {
        // When `$preserveHost` is set to `true`, this method interacts with
        // the Host header in the following ways:

        // - If the Host header is missing or empty, and the new URI contains
        //   a host component, this method MUST update the Host header in the returned
        //   request.
        $uri1 = (new UriFactory())->createUri('');
        $uri2 = (new UriFactory())->createUri('http://example2.com/test');

        // Request
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();
        $request = new Request('GET', $uri1, $headers, $cookies, $serverParams, $body);

        $clone = $request->withUri($uri2, true);
        $this->assertSame('example2.com', $clone->getHeaderLine('Host'));

        // - If the Host header is missing or empty, and the new URI does not contain a
        //   host component, this method MUST NOT update the Host header in the returned
        //   request.
        $uri3 = (new UriFactory())->createUri('');

        $clone = $request->withUri($uri3, true);
        $this->assertSame('', $clone->getHeaderLine('Host'));

        // - If a Host header is present and non-empty, this method MUST NOT update
        //   the Host header in the returned request.
        $request = $request->withHeader('Host', 'example.com');
        $clone = $request->withUri($uri2, true);
        $this->assertSame('example.com', $clone->getHeaderLine('Host'));
    }

    public function testGetCookieParams()
    {
        $shouldBe = [
            'user' => 'john',
            'id' => '123',
        ];

        $this->assertEquals($shouldBe, $this->requestFactory()->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $request = $this->requestFactory();
        $clone = $request->withCookieParams(['type' => 'framework']);

        $this->assertEquals(['type' => 'framework'], $clone->getCookieParams());
    }

    public function testGetQueryParams()
    {
        $this->assertEquals(['abc' => '123'], $this->requestFactory()->getQueryParams());
    }

    public function testGetQueryParamsAlreadySet()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'queryParams');
        $this->setAccessible($prop);
        $prop->setValue($request, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $request->getQueryParams());
    }

    public function testWithQueryParams()
    {
        $request = $this->requestFactory();
        $clone = $request->withQueryParams(['foo' => 'bar']);
        $cloneUri = $clone->getUri();

        $this->assertEquals('abc=123', $cloneUri->getQuery()); // <-- Unchanged
        $this->assertEquals(['foo' => 'bar'], $clone->getQueryParams()); // <-- Changed
    }

    public function testWithQueryParamsEmptyArray()
    {
        $request = $this->requestFactory();
        $clone = $request->withQueryParams([]);
        $cloneUri = $clone->getUri();

        $this->assertEquals('abc=123', $cloneUri->getQuery()); // <-- Unchanged
        $this->assertEquals([], $clone->getQueryParams()); // <-- Changed
    }

    public function testGetQueryParamsWithoutUri()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'uri');
        $this->setAccessible($prop);
        $prop->setValue($request, null);

        $this->assertEquals([], $request->getQueryParams());
    }

    public function testWithUploadedFiles()
    {
        $files = [new UploadedFile('foo.txt'), new UploadedFile('bar.txt')];

        $request = $this->requestFactory();
        $prevUploaded = $request->getUploadedFiles();
        $clone = $request->withUploadedFiles($files);

        $this->assertEquals($prevUploaded, $request->getUploadedFiles());
        $this->assertEquals($files, $clone->getUploadedFiles());
    }

    public function testGetServerParams()
    {
        $mockEnv = Environment::mock(["HTTP_AUTHORIZATION" => "test"]);
        $request = $this->requestFactory(["HTTP_AUTHORIZATION" => "test"]);

        $serverParams = $request->getServerParams();
        foreach ($serverParams as $key => $value) {
            if ($key == 'REQUEST_TIME' || $key == 'REQUEST_TIME_FLOAT') {
                $this->assertGreaterThanOrEqual(
                    $mockEnv[$key],
                    $value,
                    sprintf("%s value of %s was less than expected value of %s", $key, $value, $mockEnv[$key])
                );
            } else {
                $this->assertEquals(
                    $mockEnv[$key],
                    $value,
                    sprintf("%s value of %s did not equal expected value of %s", $key, $value, $mockEnv[$key])
                );
            }
        }
    }

    public function testGetAttributes()
    {
        $request = $this->requestFactory();
        $attrProp = new ReflectionProperty($request, 'attributes');
        $this->setAccessible($attrProp);
        $attrProp->setValue($request, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $request->getAttributes());
    }

    public function testGetAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new ReflectionProperty($request, 'attributes');
        $this->setAccessible($attrProp);
        $attrProp->setValue($request, ['foo' => 'bar']);

        $this->assertEquals('bar', $request->getAttribute('foo'));
        $this->assertNull($request->getAttribute('bar'));
        $this->assertEquals(2, $request->getAttribute('bar', 2));
    }

    public function testWithAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new ReflectionProperty($request, 'attributes');
        $this->setAccessible($attrProp);
        $attrProp->setValue($request, ['foo' => 'bar']);
        $clone = $request->withAttribute('test', '123');

        $this->assertEquals('123', $clone->getAttribute('test'));
    }

    public function testWithoutAttribute()
    {
        $request = $this->requestFactory();
        $attrProp = new ReflectionProperty($request, 'attributes');
        $this->setAccessible($attrProp);
        $attrProp->setValue($request, ['foo' => 'bar']);
        $clone = $request->withoutAttribute('foo');

        $this->assertNull($clone->getAttribute('foo'));
    }

    public function testGetParsedBodyWhenAlreadyParsed()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'parsedBody');
        $this->setAccessible($prop);
        $prop->setValue($request, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }

    public function testGetParsedBodyWhenBodyDoesNotExist()
    {
        $request = $this->requestFactory();
        $prop = new ReflectionProperty($request, 'body');
        $this->setAccessible($prop);
        $prop->setValue($request, null);

        $this->assertNull($request->getParsedBody());
    }

    public function testWithParsedBody()
    {
        $clone = $this->requestFactory()->withParsedBody(['xyz' => '123']);

        $this->assertEquals(['xyz' => '123'], $clone->getParsedBody());
    }

    public function testWithParsedBodyEmptyArray()
    {
        $method = 'GET';
        $uri = new Uri('https', 'example.com', 443, '/foo/bar', 'abc=123', '', '');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=utf8');
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();
        $body->write('foo=bar');
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        $clone = $request->withParsedBody([]);

        $this->assertEquals([], $clone->getParsedBody());
    }

    public function testWithParsedBodyNull()
    {
        $method = 'GET';
        $uri = new Uri('https', 'example.com', 443, '/foo/bar', 'abc=123', '', '');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=utf8');
        $cookies = [];
        $serverParams = [];
        $body = (new StreamFactory())->createStream();
        $body->write('foo=bar');
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);

        $clone = $request->withParsedBody(null);

        $this->assertNull($clone->getParsedBody());
    }

    public function testGetParsedBodyReturnsNullWhenThereIsNoBodyData()
    {
        $request = $this->requestFactory(['REQUEST_METHOD' => 'POST']);

        $this->assertNull($request->getParsedBody());
    }

    public function testGetParsedBodyReturnsNullWhenThereIsNoMediaTypeParserRegistered()
    {
        $request = $this->requestFactory([
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'text/csv',
        ]);
        $request->getBody()->write('foo,bar,baz');

        $this->assertNull($request->getParsedBody());
    }

    public function testWithParsedBodyInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->requestFactory()->withParsedBody(2);
    }

    public function testWithParsedBodyInvalidFalseValue()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->requestFactory()->withParsedBody(false);
    }
}
