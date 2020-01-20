<?php

namespace LayerShifter\TLDExtract;

use TrueBV\Punycode;

/**
 * Class that transforms IDN domains, if `intl` extension present uses it.
 */
class IDN
{

    /**
     * @var Punycode Object of TrueBV\Punycode class.
     */
    private $transformer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!function_exists('\idn_to_utf8')) {
            $this->transformer = new Punycode();
        }
    }

    /**
     * Converts domain name from Unicode to IDNA ASCII.
     *
     * @param string $domain Domain to convert in IDNA ASCII-compatible format.
     *
     * @return string
     */
    public function toASCII($domain)
    {
        if ($this->transformer) {
            return $this->transformer->encode($domain);
        }

        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            return idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
        }

        return idn_to_ascii($domain);
    }

    /**
     * Converts domain name from IDNA ASCII to Unicode.
     *
     * @param string $domain Domain to convert in Unicode format.
     *
     * @return string
     */
    public function toUTF8($domain)
    {
        if ($this->transformer) {
            return $this->transformer->decode($domain);
        }

        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            return idn_to_utf8($domain, 0, INTL_IDNA_VARIANT_UTS46);
        }

        return idn_to_utf8($domain);
    }
}
