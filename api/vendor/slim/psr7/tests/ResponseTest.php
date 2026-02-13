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
use Slim\Psr7\Headers;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use stdClass;

use function fopen;
use function property_exists;

class ResponseTest extends TestCase
{
    protected function setAccessible(ReflectionProperty|ReflectionMethod $property, bool $accessible = true): void
    {
        // only if PHP version < 8.1
        if (PHP_VERSION_ID > 80100) {
            return;
        }
        $property->setAccessible($accessible);
    }

    public function testConstructorWithDefaultArgs()
    {
        $response = new Response();

        $headersReflection = new ReflectionProperty($response, 'headers');
        $this->setAccessible($headersReflection);

        $bodyReflection = new ReflectionProperty($response, 'body');
        $this->setAccessible($bodyReflection);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf(Headers::class, $headersReflection->getValue($response));
        $this->assertInstanceOf(Stream::class, $bodyReflection->getValue($response));
    }

    public function testConstructorWithCustomArgs()
    {
        $headers = new Headers();
        $body = new Stream(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);

        $headersReflection = new ReflectionProperty($response, 'headers');
        $this->setAccessible($headersReflection);

        $bodyReflection = new ReflectionProperty($response, 'body');
        $this->setAccessible($bodyReflection);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame($headers, $headersReflection->getValue($response));
        $this->assertSame($body, $bodyReflection->getValue($response));
    }

    public function testDeepCopyClone()
    {
        $headers = new Headers();
        $body = new Stream(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);
        $clone = clone $response;

        $headersReflection = new ReflectionProperty($response, 'headers');
        $this->setAccessible($headersReflection);

        $this->assertEquals(404, $clone->getStatusCode());
        $this->assertEquals('1.1', $clone->getProtocolVersion());
        $this->assertNotSame($headers, $headersReflection->getValue($clone));
    }

    public function testDisableSetter()
    {
        $response = new Response();
        $response->foo = 'bar';

        $this->assertFalse(property_exists($response, 'foo'));
    }

    public function testGetStatusCode()
    {
        $response = new Response();
        $responseStatus = new ReflectionProperty($response, 'status');
        $this->setAccessible($responseStatus);
        $responseStatus->setValue($response, 404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWithStatus()
    {
        $response = new Response();
        $clone = $response->withStatus(302);

        $this->assertEquals(302, $clone->getStatusCode());
    }

    public function testWithStatusInvalidStatusCodeThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $response = new Response();
        $response->withStatus(800);
    }

    public function testWithStatusInvalidReasonPhraseThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Response reason phrase must be a string');

        $response = new Response();
        $response->withStatus(200, null);
    }

    public function testWithStatusEmptyReasonPhrase()
    {
        $responseWithNoMessage = new Response(310);

        $this->assertEquals('', $responseWithNoMessage->getReasonPhrase());
    }

    public function testReasonPhraseContainsCarriageReturn()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason phrase contains one of the following prohibited characters: \r \n');

        $response = new Response();
        $response = $response->withStatus(404, "Not Found\r");
    }

    public function testReasonPhraseContainsLineFeed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason phrase contains one of the following prohibited characters: \r \n');

        $response = new Response();
        $response = $response->withStatus(404, "Not Found\n");
    }

    public function testWithStatusValidReasonPhraseObject()
    {
        $response = new Response();
        $response = $response->withStatus(200, new StringableTestObject('Slim OK'));
        $this->assertEquals('Slim OK', $response->getReasonPhrase());
    }

    public function testGetReasonPhrase()
    {
        $response = new Response(404);

        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function testEmptyReasonPhraseForUnrecognisedCode()
    {
        $response = new Response();
        $response = $response->withStatus(199);

        $this->assertSame('', $response->getReasonPhrase());
    }

    public function testSetReasonPhraseForUnrecognisedCode()
    {
        $response = new Response();
        $response = $response->withStatus(199, 'Random Message');

        $this->assertEquals('Random Message', $response->getReasonPhrase());
    }

    public function testGetCustomReasonPhrase()
    {
        $response = new Response();
        $clone = $response->withStatus(200, 'Custom Phrase');

        $this->assertEquals('Custom Phrase', $clone->getReasonPhrase());
    }

    public function testResponseHeadersDoNotContainAuthorizationHeader()
    {
        $_SERVER = Environment::mock(
            [
                'PHP_AUTH_USER' => 'foo'
            ]
        );

        $response = new Response();
        $this->assertEmpty($response->getHeaders());
    }
}
