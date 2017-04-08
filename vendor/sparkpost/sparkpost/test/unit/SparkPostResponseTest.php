<?php

namespace SparkPost\Test;

use SparkPost\SparkPostResponse;
use Mockery;

class SparkPostResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * (non-PHPdoc).
     *
     * @before
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        $this->returnValue = 'some_value_to_return';
        $this->responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
    }

    public function testGetProtocolVersion()
    {
        $this->responseMock->shouldReceive('getProtocolVersion')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getProtocolVersion(), $sparkpostResponse->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $param = 'protocol version';

        $this->responseMock->shouldReceive('withProtocolVersion')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withProtocolVersion($param), $sparkpostResponse->withProtocolVersion($param));
    }

    public function testGetHeaders()
    {
        $this->responseMock->shouldReceive('getHeaders')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getHeaders(), $sparkpostResponse->getHeaders());
    }

    public function testHasHeader()
    {
        $param = 'header';

        $this->responseMock->shouldReceive('hasHeader')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->hasHeader($param), $sparkpostResponse->hasHeader($param));
    }

    public function testGetHeader()
    {
        $param = 'header';

        $this->responseMock->shouldReceive('getHeader')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getHeader($param), $sparkpostResponse->getHeader($param));
    }

    public function testGetHeaderLine()
    {
        $param = 'header';

        $this->responseMock->shouldReceive('getHeaderLine')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getHeaderLine($param), $sparkpostResponse->getHeaderLine($param));
    }

    public function testWithHeader()
    {
        $param = 'header';
        $param2 = 'value';

        $this->responseMock->shouldReceive('withHeader')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withHeader($param, $param2), $sparkpostResponse->withHeader($param, $param2));
    }

    public function testWithAddedHeader()
    {
        $param = 'header';
        $param2 = 'value';

        $this->responseMock->shouldReceive('withAddedHeader')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withAddedHeader($param, $param2), $sparkpostResponse->withAddedHeader($param, $param2));
    }

    public function testWithoutHeader()
    {
        $param = 'header';

        $this->responseMock->shouldReceive('withoutHeader')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withoutHeader($param), $sparkpostResponse->withoutHeader($param));
    }

    public function testGetRequest()
    {
        $request = ['some' => 'request'];
        $this->responseMock->shouldReceive('getRequest')->andReturn($request);
        $sparkpostResponse = new SparkPostResponse($this->responseMock, $request);
        $this->assertEquals($sparkpostResponse->getRequest(), $request);
    }

    public function testWithBody()
    {
        $param = Mockery::mock('Psr\Http\Message\StreamInterface');

        $this->responseMock->shouldReceive('withBody')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withBody($param), $sparkpostResponse->withBody($param));
    }

    public function testGetStatusCode()
    {
        $this->responseMock->shouldReceive('getStatusCode')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getStatusCode(), $sparkpostResponse->getStatusCode());
    }

    public function testWithStatus()
    {
        $param = 'status';

        $this->responseMock->shouldReceive('withStatus')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->withStatus($param), $sparkpostResponse->withStatus($param));
    }

    public function testGetReasonPhrase()
    {
        $this->responseMock->shouldReceive('getReasonPhrase')->andReturn($this->returnValue);
        $sparkpostResponse = new SparkPostResponse($this->responseMock);
        $this->assertEquals($this->responseMock->getReasonPhrase(), $sparkpostResponse->getReasonPhrase());
    }
}
