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
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\DataProvider;
use Slim\Psr7\Headers;
use stdClass;

use function base64_encode;

class HeadersTest extends TestCase
{
    public function testCreateFromGlobals()
    {
        $GLOBALS['getallheaders_return'] = [
            'HTTP_ACCEPT' => 'application/json',
        ];

        $headers = Headers::createFromGlobals();

        unset($GLOBALS['getallheaders_return']);

        $this->assertEquals(['accept' => ['application/json']], $headers->getHeaders());
        $this->assertEquals(['ACCEPT' => ['application/json']], $headers->getHeaders(true));
    }

    public function testCreateFromGlobalsUsesEmptyArrayIfGetAllHeadersReturnsFalse()
    {
        $GLOBALS['getallheaders_return'] = false;

        $headers = Headers::createFromGlobals();

        unset($GLOBALS['getallheaders_return']);

        $this->assertEquals([], $headers->getHeaders());
    }

    public function testAddHeader()
    {
        $headers = new Headers([
            'Accept' => 'application/json',
        ]);

        $headers->addHeader('Accept', 'text/html');

        $this->assertEquals(['application/json', 'text/html'], $headers->getHeader('Accept'));
        $this->assertEquals(['accept' => ['application/json', 'text/html']], $headers->getHeaders());
        $this->assertEquals(['Accept' => ['application/json', 'text/html']], $headers->getHeaders(true));
    }

    public function testAddHeaderValueEmptyArray()
    {
        $this->expectException(InvalidArgumentException::class);

        $headers = new Headers();
        $headers->addHeader('Header', []);
    }

