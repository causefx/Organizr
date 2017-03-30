<?php

namespace SparkPost\Test;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Nyholm\NSA;
use SparkPost\SparkPost;
use SparkPost\SparkPostPromise;
use GuzzleHttp\Promise\FulfilledPromise as GuzzleFulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise as GuzzleRejectedPromise;
use Http\Adapter\Guzzle6\Promise as GuzzleAdapterPromise;
use Mockery;

class SparkPostTest extends \PHPUnit_Framework_TestCase
{
    private $clientMock;
    /** @var SparkPost */
    private $resource;

    private $exceptionMock;
    private $exceptionBody;

    private $responseMock;
    private $responseBody;

    private $promiseMock;

    private $postTransmissionPayload = [
        'content' => [
            'from' => ['name' => 'Sparkpost Team', 'email' => 'postmaster@sendmailfor.me'],
            'subject' => 'First Mailing From PHP',
            'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
        ],
        'substitution_data' => ['name' => 'Avi'],
        'recipients' => [
            ['address' => 'avi.goldman@sparkpost.com'],
        ],
    ];

    private $getTransmissionPayload = [
        'campaign_id' => 'thanksgiving',
    ];

    /**
     * (non-PHPdoc).
     *
     * @before
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    public function setUp()
    {
        // response mock up
        $responseBodyMock = Mockery::mock();
        $this->responseBody = ['results' => 'yay'];
        $this->responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $this->responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $this->responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($this->responseBody));

        // exception mock up
        $exceptionResponseMock = Mockery::mock();
        $this->exceptionBody = ['results' => 'failed'];
        $this->exceptionMock = Mockery::mock('Http\Client\Exception\HttpException');
        $this->exceptionMock->shouldReceive('getResponse')->andReturn($exceptionResponseMock);
        $exceptionResponseMock->shouldReceive('getStatusCode')->andReturn(500);
        $exceptionResponseMock->shouldReceive('getBody->__toString')->andReturn(json_encode($this->exceptionBody));

        // promise mock up
        $this->promiseMock = Mockery::mock('Http\Promise\Promise');

        //setup mock for the adapter
        $this->clientMock = Mockery::mock('Http\Adapter\Guzzle6\Client');
        $this->clientMock->shouldReceive('sendAsyncRequest')->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($this->promiseMock);

        $this->resource = new SparkPost($this->clientMock, ['key' => 'SPARKPOST_API_KEY']);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testRequestSync()
    {
        $this->resource->setOptions(['async' => false]);
        $this->clientMock->shouldReceive('sendRequest')->andReturn($this->responseMock);

        $this->assertInstanceOf('SparkPost\SparkPostResponse', $this->resource->request('POST', 'transmissions', $this->postTransmissionPayload));
    }

    public function testRequestAsync()
    {
        $promiseMock = Mockery::mock('Http\Promise\Promise');
        $this->resource->setOptions(['async' => true]);
        $this->clientMock->shouldReceive('sendAsyncRequest')->andReturn($promiseMock);

        $this->assertInstanceOf('SparkPost\SparkPostPromise', $this->resource->request('GET', 'transmissions', $this->getTransmissionPayload));
    }

    public function testDebugOptionWhenFalse() {
        $this->resource->setOptions(['async' => false, 'debug' => false]);
        $this->clientMock->shouldReceive('sendRequest')->andReturn($this->responseMock);

        $response = $this->resource->request('POST', 'transmissions', $this->postTransmissionPayload);

        $this->assertEquals($response->getRequest(), null);
    }

    public function testDebugOptionWhenTrue() {
        // setup
        $this->resource->setOptions(['async' => false, 'debug' => true]);

        // successful
        $this->clientMock->shouldReceive('sendRequest')->once()->andReturn($this->responseMock);
        $response = $this->resource->request('POST', 'transmissions', $this->postTransmissionPayload);
        $this->assertEquals(json_decode($response->getRequest()['body'], true), $this->postTransmissionPayload);

        // unsuccessful
        $this->clientMock->shouldReceive('sendRequest')->once()->andThrow($this->exceptionMock);

        try {
            $response = $this->resource->request('POST', 'transmissions', $this->postTransmissionPayload);
        }
        catch (\Exception $e) {
            $this->assertEquals(json_decode($e->getRequest()['body'], true), $this->postTransmissionPayload);
        }
    }

    public function testSuccessfulSyncRequest()
    {
        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($this->responseMock);

        $response = $this->resource->syncRequest('POST', 'transmissions', $this->postTransmissionPayload);

        $this->assertEquals($this->responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnsuccessfulSyncRequest()
    {
        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andThrow($this->exceptionMock);

        try {
            $this->resource->syncRequest('POST', 'transmissions', $this->postTransmissionPayload);
        } catch (\Exception $e) {
            $this->assertEquals($this->exceptionBody, $e->getBody());
            $this->assertEquals(500, $e->getCode());
        }
    }

    public function testSuccessfulAsyncRequestWithWait()
    {
        $this->promiseMock->shouldReceive('wait')->andReturn($this->responseMock);

        $promise = $this->resource->asyncRequest('POST', 'transmissions', $this->postTransmissionPayload);
        $response = $promise->wait();

        $this->assertEquals($this->responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnsuccessfulAsyncRequestWithWait()
    {
        $this->promiseMock->shouldReceive('wait')->andThrow($this->exceptionMock);

        $promise = $this->resource->asyncRequest('POST', 'transmissions', $this->postTransmissionPayload);

        try {
            $response = $promise->wait();
        } catch (\Exception $e) {
            $this->assertEquals($this->exceptionBody, $e->getBody());
            $this->assertEquals(500, $e->getCode());
        }
    }

    public function testSuccessfulAsyncRequestWithThen()
    {
        $guzzlePromise = new GuzzleFulfilledPromise($this->responseMock);
        $result = $this->resource->buildRequest('POST', 'transmissions', $this->postTransmissionPayload, []);

        $promise = new SparkPostPromise(new GuzzleAdapterPromise($guzzlePromise, $result));

        $responseBody = $this->responseBody;
        $promise->then(function ($response) use ($responseBody) {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($responseBody, $response->getBody());
        }, null)->wait();
    }

    public function testUnsuccessfulAsyncRequestWithThen()
    {
        $guzzlePromise = new GuzzleRejectedPromise($this->exceptionMock);
        $result = $this->resource->buildRequest('POST', 'transmissions', $this->postTransmissionPayload, []);

        $promise = new SparkPostPromise(new GuzzleAdapterPromise($guzzlePromise, $result));

        $exceptionBody = $this->exceptionBody;
        $promise->then(null, function ($exception) use ($exceptionBody) {
            $this->assertEquals(500, $exception->getCode());
            $this->assertEquals($exceptionBody, $exception->getBody());
        })->wait();
    }

    public function testPromise()
    {
        $promise = $this->resource->asyncRequest('POST', 'transmissions', $this->postTransmissionPayload);

        $this->promiseMock->shouldReceive('getState')->twice()->andReturn('pending');
        $this->assertEquals($this->promiseMock->getState(), $promise->getState());

        $this->promiseMock->shouldReceive('getState')->twice()->andReturn('rejected');
        $this->assertEquals($this->promiseMock->getState(), $promise->getState());
    }

    /**
     * @expectedException \Exception
     */
    public function testUnsupportedAsyncRequest()
    {
        $this->resource->setHttpClient(Mockery::mock('Http\Client\HttpClient'));

        $this->resource->asyncRequest('POST', 'transmissions', $this->postTransmissionPayload);
    }

