<?php

namespace Buzz\Util;

use Buzz\Message\RequestInterface;

class Cookie
{
    const ATTR_DOMAIN  = 'domain';
    const ATTR_PATH    = 'path';
    const ATTR_SECURE  = 'secure';
    const ATTR_MAX_AGE = 'max-age';
    const ATTR_EXPIRES = 'expires';

    protected $name;
    protected $value;
    protected $attributes = array();
    protected $createdAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = time();
    }

    /**
     * Returns true if the current cookie matches the supplied request.
     *
     * @param RequestInterface $request A request object
     *
     * @return boolean
     */
    public function matchesRequest(RequestInterface $request)
    {
        // domain
        if (!$this->matchesDomain(parse_url($request->getHost(), PHP_URL_HOST))) {
            return false;
        }

        // path
        if (!$this->matchesPath($request->getResource())) {
            return false;
        }

        // secure
        if ($this->hasAttribute(static::ATTR_SECURE) && !$request->isSecure()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true of the current cookie has expired.
     *
     * Checks the max-age and expires attributes.
     *
     * @return boolean Whether the current cookie has expired
     */
    public function isExpired()
    {
        $maxAge = $this->getAttribute(static::ATTR_MAX_AGE);
        if ($maxAge && time() - $this->getCreatedAt() > $maxAge) {
            return true;
        }

        $expires = $this->getAttribute(static::ATTR_EXPIRES);
        if ($expires && strtotime($expires) < time()) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the current cookie matches the supplied domain.
     *
     * @param string $domain A domain hostname
     *
     * @return boolean
     */
    public function matchesDomain($domain)
    {
        $cookieDomain = $this->getAttribute(static::ATTR_DOMAIN);

        if (0 === strpos($cookieDomain, '.')) {
            $pattern = '/\b'.preg_quote(substr($cookieDomain, 1), '/').'$/i';

            return (boolean) preg_match($pattern, $domain);
        } else {
            return 0 == strcasecmp($cookieDomain, $domain);
        }
    }

    /**
     * Returns true if the current cookie matches the supplied path.
     *
     * @param string $path A path
     *
     * @return boolean
     */
    public function matchesPath($path)
    {
        $needle = $this->getAttribute(static::ATTR_PATH);

        return null === $needle || 0 === strpos($path, $needle);
    }

    /**
     * Populates the current cookie with data from the supplied Set-Cookie header.
     *
     * @param string $header        A Set-Cookie header
     * @param string $issuingDomain The domain that issued the header
     */
    public function fromSetCookieHeader($header, $issuingDomain)
    {
        list($this->name, $header)  = explode('=', $header, 2);
        if (false === strpos($header, ';')) {
            $this->value = $header;
            $header = null;
        } else {
            list($this->value, $header) = explode(';', $header, 2);
        }

        $this->clearAttributes();
        foreach (array_map('trim', explode(';', trim($header))) as $pair) {
            if (false === strpos($pair, '=')) {
                $name = $pair;
                $value = null;
            } else {
                list($name, $value) = explode('=', $pair);
            }

            $this->setAttribute($name, $value);
        }

        if (!$this->getAttribute(static::ATTR_DOMAIN)) {
            $this->setAttribute(static::ATTR_DOMAIN, $issuingDomain);
        }
    }

    /**
     * Formats a Cookie header for the current cookie.
     *
     * @return string An HTTP request Cookie header
     */
    public function toCookieHeader()
    {
        return 'Cookie: '.$this->getName().'='.$this->getValue();
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setAttributes(array $attributes)
    {
        // attributes are case insensitive
        $this->attributes = array_change_key_case($attributes);
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[strtolower($name)] = $value;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name)
    {
        $name = strtolower($name);

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }

    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    public function clearAttributes()
    {
        $this->setAttributes(array());
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
