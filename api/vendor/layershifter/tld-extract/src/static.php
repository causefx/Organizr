<?php
/**
 * TLDExtract: Library for extraction of domain parts e.g. TLD. Domain parser that uses Public Suffix List.
 *
 * @link      https://github.com/layershifter/TLDExtract
 *
 * @copyright Copyright (c) 2016, Alexander Fedyashov
 * @license   https://raw.githubusercontent.com/layershifter/TLDExtract/master/LICENSE Apache 2.0 License
 */

namespace {

    use LayerShifter\TLDExtract\Extract;

    /**
     * Extract the subdomain, host and gTLD/ccTLD components from a URL.
     *
     * @param string $url  URL that will be extracted
     * @param int    $mode Optional, option that will control extraction process
     *
     * @return \LayerShifter\TLDExtract\ResultInterface
     */
    function tld_extract($url, $mode = null)
    {
        static $extract = null;

        if (null === $extract) {
            $extract = new Extract();
        }

        if (null !== $mode) {
            $extract->setExtractionMode($mode);
        }

        return $extract->parse($url);
    }
}
