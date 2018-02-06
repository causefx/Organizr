<?php

namespace Buzz\Util;

use Buzz\Message\RequestInterface;
use Buzz\Exception\InvalidArgumentException;

class Url
{
    private static $defaultPorts = array(
        'http'  => 80,
        'https' => 443,
    );

    private $url;
    private $components;

    /**
     * Constructor.
     *
     * @param string $url The URL
     *
     * @throws InvalidArgumentException If the URL is invalid
     */
    public function __construct($url)
    {
        $components = parse_url($url);

        if (false === $components) {
            throw new InvalidArgumentException(sprintf('The URL "%s" is invalid.', $url));
        }

        // support scheme-less URLs
        if (!isset($components['host']) && isset($components['path'])) {
            $pos = strpos($components['path'], '/');
            if (false === $pos) {
                $components['host'] = $components['path'];
                unset($components['path']);
            } elseif (0 !== $pos) {
                list($host, $path) = explode('/', $components['path'], 2);
                $components['host'] = $host;
                $components['path'] = '/'.$path;
            }
        }

        // default port
        if (isset($components['scheme']) && !isset($components['port']) && isset(self::$defaultPorts[$components['scheme']])) {
            $components['port'] = self::$defaultPorts[$components['scheme']];
        }

        $this->url = $url;
        $this->components = $components;
    }

    public function getScheme()
    {
        return $this->parseUrl('scheme');
    }

    public function getHostname()
    {
        return $this->parseUrl('host');
    }

    public function getPort()
    {
        return $this->parseUrl('port');
    }

    public function getUser()
    {
        return $this->parseUrl('user');
    }

    public function getPassword()
    {
        return $this->parseUrl('pass');
    }

    public function getPath()
    {
        return $this->parseUrl('path');
    }

    public function getQueryString()
    {
        return $this->parseUrl('query');
    }

    public function getFragment()
    {
        return $this->parseUrl('fragment');
    }

    /**
     * Returns a host string that combines scheme, hostname and port.
     *
     * @return string A host value for an HTTP message
     */
    public function getHost()
    {
        if ($hostname = $this->parseUrl('host')) {
            $host  = $scheme = $this->parseUrl('scheme', 'http');
            $host .= '://';
            $host .= $hostname;

            $port = $this->parseUrl('port');
            if ($port && (!isset(self::$defaultPorts[$scheme]) || self::$defaultPorts[$scheme] != $port)) {
                $host .= ':'.$port;
            }

            return $host;
        }
    }

    /**
     * Returns a resource string that combines path and query string.
     *
     * @return string A resource value for an HTTP message
     */
    public function getResource()
    {
        $resource = $this->parseUrl('path', '/');

        if ($query = $this->parseUrl('query')) {
            $resource .= '?'.$query;
        }

        return $resource;
    }

    /**
     * Returns a formatted URL.
     */
    public function format($pattern)
    {
        static $map = array(
            's' => 'getScheme',
            'u' => 'getUser',
            'a' => 'getPassword',
            'h' => 'getHostname',
            'o' => 'getPort',
            'p' => 'getPath',
            'q' => 'getQueryString',
            'f' => 'getFragment',
            'H' => 'getHost',
            'R' => 'getResource',
        );

        $url = '';

        $parts = str_split($pattern);
        while ($part = current($parts)) {
            if (isset($map[$part])) {
                $method = $map[$part];
                $url .= $this->$method();
            } elseif ('\\' == $part) {
                $url .= next($parts);
            } elseif (!ctype_alpha($part)) {
                $url .= $part;
            } else {
                throw new InvalidArgumentException(sprintf('The format character "%s" is invalid.', $part));
            }

            next($parts);
        }

        return $url;
    }

    /**
     * Applies the current URL to the supplied request.
     */
    public function applyToRequest(RequestInterface $request)
    {
        $request->setResource($this->getResource());
        $request->setHost($this->getHost());
    }

    private function parseUrl($component = null, $default = null)
    {
        if (null === $component) {
            return $this->components;
        } elseif (isset($this->components[$component])) {
            return $this->components[$component];
        } else {
            return $default;
        }
    }
}
