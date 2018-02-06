<?php
namespace Transmission;

use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Client\ClientInterface;

/**
 * The Client class is used to make API calls to the Transmission server
 *
 * @author Ramon Kleiss <ramon@cubilon.nl>
 */
class Client
{
    /**
     * @var string
     */
    const DEFAULT_HOST = 'localhost';

    /**
     * @var integer
     */
    const DEFAULT_PORT = 9091;

    /**
     * @var string
     */
    const DEFAULT_PATH = '/transmission/rpc';

    /**
     * @var string
     */
    const TOKEN_HEADER = 'X-Transmission-Session-Id';

    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var Buzz\Client\ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $auth;

    /**
     * Constructor
     *
     * @param string  $host The hostname of the Transmission server
     * @param integer $port The port the Transmission server is listening on
     * @param string  $path The path to Transmission server rpc api
     */
    public function __construct($host = null, $port = null, $path = null)
    {
        $this->setHost($host ?: self::DEFAULT_HOST);
        $this->setPort($port ?: self::DEFAULT_PORT);
        $this->setPath($path ?: self::DEFAULT_PATH);
        $this->setToken(null);
        $this->setClient(new Curl());
    }

    /**
     * Authenticate against the Transmission server
     *
     * @param string $username
     * @param string $password
     */
    public function authenticate($username, $password)
    {
        $this->auth = base64_encode($username .':'. $password);
    }

    /**
     * Make an API call
     *
     * @param string $method
     * @param array  $arguments
     * @return stdClass
     * @throws RuntimeException
     */
    public function call($method, array $arguments)
    {
        $request = new Request('POST', $this->getPath(), $this->getUrl());
        $response = new Response();
        $content = array('method' => $method, 'arguments' => $arguments);

        $request->addHeader(sprintf('%s: %s', self::TOKEN_HEADER, $this->getToken()));
        $request->setContent(json_encode($content));

        if (is_string($this->auth)) {
            $request->addHeader(sprintf('Authorization: Basic %s', $this->auth));
        }

        try {
            $this->getClient()->send($request, $response);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Could not connect to Transmission',
                0,
                $e
            );
        }

        if ($response->getStatusCode() != 200 &&
            $response->getStatusCode() != 401 &&
            $response->getStatusCode() != 409) {
            throw new \RuntimeException('Unexpected response received from Transmission');
        }

        if ($response->getStatusCode() == 401) {
            throw new \RuntimeException('Access to Transmission requires authentication');
        }

        if ($response->getStatusCode() == 409) {
            $this->setToken($response->getHeader(self::TOKEN_HEADER));

            return $this->call($method, $arguments);
        }

        return json_decode($response->getContent());
    }

    /**
     * Get the URL used to connect to Transmission
     *
     * @return string
     */
    public function getUrl()
    {
        return sprintf(
            'http://%s:%d',
            $this->getHost(),
            $this->getPort()
        );
    }

    /**
     * Set the hostname of the Transmission server
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = (string) $host;
    }

    /**
     * Get the hostname of the Transmission server
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the port the Transmission server is listening on
     *
     * @param integer $port
     */
    public function setPort($port)
    {
        $this->port = (integer) $port;
    }

    /**
     * Get the port the Transmission server is listening on
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the path to Transmission server rpc api
     *
     * @param string $path
     */
    public function setPath($path)
    {
        return $this->path = (string) $path;
    }

    /**
     * Get the path to Transmission server rpc api
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the CSRF-token of the Transmission client
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = (string) $token;
    }

    /**
     * Get the CSRF-token for the Transmission client
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the Buzz client used to connect to Transmission
     *
     * @param Buzz\Client\ClientInterface $client
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get the Buzz client used to connect to Transmission
     *
     * @return Buzz\Client\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
