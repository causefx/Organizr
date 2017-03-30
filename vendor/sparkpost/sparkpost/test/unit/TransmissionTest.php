<?php

namespace SparkPost\Test;

use SparkPost\SparkPost;
use Mockery;

class TransmissionTest extends \PHPUnit_Framework_TestCase
{
    private $clientMock;
    /** @var SparkPost */
    private $resource;

    private $postTransmissionPayload = [
        'content' => [
            'from' => ['name' => 'Sparkpost Team', 'email' => 'postmaster@sendmailfor.me'],
            'subject' => 'First Mailing From PHP',
            'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
        ],
        'substitution_data' => ['name' => 'Avi'],
        'recipients' => [
            [
                'address' => [
                    'name' => 'Vincent',
                    'email' => 'vincent.song@sparkpost.com',
                ],
            ],
            ['address' => 'test@example.com'],
        ],
        'cc' => [
            [
                'address' => [
                    'email' => 'avi.goldman@sparkpost.com',
                ],
            ],
        ],
        'bcc' => [
            ['address' => 'Emely Giraldo <emely.giraldo@sparkpost.com>'],
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
        //setup mock for the adapter
        $this->clientMock = Mockery::mock('Http\Adapter\Guzzle6\Client');

        $this->resource = new SparkPost($this->clientMock, ['key' => 'SPARKPOST_API_KEY', 'async' => false]);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidEmailFormat()
    {
        $this->postTransmissionPayload['recipients'][] = [
            'address' => 'invalid email format',
        ];

        $response = $this->resource->transmissions->post($this->postTransmissionPayload);
    }

    public function testGet()
    {
        $responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $responseBodyMock = Mockery::mock();

        $responseBody = ['results' => 'yay'];

        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($responseMock);

        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($responseBody));

        $response = $this->resource->transmissions->get($this->getTransmissionPayload);

        $this->assertEquals($responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPut()
    {
        $responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $responseBodyMock = Mockery::mock();

        $responseBody = ['results' => 'yay'];

        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($responseMock);

        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($responseBody));

        $response = $this->resource->transmissions->put($this->getTransmissionPayload);

        $this->assertEquals($responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPost()
    {
        $responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $responseBodyMock = Mockery::mock();

        $responseBody = ['results' => 'yay'];

        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($responseMock);

        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($responseBody));

        $response = $this->resource->transmissions->post($this->postTransmissionPayload);

        $this->assertEquals($responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPostWithRecipientList()
    {
        $postTransmissionPayload = $this->postTransmissionPayload;
        $postTransmissionPayload['recipients'] = ['list_id' => 'SOME_LIST_ID'];

        $responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $responseBodyMock = Mockery::mock();

        $responseBody = ['results' => 'yay'];

        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($responseMock);

        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($responseBody));

        $response = $this->resource->transmissions->post();

        $this->assertEquals($responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDelete()
    {
        $responseMock = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $responseBodyMock = Mockery::mock();

        $responseBody = ['results' => 'yay'];

        $this->clientMock->shouldReceive('sendRequest')->
            once()->
            with(Mockery::type('GuzzleHttp\Psr7\Request'))->
            andReturn($responseMock);

        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($responseBodyMock);
        $responseBodyMock->shouldReceive('__toString')->andReturn(json_encode($responseBody));

        $response = $this->resource->transmissions->delete($this->getTransmissionPayload);

        $this->assertEquals($responseBody, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFormatPayload()
    {
        $correctFormattedPayload = json_decode('{"content":{"from":{"name":"Sparkpost Team","email":"postmaster@sendmailfor.me"},"subject":"First Mailing From PHP","text":"Congratulations, {{name}}!! You just sent your very first mailing!","headers":{"CC":"avi.goldman@sparkpost.com"}},"substitution_data":{"name":"Avi"},"recipients":[{"address":{"name":"Vincent","email":"vincent.song@sparkpost.com"}},{"address":{"email":"test@example.com"}},{"address":{"email":"emely.giraldo@sparkpost.com","header_to":"\"Vincent\" <vincent.song@sparkpost.com>"}},{"address":{"email":"avi.goldman@sparkpost.com","header_to":"\"Vincent\" <vincent.song@sparkpost.com>"}}]}', true);

        $formattedPayload = $this->resource->transmissions->formatPayload($this->postTransmissionPayload);
        $this->assertEquals($correctFormattedPayload, $formattedPayload);
    }
}
