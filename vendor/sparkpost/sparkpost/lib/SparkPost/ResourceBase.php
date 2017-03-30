<?php

namespace SparkPost;

/**
 * Class ResourceBase.
 */
class ResourceBase
{
    /**
     * SparkPost object used to make requests.
     */
    protected $sparkpost;

    /**
     * The api endpoint that gets prepended to all requests send through this resource.
     */
    protected $endpoint;

    /**
     * Sets up the Resource.
     *
     * @param SparkPost $sparkpost - the sparkpost instance that this resource is attached to
     * @param string    $endpoint  - the endpoint that this resource wraps
     */
    public function __construct(SparkPost $sparkpost, $endpoint)
    {
        $this->sparkpost = $sparkpost;
        $this->endpoint = $endpoint;
    }

    /**
     * Sends get request to API at the set endpoint.
     *
     * @see SparkPost->request()
     */
    public function get($uri = '', $payload = [], $headers = [])
    {
        return $this->request('GET', $uri, $payload, $headers);
    }

    /**
     * Sends put request to API at the set endpoint.
     *
     * @see SparkPost->request()
     */
    public function put($uri = '', $payload = [], $headers = [])
    {
        return $this->request('PUT', $uri, $payload, $headers);
    }

    /**
     * Sends post request to API at the set endpoint.
     *
     * @see SparkPost->request()
     */
    public function post($payload = [], $headers = [])
    {
        return $this->request('POST', '', $payload, $headers);
    }

    /**
     * Sends delete request to API at the set endpoint.
     *
     * @see SparkPost->request()
     */
    public function delete($uri = '', $payload = [], $headers = [])
    {
        return $this->request('DELETE', $uri, $payload, $headers);
    }

    /**
     * Sends requests to SparkPost object to the resource endpoint.
     *
     * @see SparkPost->request()
     *
     * @return SparkPostPromise or SparkPostResponse depending on sync or async request
     */
    public function request($method = 'GET', $uri = '', $payload = [], $headers = [])
    {
        if (is_array($uri)) {
            $headers = $payload;
            $payload = $uri;
            $uri = '';
        }

        $uri = $this->endpoint.'/'.$uri;

        return $this->sparkpost->request($method, $uri, $payload, $headers);
    }
}
