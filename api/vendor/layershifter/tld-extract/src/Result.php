<?php
/**
 * TLDExtract: Library for extraction of domain parts e.g. TLD. Domain parser that uses Public Suffix List.
 *
 * @link      https://github.com/layershifter/TLDExtract
 *
 * @copyright Copyright (c) 2016, Alexander Fedyashov
 * @license   https://raw.githubusercontent.com/layershifter/TLDExtract/master/LICENSE Apache 2.0 License
 */

namespace LayerShifter\TLDExtract;

use LayerShifter\TLDSupport\Helpers\IP;

/**
 * This class holds the components of a domain name.
 *
 * You can access the components using either method, property or array syntax. For example, "echo $result->suffix" and
 * "echo $result['suffix']" will both work and output the same string.
 *
 * All properties are read-only.
 *
 * @property-read null|string $subdomain
 * @property-read null|string $hostname
 * @property-read null|string $suffix
 */
class Result implements \ArrayAccess, ResultInterface
{
    /**
     * The subdomain. For example, the subdomain of "a.b.google.com" is "a.b".
     *
     * @var null|string
     */
    private $subdomain;
    /**
     * Hostname part of domain or IP-address. For example, in "a.b.google.com" the registered domain is "google".
     *
     * @var null|string
     */
    private $hostname;
    /**
     * The top-level domain / public suffix. For example: "com", "co.uk", "act.edu.au".
     *
     * @var null|string
     */
    private $suffix;

    /**
     * Constructor of class.
     *
     * @param null|string $subdomain
     * @param null|string $hostname
     * @param null|string $suffix
     */
    public function __construct($subdomain, $hostname, $suffix)
    {
        $this->subdomain = $subdomain;
        $this->hostname = $hostname;
        $this->suffix = $suffix;
    }

    /**
     * Returns subdomain if it exists.
     *
     * @return null|string
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    /**
     * Return subdomains if they exist, example subdomain is "www.news", method will return array ['www', 'bbc'].
     *
     * @return array
     */
    public function getSubdomains()
    {
        return null === $this->subdomain ? array() : explode('.', $this->subdomain);
    }

    /**
     * Returns hostname if it exists.
     *
     * @return null|string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Returns suffix if it exists.
     *
     * @return null|string
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Method that returns full host record.
     *
     * @return string
     */
    public function getFullHost()
    {
        // Case 1: Host hasn't suffix, possibly IP.

        if (null === $this->suffix) {
            return $this->hostname;
        }

        // Case 2: Domain with suffix, but without subdomain.

        if (null === $this->subdomain) {
            return $this->hostname . '.' . $this->suffix;
        }

        // Case 3: Domain with suffix & subdomain.

        return implode('.', [$this->subdomain, $this->hostname, $this->suffix]);
    }

    /**
     * Returns registrable domain or null.
     *
     * @return null|string
     */
    public function getRegistrableDomain()
    {
        if (null === $this->suffix) {
            return null;
        }

        return null === $this->hostname ? null : $this->hostname . '.' . $this->suffix;
    }

    /**
     * Returns true if domain is valid.
     *
     * @return bool
     */
    public function isValidDomain()
    {
        return null !== $this->getRegistrableDomain();
    }

    /**
     * Returns true is result is IP.
     *
     * @return bool
     */
    public function isIp()
    {
        return null === $this->suffix && IP::isValid($this->hostname);
    }

    /**
     * Magic method for run isset on private params.
     *
     * @param string $name Field name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Converts class fields to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFullHost();
    }

    /**
     * Whether or not an offset exists.
     *
     * @param mixed $offset An offset to check for
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * Returns the value at specified offset.
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @throws \OutOfRangeException
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Magic method, controls access to private params.
     *
     * @param string $name Name of params to retrieve
     *
     * @throws \OutOfRangeException
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new \OutOfRangeException(sprintf('Unknown field "%s"', $name));
        }

        return $this->{$name};
    }

    /**
     * Magic method, makes params read-only.
     *
     * @param string $name  Name of params to retrieve
     * @param mixed  $value Value to set
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function __set($name, $value)
    {
        throw new \LogicException("Can't modify an immutable object.");
    }

    /**
     * Disables assigns a value to the specified offset.
     *
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value  Value to set
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException(
            sprintf("Can't modify an immutable object. You tried to set value '%s' to field '%s'.", $value, $offset)
        );
    }

    /**
     * Disables unset of an offset.
     *
     * @param mixed $offset The offset for unset
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException(sprintf("Can't modify an immutable object. You tried to unset '%s.'", $offset));
    }

    /**
     * Get the domain name components as a native PHP array. The returned array will contain these keys: 'subdomain',
     * 'domain' and 'tld'.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'subdomain' => $this->subdomain,
            'hostname'  => $this->hostname,
            'suffix'    => $this->suffix,
        ];
    }

    /**
     * Get the domain name components as a JSON.
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
