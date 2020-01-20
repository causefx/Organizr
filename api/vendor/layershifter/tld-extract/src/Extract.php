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

use LayerShifter\TLDDatabase\Store;
use LayerShifter\TLDExtract\Exceptions\RuntimeException;
use LayerShifter\TLDSupport\Helpers\Arr;
use LayerShifter\TLDSupport\Helpers\IP;
use LayerShifter\TLDSupport\Helpers\Str;
use TrueBV\Exception\OutOfBoundsException;

/**
 * Extract class accurately extracts subdomain, domain and TLD components from URLs.
 *
 * @see Result for more information on the returned data structure.
 */
class Extract
{

    /**
     * @const int If this option provided, extract will consider ICANN suffixes.
     */
    const MODE_ALLOW_ICANN = 2;

    /**
     * @deprecated This constant is a typo, please use self::MODE_ALLOW_ICANN
     */
    const MODE_ALLOW_ICCAN = self::MODE_ALLOW_ICANN;

    /**
     * @const int If this option provided, extract will consider private suffixes.
     */
    const MODE_ALLOW_PRIVATE = 4;
    /**
     * @const int If this option provided, extract will consider custom domains.
     */
    const MODE_ALLOW_NOT_EXISTING_SUFFIXES = 8;
    /**
     * @const string RFC 3986 compliant scheme regex pattern.
     *
     * @see   https://tools.ietf.org/html/rfc3986#section-3.1
     */
    const SCHEMA_PATTERN = '#^([a-zA-Z][a-zA-Z0-9+\-.]*:)?//#';
    /**
     * @const string The specification for this regex is based upon the extracts from RFC 1034 and RFC 2181 below.
     *
     * @see   https://tools.ietf.org/html/rfc1034
     * @see   https://tools.ietf.org/html/rfc2181
     */
    const HOSTNAME_PATTERN = '#^((?!-)[a-z0-9_-]{0,62}[a-z0-9_]\.)+[a-z]{2,63}|[xn\-\-a-z0-9]]{6,63}$#';

    /**
     * @var int Value of extraction options.
     */
    private $extractionMode;
    /**
     * @var string Name of class that will store results of parsing.
     */
    private $resultClassName;
    /**
     * @var IDN Object of TLDExtract\IDN class.
     */
    private $idn;
    /**
     * @var Store Object of TLDDatabase\Store class.
     */
    private $suffixStore;

    /**
     * Factory constructor.
     *
     * @param null|string $databaseFile    Optional, name of file with Public Suffix List database
     * @param null|string $resultClassName Optional, name of class that will store results of parsing
     * @param null|int    $extractionMode  Optional, option that will control extraction process
     *
     * @throws RuntimeException
     */
    public function __construct($databaseFile = null, $resultClassName = null, $extractionMode = null)
    {
        $this->idn = new IDN();
        $this->suffixStore = new Store($databaseFile);
        $this->resultClassName = Result::class;

        // Checks for resultClassName argument.

        if (null !== $resultClassName) {
            if (!class_exists($resultClassName)) {
                throw new RuntimeException(sprintf('Class "%s" is not defined', $resultClassName));
            }

            if (!in_array(ResultInterface::class, class_implements($resultClassName), true)) {
                throw new RuntimeException(sprintf('Class "%s" not implements ResultInterface', $resultClassName));
            }

            $this->resultClassName = $resultClassName;
        }

        $this->setExtractionMode($extractionMode);
    }

