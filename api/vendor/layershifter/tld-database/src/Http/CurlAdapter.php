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
 * cURL adapter for fetching Public Suffix List.
 */
final class CurlAdapter implements AdapterInterface
{
    /**
     * @const int Number of seconds for HTTP timeout.
     */
    const TIMEOUT = 60;

    /**
     * @inheritdoc
     */
    public function get($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, CurlAdapter::TIMEOUT);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, CurlAdapter::TIMEOUT);

        // If windows is used, SSL verification will be disabled.

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        $responseContent = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $errorMessage = curl_error($curl);
        $errorNumber = curl_errno($curl);

        curl_close($curl);

        if ($errorNumber !== 0) {
            throw new HttpException(sprintf('Get cURL error while fetching PSL file: %s', $errorMessage));
        }

        if ($responseCode !== 200) {
            throw new HttpException(
                sprintf('Get invalid HTTP response code "%d" while fetching PSL file', $responseCode)
            );
        }

        return preg_split('/[\n\r]+/', $responseContent);
    }
}
