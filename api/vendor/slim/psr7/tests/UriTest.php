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
use Slim\Psr7\Uri;
use stdClass;

class UriTest extends TestCase
{
    public function uriFactory(): Uri
    {
        $scheme = 'https';
        $host = 'example.com';
        $port = 443;
        $path = '/foo/bar';
        $query = 'abc=123';
        $fragment = 'section3';
        $user = 'josh';
        $password = 'sekrit';

        return new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);
    }

    public function testSupportOtherSchemes()
    {
        $wsUri = new class ('ws', 'example.com') extends Uri {
            public const SUPPORTED_SCHEMES = [
                'ws' => 80,
                'wss' => 443,
            ];
        };

        $this->assertEquals('ws', $wsUri->getScheme());
    }

    public function testGetScheme()
    {
        $this->assertEquals('https', $this->uriFactory()->getScheme());
    }

    public function testWithScheme()
    {
        $uri = $this->uriFactory()->withScheme('http');

        $this->assertEquals('http', $uri->getScheme());
    }

    public function testWithSchemeRemovesSuffix()
    {
        $uri = $this->uriFactory()->withScheme('http://');

        $this->assertEquals('http', $uri->getScheme());
    }

    public function testWithSchemeEmpty()
    {
        $uri = $this->uriFactory()->withScheme('');

        $this->assertEquals('', $uri->getScheme());
    }

    public function testWithSchemeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Uri scheme must be one of:.*$/');

        $this->uriFactory()->withScheme('ftp');
    }

    public function testWithSchemeInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri scheme must be a string');

        $this->uriFactory()->withScheme([]);
    }

    public function testGetAuthorityWithUsernameAndPassword()
    {
        $this->assertEquals('josh:sekrit@example.com', $this->uriFactory()->getAuthority());
    }

    public function testWithUserInfo()
    {
        $uri = $this->uriFactory()->withUserInfo('bob', 'pass');

        $this->assertEquals('bob:pass', $uri->getUserInfo());
    }

    public function testWithUserInfoEncodesCorrectly()
    {
        $uri = $this->uriFactory()->withUserInfo('bob@example.com', 'pass:word');

        $this->assertEquals('bob%40example.com:pass%3Aword', $uri->getUserInfo());
    }

    public function testWithUserInfoRemovesPassword()
    {
        $uri = $this->uriFactory()->withUserInfo('bob');

        $this->assertEquals('bob', $uri->getUserInfo());
    }

    public function testWithUserInfoRemovesInfo()
    {
        $uri = $this->uriFactory()->withUserInfo('bob', 'password');
        $uri = $uri->withUserInfo('');

        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testGetHost()
    {
        $this->assertEquals('example.com', $this->uriFactory()->getHost());
    }

    public function testWithHost()
    {
        $uri = $this->uriFactory()->withHost('slimframework.com');

        $this->assertEquals('slimframework.com', $uri->getHost());
    }

    public function testWithHostValidObject()
    {
        $mock = new StringableTestObject('host.test');

        $uri = $this->uriFactory()->withHost($mock);
        $this->assertEquals('host.test', $uri->getHost());
    }

    public function testWithHostInvalidObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri host must be a string');

        $this->uriFactory()->withHost(new stdClass());
    }

    public function testFilterHost()
    {
        $uri = new Uri('http', '2001:db8::1');

        $this->assertEquals('[2001:db8::1]', $uri->getHost());
    }

    public function testGetPortWithSchemeAndNonDefaultPort()
    {
        $uri = new Uri('https', 'www.example.com', 4000);

        $this->assertEquals(4000, $uri->getPort());
    }

    public function testGetPortWithSchemeAndDefaultPort()
    {
        $uriHttp = new Uri('http', 'www.example.com', 80);
        $uriHttps = new Uri('https', 'www.example.com', 443);

        $this->assertNull($uriHttp->getPort());
        $this->assertNull($uriHttps->getPort());
    }

    public function testGetPortWithoutSchemeAndPort()
    {
        $uri = new Uri('', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testGetPortWithSchemeWithoutPort()
    {
        $uri = new Uri('http', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testWithPort()
    {
        $uri = $this->uriFactory()->withPort(8000);

        $this->assertEquals(8000, $uri->getPort());
    }

    public function testWithPortNull()
    {
        $uri = $this->uriFactory()->withPort(null);

        $this->assertEquals(null, $uri->getPort());
    }

    public function testWithPortInvalidInt()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->uriFactory()->withPort(70000);
    }

    public function testWithPortInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->uriFactory()->withPort('Foo');
    }

    public function testWithPortIntegerAsString()
    {
        $uri = $this->uriFactory()->withPort("199");

        $this->assertEquals(199, $uri->getPort());
    }

    public function testGetPath()
    {
        $this->assertEquals('/foo/bar', $this->uriFactory()->getPath());
    }

    public function testWithPath()
    {
        $uri = $this->uriFactory()->withPath('/new');

        $this->assertEquals('/new', $uri->getPath());
    }

    public function testWithPathWithoutPrefix()
    {
        $uri = $this->uriFactory()->withPath('new');

        $this->assertEquals('new', $uri->getPath());
    }

    public function testWithPathEmptyValue()
    {
        $uri = $this->uriFactory()->withPath('');

        $this->assertEquals('', $uri->getPath());
    }

    public function testWithPathUrlEncodesInput()
    {
        $uri = $this->uriFactory()->withPath('/includes?/new');

        $this->assertEquals('/includes%3F/new', $uri->getPath());
    }

    public function testWithPathDoesNotDoubleEncodeInput()
    {
        $uri = $this->uriFactory()->withPath('/include%25s/new');

        $this->assertEquals('/include%25s/new', $uri->getPath());
    }

    public function testWithPathInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri path must be a string');

        $this->uriFactory()->withPath(['foo']);
    }

    public function testGetQuery()
    {
        $this->assertEquals('abc=123', $this->uriFactory()->getQuery());
    }

    public function testWithQuery()
    {
        $uri = $this->uriFactory()->withQuery('xyz=123');

        $this->assertEquals('xyz=123', $uri->getQuery());
    }

    public function testWithQueryRemovesPrefix()
    {
        $uri = $this->uriFactory()->withQuery('?xyz=123');

        $this->assertEquals('xyz=123', $uri->getQuery());
    }

    public function testWithQueryEmpty()
    {
        $uri = $this->uriFactory()->withQuery('');

        $this->assertEquals('', $uri->getQuery());
    }

    public function testWithQueryValidObject()
    {
        $mock = new StringableTestObject('xyz=123');

        $uri = $this->uriFactory()->withQuery($mock);
        $this->assertEquals('xyz=123', $uri->getQuery());
    }

    public function testFilterQuery()
    {
        $uri = $this->uriFactory()->withQuery('?foobar=%match');

        $this->assertEquals('foobar=%25match', $uri->getQuery());
    }

    public function testWithQueryInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri query must be a string');

        $this->uriFactory()->withQuery(['foo']);
    }

    public function testGetFragment()
    {
        $this->assertEquals('section3', $this->uriFactory()->getFragment());
    }

    public function testWithFragment()
    {
        $uri = $this->uriFactory()->withFragment('other-fragment');

        $this->assertEquals('other-fragment', $uri->getFragment());
    }

    public function testWithFragmentRemovesPrefix()
    {
        $uri = $this->uriFactory()->withFragment('#other-fragment');

        $this->assertEquals('other-fragment', $uri->getFragment());
    }

    public function testWithFragmentEmpty()
    {
        $uri = $this->uriFactory()->withFragment('');

        $this->assertEquals('', $uri->getFragment());
    }

    public function testWithFragmentValidObject()
    {
        $mock = new StringableTestObject('other-fragment');

        $uri = $this->uriFactory()->withFragment($mock);
        $this->assertEquals('other-fragment', $uri->getFragment());
    }

    public function testWithFragmentUrlEncode()
    {
        $uri = $this->uriFactory()->withFragment('^a');

        $this->assertEquals('%5Ea', $uri->getFragment());
    }

    public function testWithFragmentInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri fragment must be a string');

        $this->uriFactory()->withFragment(['foo']);
    }

    public function testToString()
    {
        $uri = $this->uriFactory();

        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('/bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withScheme('')->withHost('')->withPort(null)->withUserInfo('')->withPath('//bar');
        $this->assertEquals('/bar?abc=123#section3', (string) $uri);
    }
}
