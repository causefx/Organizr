<?php
namespace Transmission\Tests;

use Transmission\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    /**
     * @test
     */
    public function shouldHaveDefaultHost()
    {
        $this->assertEquals('localhost', $this->getClient()->getHost());
    }

    /**
     * @test
     */
    public function shouldHaveDefaultPort()
    {
        $this->assertEquals(9091, $this->getClient()->getPort());
    }

    /**
     * @test
     */
    public function shouldHaveNoTokenOnInstantiation()
    {
        $this->assertEmpty($this->getClient()->getToken());
    }

    /**
     * @test
     */
    public function shouldHaveDefaultClient()
    {
        $this->assertInstanceOf('Buzz\Client\Curl', $this->getClient()->getClient());
    }

    /**
     * @test
     */
    public function shouldGenerateDefaultUrl()
    {
        $this->assertEquals('http://localhost:9091', $this->getClient()->getUrl());
    }

    /**
     * @test
     */
    public function shouldMakeApiCall()
    {
        $test   = $this;
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->once())
            ->method('send')
            ->with(
                $this->isInstanceOf('Buzz\Message\Request'),
                $this->isInstanceOf('Buzz\Message\Response')
            )
            ->will($this->returnCallback(function ($request, $response) use ($test) {
                $test->assertEquals('POST', $request->getMethod());
                $test->assertEquals('/transmission/rpc', $request->getResource());
                $test->assertEquals('http://localhost:9091', $request->getHost());
                $test->assertEmpty($request->getHeader('X-Transmission-Session-Id'));
                $test->assertEquals('{"method":"foo","arguments":{"bar":"baz"}}', $request->getContent());

                $response->addHeader('HTTP/1.1 200 OK');
                $response->addHeader('Content-Type: application/json');
                $response->addHeader('X-Transmission-Session-Id: foo');
                $response->setContent('{"foo":"bar"}');
            }));

        $this->getClient()->setClient($client);
        $response = $this->getClient()->call('foo', array('bar' => 'baz'));

        $this->assertInstanceOf('stdClass', $response);
        $this->assertObjectHasAttribute('foo', $response);
        $this->assertEquals('bar', $response->foo);
    }

    /**
     * @test
     */
    public function shouldAuthenticate()
    {
        $test   = $this;
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->once())
            ->method('send')
            ->with(
                $this->isInstanceOf('Buzz\Message\Request'),
                $this->isInstanceOf('Buzz\Message\Response')
            )
            ->will($this->returnCallback(function ($request, $response) use ($test) {
                $test->assertEquals('POST', $request->getMethod());
                $test->assertEquals('/transmission/rpc', $request->getResource());
                $test->assertEquals('http://localhost:9091', $request->getHost());
                $test->assertEmpty($request->getHeader('X-Transmission-Session-Id'));
                $test->assertEquals('Basic '. base64_encode('foo:bar'), $request->getHeader('Authorization'));
                $test->assertEquals('{"method":"foo","arguments":{"bar":"baz"}}', $request->getContent());

                $response->addHeader('HTTP/1.1 200 OK');
                $response->addHeader('Content-Type: application/json');
                $response->addHeader('X-Transmission-Session-Id: foo');
                $response->setContent('{"foo":"bar"}');
            }));

        $this->getClient()->authenticate('foo', 'bar');
        $this->getClient()->setClient($client);
        $response = $this->getClient()->call('foo', array('bar' => 'baz'));

        $this->assertInstanceOf('stdClass', $response);
        $this->assertObjectHasAttribute('foo', $response);
        $this->assertEquals('bar', $response->foo);
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function shouldThrowExceptionOnExceptionDuringApiCall()
    {
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception()));

        $this->getClient()->setClient($client);
        $this->getClient()->call('foo', array());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function shouldThrowExceptionOnUnexpectedStatusCode()
    {
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request, $response) {
                $response->addHeader('HTTP/1.1 500 Internal Server Error');
            }));

        $this->getClient()->setClient($client);
        $this->getClient()->call('foo', array());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function shouldThrowExceptionOnAccessDenied()
    {
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->once())
            ->method('send')
            ->will($this->returnCallback(function ($request, $response) {
                $response->addHeader('HTTP/1.1 401 Access Denied');
            }));

        $this->getClient()->setClient($client);
        $this->getClient()->call('foo', array());
    }

    /**
     * @test
     */
    public function shouldHandle409ResponseWhenMakingAnApiCall()
    {
        $test   = $this;
        $client = $this->getMock('Buzz\Client\Curl');
        $client->expects($this->at(0))
            ->method('send')
            ->will($this->returnCallback(function ($request, $response) use ($test) {
                $test->assertEmpty($request->getHeader('X-Transmission-Session-Id'));

                $response->addHeader('HTTP/1.1 409 Conflict');
                $response->addHeader('X-Transmission-Session-Id: foo');
            }));

        $client->expects($this->at(1))
            ->method('send')
            ->will($this->returnCallback(function ($request, $response) {
                $response->addHeader('HTTP/1.1 200 OK');
                $response->addHeader('Content-Type: application/json');
                $response->addHeader('X-Transmission-Session-Id: foo');
                $response->setContent('{"foo":"bar"}');
            }));

        $this->getClient()->setClient($client);
        $response = $this->getClient()->call('foo', array());

        $this->assertEquals('foo', $this->getClient()->getToken());
        $this->assertInstanceOf('stdClass', $response);
        $this->assertObjectHasAttribute('foo', $response);
        $this->assertEquals('bar', $response->foo);
    }

    public function setup()
    {
        $this->client = new Client();
    }

    private function getClient()
    {
        return $this->client;
    }
}