    /**
     * Sets extraction mode, option that will control extraction process.
     *
     * @param int $extractionMode One of MODE_* constants
     *
     * @throws RuntimeException
     */
    public function setExtractionMode($extractionMode = null)
    {
        if (null === $extractionMode) {
            $this->extractionMode = static::MODE_ALLOW_ICANN
                | static::MODE_ALLOW_PRIVATE
                | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES;

            return;
        }

        if (!is_int($extractionMode)) {
            throw new RuntimeException('Invalid argument type, extractionMode must be integer');
        }

        if (!in_array($extractionMode, [
            static::MODE_ALLOW_ICANN,
            static::MODE_ALLOW_PRIVATE,
            static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_PRIVATE,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_ICANN | static::MODE_ALLOW_PRIVATE | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES,
            static::MODE_ALLOW_PRIVATE | static::MODE_ALLOW_NOT_EXISTING_SUFFIXES
        ], true)
        ) {
            throw new RuntimeException(
                'Invalid argument type, extractionMode must be one of defined constants of their combination'
            );
        }

        $this->extractionMode = $extractionMode;
    }

    /**
     * Extract the subdomain, host and gTLD/ccTLD components from a URL.
     *
     * @param string $url URL that will be extracted
     *
     * @return ResultInterface
     */
    public function parse($url)
    {
        $hostname = $this->extractHostname($url);

        // If received hostname is valid IP address, result will be formed from it.

        if (IP::isValid($hostname)) {
            return new $this->resultClassName(null, $hostname, null);
        }

        list($subDomain, $host, $suffix) = $this->extractParts($hostname);

        return new $this->resultClassName($subDomain, $host, $suffix);
    }

    /**
     * Method that extracts the hostname or IP address from a URL.
     *
     * @param string $url URL for extraction
     *
     * @return null|string Hostname or IP address
     */
    private function extractHostname($url)
    {
        $url = trim(Str::lower($url));

        // Removes scheme and path i.e. "https://github.com/layershifter" to "github.com/layershifter".

        $url = preg_replace(static::SCHEMA_PATTERN, '', $url);

        // Removes path and query part of URL i.e. "github.com/layershifter" to "github.com".

        $url = $this->fixUriParts($url);
        $hostname = Arr::first(explode('/', $url, 2));

        // Removes username from URL i.e. user@github.com to github.com.

        $hostname = Arr::last(explode('@', $hostname));

        // Remove ports from hosts, also check for IPv6 literals like "[3ffe:2a00:100:7031::1]".
        //
        // @see http://www.ietf.org/rfc/rfc2732.txt

        $lastBracketPosition = Str::strrpos($hostname, ']');

        if ($lastBracketPosition !== false && Str::startsWith($hostname, '[')) {
            return Str::substr($hostname, 1, $lastBracketPosition - 1);
        }

        // This is either a normal hostname or an IPv4 address, just remove the port.

        $hostname = Arr::first(explode(':', $hostname));

        // If string is empty, null will be returned.

        return '' === $hostname ? null : $hostname;
    }

    /**
     * Extracts subdomain, host and suffix from input string. Based on algorithm described in
     * https://publicsuffix.org/list/.
     *
     * @param string $hostname Hostname for extraction
     *
     * @return array|string[] An array that contains subdomain, host and suffix.
     */
    public function extractParts($hostname)
    {
        $suffix = $this->extractSuffix($hostname);

        if ($suffix === $hostname) {
            return [null, $hostname, null];
        }

        if (null !== $suffix) {
            $hostname = Str::substr($hostname, 0, -Str::length($suffix) - 1);
        }

        $lastDot = Str::strrpos($hostname, '.');

        if (false === $lastDot) {
            return [null, $hostname, $suffix];
        }

        $subDomain = Str::substr($hostname, 0, $lastDot);
        $host = Str::substr($hostname, $lastDot + 1);

        return [
            $subDomain,
            $host,
            $suffix
        ];
    }

