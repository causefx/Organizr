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
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Slim\Psr7\Cookies;
use stdClass;

use function gmdate;
use function json_decode;
use function strtotime;
use function time;

class CookiesTest extends TestCase
{
    public function testConstructor()
    {
        $cookies = new Cookies([
            'test' => 'Works',
        ]);
        $prop = new ReflectionProperty($cookies, 'requestCookies');
        $this->setAccessible($prop);
        $this->assertNotEmpty($prop->getValue($cookies)['test']);
        $this->assertEquals('Works', $prop->getValue($cookies)['test']);
    }

    protected function setAccessible(ReflectionProperty|ReflectionMethod $property, bool $accessible = true): void
    {
        // only if PHP version < 8.1
        if (PHP_VERSION_ID > 80100) {
            return;
        }
        $property->setAccessible($accessible);
    }

    public function testSetDefaults()
    {
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => null
        ];

        $cookies = new Cookies();

        $prop = new ReflectionProperty($cookies, 'defaults');
        $this->setAccessible($prop);

        $origDefaults = $prop->getValue($cookies);

        $cookies->setDefaults($defaults);

        $this->assertEquals($defaults, $prop->getValue($cookies));
        $this->assertNotEquals($origDefaults, $prop->getValue($cookies));
    }

    public function testSetCookieValues()
    {
        $cookies = new Cookies();
        $cookies->set('foo', 'bar');

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $this->setAccessible($prop);

        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => false,
                'httponly' => false,
                'samesite' => null
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testSetCookieValuesContainDefaults()
    {
        $cookies = new Cookies();
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'lax'
        ];

        $cookies->setDefaults($defaults);
        $cookies->set('foo', 'bar');

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $this->setAccessible($prop);

        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'lax'
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testSetCookieValuesCanOverrideDefaults()
    {
        $cookies = new Cookies();
        $defaults = [
            'value' => 'toast',
            'domain' => null,
            'hostonly' => null,
            'path' => null,
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'lax'
        ];

        $cookies->setDefaults($defaults);
        $cookies->set('foo', ['value' => 'bar', 'secure' => false, 'samesite' => 'strict']);

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $this->setAccessible($prop);

        $expectedValue = [
            'foo' => [
                'value' => 'bar',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => false,
                'httponly' => true,
                'samesite' => 'strict'
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testSetSameSiteCookieValuesAreCaseInsensitive()
    {
        $cookies = new Cookies();
        $defaults = [
            'value' => 'bacon',
            'samesite' => 'lax'
        ];

        $cookies->setDefaults($defaults);
        $cookies->set('breakfast', ['samesite' => 'StricT']);

        $prop = new ReflectionProperty($cookies, 'responseCookies');
        $this->setAccessible($prop);

        $expectedValue = [
            'breakfast' => [
                'value' => 'bacon',
                'domain' => null,
                'hostonly' => null,
                'path' => null,
                'expires' => null,
                'secure' => false,
                'httponly' => false,
                'samesite' => 'StricT',
            ]
        ];

        $this->assertEquals($expectedValue, $prop->getValue($cookies));
    }

    public function testGet()
    {
        $cookies = new Cookies(['foo' => 'bar', 'baz' => null]);
        $this->assertEquals('bar', $cookies->get('foo'));
        $this->assertNull($cookies->get('baz', 'defaultValue'));
        $this->assertNull($cookies->get('missing'));
        $this->assertEquals('defaultValue', $cookies->get('missing', 'defaultValue'));
    }

    public function testParseHeader()
    {
        $cookies = Cookies::parseHeader('foo=bar; name=Josh');
        $this->assertEquals('bar', $cookies['foo']);
        $this->assertEquals('Josh', $cookies['name']);
    }

    public function testParseHeaderWithJsonArray()
    {
        $cookies = Cookies::parseHeader('foo=bar; testarray=["someVar1","someVar2","someVar3"]');
        $this->assertEquals('bar', $cookies['foo']);
        $this->assertContains('someVar3', json_decode($cookies['testarray']));
    }

    public function testToHeaders()
    {
        $cookies = new Cookies();
        $cookies->set('test', 'Works');
        $cookies->set('test_array', ['value' => 'bar', 'domain' => 'example.com']);
        $this->assertEquals('test=Works', $cookies->toHeaders()[0]);
        $this->assertEquals('test_array=bar; domain=example.com', $cookies->toHeaders()[1]);
    }

    public function testToHeader()
    {
        $cookies = new Cookies();
        $class = new ReflectionClass($cookies);
        $method = $class->getMethod('toHeader');
        $this->setAccessible($method);
        $properties = [
            'name' => 'test',
            'properties' => [
                'value' => 'Works'
            ]
        ];
        $time = time();
        $formattedDate = gmdate('D, d M Y H:i:s \G\M\T', $time);
        $propertiesComplex = [
            'name' => 'test_complex',
            'properties' => [
                'value' => 'Works',
                'domain' => 'example.com',
                'expires' => $time,
                'path' => '/',
                'secure' => true,
                'hostonly' => true,
                'httponly' => true,
                'samesite' => 'lax'
            ]
        ];
        $stringDate = '2016-01-01 12:00:00';
        $formattedStringDate = gmdate('D, d M Y H:i:s \G\M\T', strtotime($stringDate));
        $propertiesStringDate = [
            'name' => 'test_date',
            'properties' => [
                'value' => 'Works',
                'expires' => $stringDate,
            ]
        ];
        $cookie = $method->invokeArgs($cookies, $properties);
        $cookieComplex = $method->invokeArgs($cookies, $propertiesComplex);
        $cookieStringDate = $method->invokeArgs($cookies, $propertiesStringDate);
        $this->assertEquals('test=Works', $cookie);
        $this->assertEquals(
            'test_complex=Works; domain=example.com; path=/; expires='
            . $formattedDate . '; secure; HostOnly; HttpOnly; SameSite=lax',
            $cookieComplex
        );
        $this->assertEquals('test_date=Works; expires=' . $formattedStringDate, $cookieStringDate);
    }

    public function testParseHeaderException()
    {
        $this->expectException(InvalidArgumentException::class);

        Cookies::parseHeader(new stdClass());
    }

    public function testSetSameSiteNoneToHeaders()
    {
        $cookies = new Cookies();
        $cookies->set('foo', ['value' => 'bar', 'samesite' => 'None']);
        $this->assertEquals('foo=bar; SameSite=None', $cookies->toHeaders()[0]);
    }
}
