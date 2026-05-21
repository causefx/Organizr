<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Environment;

class EnvironmentTest extends TestCase
{
    public function testMock()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/foo/bar/index.php',
            'REQUEST_URI' => '/foo/bar?abc=123',
        ]);

        $this->assertEquals('/foo/bar/index.php', $env['SCRIPT_NAME']);
        $this->assertEquals('/foo/bar?abc=123', $env['REQUEST_URI']);
    }

    public function testMockHttps()
    {
        $env = Environment::mock([
            'HTTPS' => 'on'
        ]);

        $this->assertEquals('on', $env['HTTPS']);
        $this->assertEquals(443, $env['SERVER_PORT']);
    }

    public function testMockRequestScheme()
    {
        $env = Environment::mock([
            'REQUEST_SCHEME' => 'https'
        ]);

        $this->assertEquals('https', $env['REQUEST_SCHEME']);
        $this->assertEquals(443, $env['SERVER_PORT']);
    }
}