    public function testGetHttpHeaders()
    {
        $headers = $this->resource->getHttpHeaders([
            'Custom-Header' => 'testing',
        ]);

        $version = NSA::getProperty($this->resource, 'version');

        $this->assertEquals('SPARKPOST_API_KEY', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('testing', $headers['Custom-Header']);
        $this->assertEquals('php-sparkpost/'.$version, $headers['User-Agent']);
    }

    public function testGetUrl()
    {
        $url = 'https://api.sparkpost.com:443/api/v1/transmissions?key=value 1,value 2,value 3';
        $testUrl = $this->resource->getUrl('transmissions', ['key' => ['value 1', 'value 2', 'value 3']]);
        $this->assertEquals($url, $testUrl);
    }

    public function testSetHttpClient()
    {
        $mock = Mockery::mock(HttpClient::class);
        $this->resource->setHttpClient($mock);
        $this->assertEquals($mock, NSA::getProperty($this->resource, 'httpClient'));
    }

    public function testSetHttpAsyncClient()
    {
        $mock = Mockery::mock(HttpAsyncClient::class);
        $this->resource->setHttpClient($mock);
        $this->assertEquals($mock, NSA::getProperty($this->resource, 'httpClient'));
    }

    /**
     * @expectedException \Exception
     */
    public function testSetHttpClientException()
    {
        $this->resource->setHttpClient(new \stdClass());
    }

    public function testSetOptionsStringKey()
    {
        $this->resource->setOptions('SPARKPOST_API_KEY');
        $options = NSA::getProperty($this->resource, 'options');
        $this->assertEquals('SPARKPOST_API_KEY', $options['key']);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetBadOptions()
    {
        NSA::setProperty($this->resource, 'options', []);
        $this->resource->setOptions(['not' => 'SPARKPOST_API_KEY']);
    }

    public function testSetMessageFactory()
    {
        $messageFactory = Mockery::mock(MessageFactory::class);
        $this->resource->setMessageFactory($messageFactory);

        $this->assertEquals($messageFactory, NSA::invokeMethod($this->resource, 'getMessageFactory'));
    }
}
