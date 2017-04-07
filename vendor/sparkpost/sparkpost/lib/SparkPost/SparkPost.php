<?php

namespace SparkPost;

use Http\Client\HttpClient;
use Http\Client\HttpAsyncClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use Psr\Http\Message\RequestInterface;

class SparkPost
{
    /**
     * @var string Library version, used for setting User-Agent
     */
    private $version = '2.1.0';

    /**
     * @var HttpClient|HttpAsyncClient used to make requests
     */
    private $httpClient;

    /**
     * @var RequestFactory
     */
    private $messageFactory;

    /**
     * @var array Options for requests
     */
    private $options;

    /**
     * Default options for requests that can be overridden with the setOptions function.
     */
    private static $defaultOptions = [
        'host' => 'api.sparkpost.com',
        'protocol' => 'https',
        'port' => 443,
        'key' => '',
        'version' => 'v1',
        'async' => true,
        'debug' => false,
    ];

    /**
     * @var Transmission Instance of Transmission class
     */
    public $transmissions;

    /**
     * Sets up the SparkPost instance.
     *
     * @param HttpClient $httpClient - An httplug client or adapter
     * @param array      $options    - An array to overide default options or a string to be used as an API key
     */
    public function __construct($httpClient, array $options)
    {
        $this->setOptions($options);
        $this->setHttpClient($httpClient);
        $this->setupEndpoints();
    }

    /**
     * Sends either sync or async request based on async option.
     *
     * @param string $method
     * @param string $uri
     * @param array  $payload - either used as the request body or url query params
     * @param array  $headers
     *
     * @return SparkPostPromise|SparkPostResponse Promise or Response depending on sync or async request
     */
    public function request($method = 'GET', $uri = '', $payload = [], $headers = [])
    {
        if ($this->options['async'] === true) {
            return $this->asyncRequest($method, $uri, $payload, $headers);
        } else {
            return $this->syncRequest($method, $uri, $payload, $headers);
        }
    }

    /**
     * Sends sync request to SparkPost API.
     *
     * @param string $method
     * @param string $uri
     * @param array  $payload
     * @param array  $headers
     *
     * @return SparkPostResponse
     *
     * @throws SparkPostException
     */
    public function syncRequest($method = 'GET', $uri = '', $payload = [], $headers = [])
    {
        $requestValues = $this->buildRequestValues($method, $uri, $payload, $headers);
        $request = call_user_func_array(array($this, 'buildRequestInstance'), $requestValues);

        try {
            return new SparkPostResponse($this->httpClient->sendRequest($request), $this->ifDebug($requestValues));
        } catch (\Exception $exception) {
            throw new SparkPostException($exception, $this->ifDebug($requestValues));
        }
    }

    /**
     * Sends async request to SparkPost API.
     *
     * @param string $method
     * @param string $uri
     * @param array  $payload
     * @param array  $headers
     *
     * @return SparkPostPromise
     */
    public function asyncRequest($method = 'GET', $uri = '', $payload = [], $headers = [])
    {
        if ($this->httpClient instanceof HttpAsyncClient) {
            $requestValues = $this->buildRequestValues($method, $uri, $payload, $headers);
            $request = call_user_func_array(array($this, 'buildRequestInstance'), $requestValues);

            return new SparkPostPromise($this->httpClient->sendAsyncRequest($request), $this->ifDebug($requestValues));
        } else {
            throw new \Exception('Your http client does not support asynchronous requests. Please use a different client or use synchronous requests.');
        }
    }