    /**
     * Extracts suffix from hostname using Public Suffix List database.
     *
     * @param string $hostname Hostname for extraction
     *
     * @return null|string
     */
    private function extractSuffix($hostname)
    {
        // If hostname has leading dot, it's invalid.
        // If hostname is a single label domain makes, it's invalid.

        if (Str::startsWith($hostname, '.') || Str::strpos($hostname, '.') === false) {
            return null;
        }

        // If domain is in punycode, it will be converted to IDN.

        $isPunycoded = Str::strpos($hostname, 'xn--') !== false;

        if ($isPunycoded) {
            $hostname = $this->idn->toUTF8($hostname);
        }

        // URI producers should use names that conform to the DNS syntax, even when use of DNS is not immediately
        // apparent, and should limit these names to no more than 255 characters in length.
        //
        // @see https://tools.ietf.org/html/rfc3986
        // @see http://blogs.msdn.com/b/oldnewthing/archive/2012/04/12/10292868.aspx

        if (Str::length($hostname) > 253) {
            return null;
        }

        // The DNS itself places only one restriction on the particular labels that can be used to identify resource
        // records. That one restriction relates to the length of the label and the full name. The length of any one
        // label is limited to between 1 and 63 octets. A full domain name is limited to 255 octets (including the
        // separators).
        //
        // @see http://tools.ietf.org/html/rfc2181

        try {
            $asciiHostname = $this->idn->toASCII($hostname);
        } catch (OutOfBoundsException $e) {
            return null;
        }

        if (0 === preg_match(self::HOSTNAME_PATTERN, $asciiHostname)) {
            return null;
        }

        $suffix = $this->parseSuffix($hostname);

        if (null === $suffix) {
            if (!($this->extractionMode & static::MODE_ALLOW_NOT_EXISTING_SUFFIXES)) {
                return null;
            }

            $suffix = Str::substr($hostname, Str::strrpos($hostname, '.') + 1);
        }

        // If domain is punycoded, suffix will be converted to punycode.

        return $isPunycoded ? $this->idn->toASCII($suffix) : $suffix;
    }

    /**
     * Extracts suffix from hostname using Public Suffix List database.
     *
     * @param string $hostname Hostname for extraction
     *
     * @return null|string
     */
    private function parseSuffix($hostname)
    {
        $hostnameParts = explode('.', $hostname);
        $realSuffix = null;

        for ($i = 0, $count = count($hostnameParts); $i < $count; $i++) {
            $possibleSuffix = implode('.', array_slice($hostnameParts, $i));
            $exceptionSuffix = '!' . $possibleSuffix;

            if ($this->suffixExists($exceptionSuffix)) {
                $realSuffix = implode('.', array_slice($hostnameParts, $i + 1));

                break;
            }

            if ($this->suffixExists($possibleSuffix)) {
                $realSuffix = $possibleSuffix;

                break;
            }

            $wildcardTld = '*.' . implode('.', array_slice($hostnameParts, $i + 1));

            if ($this->suffixExists($wildcardTld)) {
                $realSuffix = $possibleSuffix;

                break;
            }
        }

        return $realSuffix;
    }

    /**
     * Method that checks existence of entry in Public Suffix List database, including provided options.
     *
     * @param string $entry Entry for check in Public Suffix List database
     *
     * @return bool
     */
    protected function suffixExists($entry)
    {
        if (!$this->suffixStore->isExists($entry)) {
            return false;
        }

        $type = $this->suffixStore->getType($entry);

        if ($this->extractionMode & static::MODE_ALLOW_ICANN && $type === Store::TYPE_ICANN) {
            return true;
        }

        return $this->extractionMode & static::MODE_ALLOW_PRIVATE && $type === Store::TYPE_PRIVATE;
    }

    /**
     * Fixes URL:
     * - from "github.com?layershifter" to "github.com/?layershifter".
     * - from "github.com#layershifter" to "github.com/#layershifter".
     *
     * @see https://github.com/layershifter/TLDExtract/issues/5
     *
     * @param string $url
     *
     * @return string
     */
    private function fixUriParts($url)
    {
        $position = Str::strpos($url, '?') ?: Str::strpos($url, '#');

        if ($position === false) {
            return $url;
        }

        return Str::substr($url, 0, $position) . '/' . Str::substr($url, $position);
    }
}