    public function testRemoveHeader()
    {
        $headers = new Headers([
            'Accept' => 'application/json',
        ]);

        $headers->removeHeader('Accept');

        $this->assertEquals([], $headers->getHeader('Accept'));
        $this->assertEquals([], $headers->getHeaders());
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testRemoveHeaderByIncompatibleStringWithRFC()
    {
        $headers = new Headers();
        $headers->removeHeader('<incompatible with RFC>');
    }

    public function testGetHeader()
    {
        $headers = new Headers([
            'Accept' => ['application/json', 'text/html'],
        ]);

        $this->assertEquals(['application/json', 'text/html'], $headers->getHeader('accept'));
        $this->assertEquals(['application/json', 'text/html'], $headers->getHeader('Accept'));
        $this->assertEquals(['application/json', 'text/html'], $headers->getHeader('HTTP_ACCEPT'));
    }

    public function testGetHeaderReturnsValidatedAndTrimedHeaderDefaultValue()
    {
        $headers = new Headers([]);

        $this->assertEquals(['application/json'], $headers->getHeader('accept', ' application/json'));
    }

    public function testGetHeaderThrowsExceptionWithInvalidDefaultArgument()
    {
        $this->expectException(InvalidArgumentException::class);

        $headers = new Headers([]);

        $headers->getHeader('accept', new stdClass());
    }

    public function testSetHeader()
    {
        $headers = new Headers([
            'Content-Length' => 0,
        ]);

        $headers->setHeader('Content-Length', 100);

        $this->assertSame(['100'], $headers->getHeader('Content-Length'));
        $this->assertEquals(['content-length' => ['100']], $headers->getHeaders());
        $this->assertEquals(['Content-Length' => ['100']], $headers->getHeaders(true));
    }

    public function testSetHeaderPreservesOriginalCaseIfHeaderAlreadyExists()
    {
        $headers = new Headers([
            'CONTENT-LENGTH' => 0,
        ]);

        $headers->setHeader('Content-Length', 100);

        $this->assertEquals(['content-length' => ['100']], $headers->getHeaders());
        $this->assertEquals(['CONTENT-LENGTH' => ['100']], $headers->getHeaders(true));
    }

    public function testSetHeaders()
    {
        $headers = new Headers([
            'Content-Length' => 0,
        ]);

        $headers->setHeaders([
            'Accept' => 'application/json',
        ]);

        $this->assertEquals(['accept' => ['application/json']], $headers->getHeaders());
        $this->assertEquals(['Accept' => ['application/json']], $headers->getHeaders(true));
    }

    public function testHasHeader()
    {
        $headers = new Headers([
            'Accept' => 'application/json',
        ]);

        $this->assertTrue($headers->hasHeader('accept'));
        $this->assertTrue($headers->hasHeader('Accept'));
        $this->assertTrue($headers->hasHeader('HTTP_ACCEPT'));
    }

    public function testGetHeaders()
    {
        $headers = new Headers([
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_CONTENT_TYPE' => 'application/json',
        ]);

        $expectedNormalizedHeaders = [
            'accept' => ['text/html'],
            'content-type' => ['application/json'],
        ];

        $this->assertEquals($expectedNormalizedHeaders, $headers->getHeaders());
    }

    public function testGetHeadersPreservesOriginalCase()
    {
        $headers = new Headers([
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_CONTENT_TYPE' => 'application/json',
        ]);

        $expectedOriginalHeaders = [
            'ACCEPT' => ['text/html'],
            'CONTENT-TYPE' => ['application/json'],
        ];

        $this->assertEquals($expectedOriginalHeaders, $headers->getHeaders(true));
    }

    public function testParseAuthorizationHeader()
    {
        $expectedValue = 'Basic ' . base64_encode('user:password');

        $headers = new Headers(['Authorization' => $expectedValue]);
        $this->assertEquals([$expectedValue], $headers->getHeader('Authorization'));

        $headers = new Headers([], ['REDIRECT_HTTP_AUTHORIZATION' => 'cookie']);
        $this->assertEquals(['cookie'], $headers->getHeader('Authorization'));

        $headers = new Headers([], ['PHP_AUTH_USER' => 'user', 'PHP_AUTH_PW' => 'password']);
        $this->assertEquals([$expectedValue], $headers->getHeader('Authorization'));

        $headers = new Headers([], ['PHP_AUTH_DIGEST' => 'digest']);
        $this->assertEquals(['digest'], $headers->getHeader('Authorization'));
    }

    /**
     * @dataProvider provideInvalidHeaderNames
     */
    #[DataProvider('provideInvalidHeaderNames')]
    public function testWithInvalidHeaderName($headerName): void
    {
        $headers = new Headers();

        $this->expectException(\InvalidArgumentException::class);

        $headers->setHeader($headerName, 'foo');
    }

    public static function provideInvalidHeaderNames(): array
    {
        return [
            [[]],
            [false],
            [new \stdClass()],
            ["Content-Type\r\n\r\n"],
            ["Content-Type\r\n"],
            ["Content-Type\n"],
            ["\r\nContent-Type"],
            ["\nContent-Type"],
            ["\n"],
            ["\r\n"],
            ["\t"],
        ];
    }

    /**
     * @dataProvider provideInvalidHeaderValues
     */
    #[DataProvider('provideInvalidHeaderValues')]
    public function testSetInvalidHeaderValue($headerValue)
    {
        $headers = new Headers();

        $this->expectException(\InvalidArgumentException::class);

        $headers->setHeader('Content-Type', $headerValue);
    }

    public static function provideInvalidHeaderValues(): array
    {
        // Explicit tests for newlines as the most common exploit vector.
        $tests = [
            ["new\nline"],
            ["new\r\nline"],
            ["new\rline"],
            ["new\r\n line"],
            ["newline\n"],
            ["\nnewline"],
            ["newline\r\n"],
            ["\n\rnewline"],
        ];

        for ($i = 0; $i <= 0xff; $i++) {
            if (\chr($i) == "\t") {
                continue;
            }
            if (\chr($i) == " ") {
                continue;
            }
            if ($i >= 0x21 && $i <= 0x7e) {
                continue;
            }
            if ($i >= 0x80) {
                continue;
            }

            $tests[] = ["foo" . \chr($i) . "bar"];
            $tests[] = ["foo" . \chr($i)];
        }

        return $tests;
    }
}