    /**
     * Builds request values from given params.
     *
     * @param string $method
     * @param string $uri
     * @param array  $payload
     * @param array  $headers
     *
     * @return array $requestValues
     */
    public function buildRequestValues($method, $uri, $payload, $headers)
    {
        $method = trim(strtoupper($method));

        if ($method === 'GET') {
            $params = $payload;
            $body = [];
        } else {
            $params = [];
            $body = $payload;
        }

        $url = $this->getUrl($uri, $params);
        $headers = $this->getHttpHeaders($headers);

        // Sparkpost API will not tolerate form feed in JSON.
        $jsonReplace = [
            '\f' => '',
        ];
        $body = strtr(json_encode($body), $jsonReplace);

        return [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * Build RequestInterface from given params.
     *
     * @param array $requestValues
     *
     * @return RequestInterface
     */
    public function buildRequestInstance($method, $uri, $headers, $body)
    {
        return $this->getMessageFactory()->createRequest($method, $uri, $headers, $body);
    }

    /**
     * Build RequestInterface from given params.
     *
     * @param array $requestValues
     *
     * @return RequestInterface
     */
    public function buildRequest($method, $uri, $payload, $headers)
    {
        $requestValues = $this->buildRequestValues($method, $uri, $payload, $headers);
        return call_user_func_array(array($this, 'buildRequestInstance'), $requestValues);
    }

    /**
     * Returns an array for the request headers.
     *
     * @param array $headers - any custom headers for the request
     *
     * @return array $headers - headers for the request
     */
    public function getHttpHeaders($headers = [])
    {
        $constantHeaders = [
            'Authorization' => $this->options['key'],
            'Content-Type' => 'application/json',
            'User-Agent' => 'php-sparkpost/'.$this->version,
        ];

        foreach ($constantHeaders as $key => $value) {
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Builds the request url from the options and given params.
     *
     * @param string $path   - the path in the url to hit
     * @param array  $params - query parameters to be encoded into the url
     *
     * @return string $url - the url to send the desired request to
     */
    public function getUrl($path, $params = [])
    {
        $options = $this->options;

        $paramsArray = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            array_push($paramsArray, $key.'='.$value);
        }

        $paramsString = implode('&', $paramsArray);

        return $options['protocol'].'://'.$options['host'].($options['port'] ? ':'.$options['port'] : '').'/api/'.$options['version'].'/'.$path.($paramsString ? '?'.$paramsString : '');
    }

    /**
     * Sets $httpClient to be used for request.
     *
     * @param HttpClient|HttpAsyncClient $httpClient - the client to be used for request
     */
    public function setHttpClient($httpClient)
    {
        if (!($httpClient instanceof HttpAsyncClient || $httpClient instanceof HttpClient)) {
            throw new \LogicException(sprintf('Parameter to SparkPost::setHttpClient must be instance of "%s" or "%s"', HttpClient::class, HttpAsyncClient::class));
        }

        $this->httpClient = $httpClient;
    }

    /**
     * Sets the options from the param and defaults for the SparkPost object.
     *
     * @param array $options - either an string API key or an array of options
     */
    public function setOptions($options)
    {
        // if the options map is a string we should assume that its an api key
        if (is_string($options)) {
            $options = ['key' => $options];
        }

        // Validate API key because its required
        if (!isset($this->options['key']) && (!isset($options['key']) || !preg_match('/\S/', $options['key']))) {
            throw new \Exception('You must provide an API key');
        }

        $this->options = isset($this->options) ? $this->options : self::$defaultOptions;

        // set options, overriding defaults
        foreach ($options as $option => $value) {
            if (key_exists($option, $this->options)) {
                $this->options[$option] = $value;
            }
        }
    }

    /**
     * Returns the given value if debugging, an empty instance otherwise.
     *
     * @param any $param
     *
     * @return any $param
     */
    private function ifDebug($param)
    {
        return $this->options['debug'] ? $param : null;
    }

    /**
     * Sets up any endpoints to custom classes e.g. $this->transmissions.
     */
    private function setupEndpoints()
    {
        $this->transmissions = new Transmission($this);
    }

    /**
     * @return RequestFactory
     */
    private function getMessageFactory()
    {
        if (!$this->messageFactory) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }

        return $this->messageFactory;
    }

    /**
     * @param RequestFactory $messageFactory
     *
     * @return SparkPost
     */
    public function setMessageFactory(RequestFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }
}
