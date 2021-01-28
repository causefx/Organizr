<?php namespace Bogstag\OAuth2\Client\Test\Provider;

use Mockery as m;

class TraktTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(
            '{"access_token": "mock_access_token","token_type": "bearer","expires_in": 3600,"refresh_token": "mock_refresh_token","scope": "public","created_at": '.
            time().'}'
        );
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $username = uniqid();
        $name = uniqid();
        $avatarUrl = uniqid();
        $id = rand(1000, 9999);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(
            '{"access_token":"mock_access_token","authentication_token":"","code":"","expires_in":3600,"refresh_token":"mock_refresh_token","scope":"","state":"","token_type":""}'
        );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(
            '{"user": {"username": "'.$username.'","name": "'.$name.'","ids": {"slug": "'.$id.
            '"},"images": {"avatar": {"full": "'.$avatarUrl.'"}}}}'
        );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(2)->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['user']['username']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['user']['name']);
        $this->assertEquals($avatarUrl, $user->getAvatarUrl());
        $this->assertEquals($avatarUrl, $user->toArray()['user']['images']['avatar']['full']);
        $this->assertEquals($id, $user->getId());
        $this->assertEquals($id, $user->toArray()['user']['ids']['slug']);
    }

    /**
     * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
     **/
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $message = uniqid();

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(
            '{"error": {"code": "request_token_expired", "message": "'.$message.'"}}'
        );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(500);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    protected function setUp()
    {
        $this->provider = new \Bogstag\OAuth2\Client\Provider\Trakt(
            [
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            ]
        );
    }
}
