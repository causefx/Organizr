<?php

namespace Buzz\Message;

class Response extends AbstractMessage
{
    private $protocolVersion;
    private $statusCode;
    private $reasonPhrase;

    /**
     * Returns the protocol version of the current response.
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        if (null === $this->protocolVersion) {
            $this->parseStatusLine();
        }

        return $this->protocolVersion ?: null;
    }

    /**
     * Returns the status code of the current response.
     *
     * @return integer
     */
    public function getStatusCode()
    {
        if (null === $this->statusCode) {
            $this->parseStatusLine();
        }

        return $this->statusCode ?: null;
    }

    /**
     * Returns the reason phrase for the current response.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        if (null === $this->reasonPhrase) {
            $this->parseStatusLine();
        }

        return $this->reasonPhrase ?: null;
    }

    public function setHeaders(array $headers)
    {
        parent::setHeaders($headers);

        $this->resetStatusLine();
    }

    public function addHeader($header)
    {
        parent::addHeader($header);

        $this->resetStatusLine();
    }

    public function addHeaders(array $headers)
    {
        parent::addHeaders($headers);

        $this->resetStatusLine();
    }

    /**
     * Is response invalid?
     *
     * @return boolean
     */
    public function isInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * Is response informative?
     *
     * @return boolean
     */
    public function isInformational()
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Is response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Is the response a redirect?
     *
     * @return boolean
     */
    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Is there a client error?
     *
     * @return boolean
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Was there a server side error?
     *
     * @return boolean
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * Is the response OK?
     *
     * @return boolean
     */
    public function isOk()
    {
        return 200 === $this->getStatusCode();
    }

    /**
     * Is the response forbidden?
     *
     * @return boolean
     */
    public function isForbidden()
    {
        return 403 === $this->getStatusCode();
    }

    /**
     * Is the response a not found error?
     *
     * @return boolean
     */
    public function isNotFound()
    {
        return 404 === $this->getStatusCode();
    }

    /**
     * Is the response empty?
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return in_array($this->getStatusCode(), array(201, 204, 304));
    }

    // private

    private function parseStatusLine()
    {
        $headers = $this->getHeaders();

        if (isset($headers[0]) && 2 <= count($parts = explode(' ', $headers[0], 3))) {
            $this->protocolVersion = (string) substr($parts[0], 5);
            $this->statusCode = (integer) $parts[1];
            $this->reasonPhrase = isset($parts[2]) ? $parts[2] : '';
        } else {
            $this->protocolVersion = $this->statusCode = $this->reasonPhrase = false;
        }
    }

    private function resetStatusLine()
    {
        $this->protocolVersion = $this->statusCode = $this->reasonPhrase = null;
    }
}
