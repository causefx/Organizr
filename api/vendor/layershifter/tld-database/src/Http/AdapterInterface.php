<?php
/**
 * TLDDatabase: Abstraction for Public Suffix List in PHP.
 *
 * @link      https://github.com/layershifter/TLDDatabase
 *
 * @copyright Copyright (c) 2016, Alexander Fedyashov
 * @license   https://raw.githubusercontent.com/layershifter/TLDDatabase/master/LICENSE Apache 2.0 License
 */

namespace LayerShifter\TLDDatabase\Http;

use LayerShifter\TLDDatabase\Exceptions\HttpException;

/**
 * AdapterInterface for HTTP adapters that can be used for fetching Public Suffix List.
 */
interface AdapterInterface
{
    /**
     * Fetches Public Suffix List file and returns its content as array of strings.
     *
     * @param string $url URL of Public Suffix List file.
     *
     * @return array|string[]
     *
     * @throws HttpException
     */
    public function get($url);
}
