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

/**
 * Interface for parsing results.
 */
interface ResultInterface
{

    /**
     * Class that implements ResultInterface must have following constructor.
     *
     * @param null|string $subdomain
     * @param null|string $hostname
     * @param null|string $suffix
     */
    public function __construct($subdomain, $hostname, $suffix);

    /**
     * Returns subdomain if it exists.
     *
     * @return null|string
     */
    public function getSubdomain();

    /**
     * Return subdomains if they exist, example subdomain is "www.news", method will return array ['www', 'bbc'].
     *
     * @return array
     */
    public function getSubdomains();

    /**
     * Returns hostname if it exists.
     *
     * @return null|string
     */
    public function getHostname();

    /**
     * Returns suffix if it exists.
     *
     * @return null|string
     */
    public function getSuffix();

    /**
     * Method that returns full host record.
     *
     * @return string
     */
    public function getFullHost();

    /**
     * Returns registrable domain or null.
     *
     * @return null|string
     */
    public function getRegistrableDomain();

    /**
     * Returns true if domain is valid.
     *
     * @return bool
     */
    public function isValidDomain();

    /**
     * Returns true is result is IP.
     *
     * @return bool
     */
    public function isIp();
}
