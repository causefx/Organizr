<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Factory;

use Interop\Http\Factory\UriFactoryTestCase;
use Slim\Psr7\Environment;
use Slim\Psr7\Factory\UriFactory;

class UriFactoryTest extends UriFactoryTestCase
{
    protected function createUriFactory(): UriFactory
    {
        return new UriFactory();
    }

    public function testGetAuthorityWithUsername()
    {
        $uri = $this->createUriFactory()->createUri('https://josh@example.com/foo/bar?abc=123#section3');

        $this->assertEquals('josh@example.com', $uri->getAuthority());
    }

    public function testGetAuthority()
    {
        $uri = $this->createUriFactory()->createUri('https://example.com/foo/bar?abc=123#section3');

        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function testGetAuthorityWithNonStandardPort()
    {
        $uri = $this->createUriFactory()->createUri('https://example.com:400/foo/bar?abc=123#section3');

        $this->assertEquals('example.com:400', $uri->getAuthority());
    }

    public function testGetUserInfoWithUsernameAndPassword()
    {
        $uri = $this->createUriFactory()->createUri('https://josh:sekrit@example.com:443/foo/bar?abc=123#section3');

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsernameAndPasswordEncodesCorrectly()
    {
        $uri = $this
            ->createUriFactory()
            ->createUri('https://bob@example.com:pass:word@example.com:443/foo/bar?abc=123#section3');

        $this->assertEquals('bob%40example.com:pass%3Aword', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsername()
    {
        $uri = $this->createUriFactory()->createUri('http://josh@example.com/foo/bar?abc=123#section3');

        $this->assertEquals('josh', $uri->getUserInfo());
    }

    public function testGetUserInfoNone()
    {
        $uri = $this->createUriFactory()->createUri('https://example.com/foo/bar?abc=123#section3');

        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testCreateFromString()
    {
        $uri = $this->createUriFactory()->createUri('https://example.com:8080/foo/bar?abc=123');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    public function testCreateFromGlobals()
    {
        $globals = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar?baz=1',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080,
        ]);

        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromGlobalsWithHttps()
    {
        $globals = Environment::mock(
            [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'example.com'
            ]
        );

        // Make the 'SERVER_PORT' empty as we want to test if the default server port gets set correctly.
        $globals['SERVER_PORT'] = '';

        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());

        // The port is expected to be NULL as the server port is the default standard (443 in case of https).
        $this->assertNull($uri->getPort());
    }

    public function testCreateFromGlobalsUsesServerNameAsHostIfHostHeaderIsNotPresent()
    {
        $globals = Environment::mock([
            'SERVER_NAME' => 'example.com',
        ]);

        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('example.com', $uri->getHost());
    }

    public function testCreateFromGlobalWithIPv6HostNoPort()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]',
            'REMOTE_ADDR' => '2001:db8::1',
            'SERVER_PORT' => 8080,
        ]);
        $uri = $this->createUriFactory()->createFromGlobals($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromGlobalsWithIPv6HostWithPort()
    {
        $globals = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]:8080',
            'REMOTE_ADDR' => '2001:db8::1',
            'SERVER_PORT' => 8080,
        ]);

        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromGlobalsWithBasePathContainingSpace()
    {
        $globals = Environment::mock([
            'SCRIPT_NAME' => "/f'oo bar/index.php",
            'REQUEST_URI' => "/f%27oo%20bar/baz",
        ]);
        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('/f%27oo%20bar/baz', $uri->getPath());
    }

    public function testWithPathWhenBaseRootIsEmpty()
    {
        $globals = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/bar',
        ]);
        $uri = $this->createUriFactory()->createFromGlobals($globals);

        $this->assertEquals('http://localhost/test', (string) $uri->withPath('test'));
    }

    /**
     * When the URL is /foo/index.php/bar/baz, we need the baseURL to be
     * /foo/index.php so that routing works correctly.
     *
     * @ticket 1639 as a fix to 1590 broke this.
     */
    public function testRequestURIContainsIndexDotPhp()
    {
        $uri = $this->createUriFactory()->createFromGlobals(
            Environment::mock(
                [
                    'SCRIPT_NAME' => '/foo/index.php',
                    'REQUEST_URI' => '/foo/index.php/bar/baz',
                ]
            )
        );
        $this->assertSame('/foo/index.php/bar/baz', $uri->getPath());
    }

    public function testRequestURICanContainParams()
    {
        $uri = $this->createUriFactory()->createFromGlobals(
            Environment::mock(
                [
                    'REQUEST_URI' => '/foo?abc=123',
                ]
            )
        );
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    public function testUriDistinguishZeroFromEmptyString()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $this->assertSame($expected, (string) $this->createUriFactory()->createUri($expected));
    }
}
