<?php

namespace Buzz\Client;

use Buzz\Converter\RequestConverter;
use Buzz\Converter\ResponseConverter;
use Buzz\Exception\RequestException;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Exception\ClientException;
use Psr\Http\Message\RequestInterface as PSR7RequestInterface;

class MultiCurl extends AbstractCurl implements BatchClientInterface
{
    private $queue = array();
    private $curlm;

    /**
     * Populates the supplied response with the response for the supplied request.
     *
     * The array of options will be passed to curl_setopt_array().
     *
     * If a "callback" option is supplied, its value will be called when the
     * request completes. The callable should have the following signature:
     *
     *     $callback = function($client, $request, $response, $options, $error) {
     *         if (!$error) {
     *             // success
     *         } else {
     *             // error ($error is one of the CURLE_* constants)
     *         }
     *     };
     *
     * @param RequestInterface $request  A request object
     * @param MessageInterface $response A response object
     * @param array            $options  An array of options
     *
     * @deprecated Will be removed in 1.0. Use sendRequest instead.
     */
    public function send(RequestInterface $request, MessageInterface $response, array $options = array())
    {
        @trigger_error('MultiCurl::send() is deprecated. Use MultiCurl::sendRequest instead.', E_USER_DEPRECATED);

        $request = RequestConverter::psr7($request);
        $this->queue[] = array($request, $response, $options);
    }

    public function sendRequest(PSR7RequestInterface $request, $options = [])
    {
        $this->queue[] = array($request, null, $options);
    }

    public function count()
    {
        return count($this->queue);
    }

    public function flush()
    {
        while ($this->queue) {
            $this->proceed();
        }
    }

    public function proceed()
    {
        if (!$this->queue) {
            return;
        }

        if (!$this->curlm && false === $this->curlm = curl_multi_init()) {
            throw new ClientException('Unable to create a new cURL multi handle');
        }

        foreach (array_keys($this->queue) as $i) {
            if (3 == count($this->queue[$i])) {
                // prepare curl handle
                list($request, , $options) = $this->queue[$i];
                $curl = static::createCurlHandle();

                // remove custom option
                unset($options['callback']);

                $this->prepare($curl, $request, $options);
                $this->queue[$i][] = $curl;
                curl_multi_add_handle($this->curlm, $curl);
            }
        }

        // process outstanding perform
        $active = null;
        do {
            $mrc = curl_multi_exec($this->curlm, $active);
        } while ($active && CURLM_CALL_MULTI_PERFORM == $mrc);

        // handle any completed requests
        while ($done = curl_multi_info_read($this->curlm)) {
            foreach (array_keys($this->queue) as $i) {
                list($request, $response, $options, $curl) = $this->queue[$i];

                if ($curl !== $done['handle']) {
                    continue;
                }

                // populate the response object
                if (CURLE_OK === $done['result']) {
                    $psr7Response = $this->createResponse($curl, curl_multi_getcontent($curl));
                    ResponseConverter::copy(ResponseConverter::buzz($psr7Response), $response);
                } else if (!isset($e)) {
                    $errorMsg = curl_error($curl);
                    $errorNo  = curl_errno($curl);

                    $e = new RequestException($errorMsg, $errorNo);
                    $e->setRequest($request);
                }

                // remove from queue
                curl_multi_remove_handle($this->curlm, $curl);
                curl_close($curl);
                unset($this->queue[$i]);

                // callback
                if (isset($options['callback'])) {
                    $returnResponse = isset($options['psr7_response']) && $options['psr7_response'] === true ? $psr7Response : $response;
                    call_user_func($options['callback'], $this, $request, $returnResponse, $options, $done['result']);
                }
            }
        }

        // cleanup
        if (!$this->queue) {
            curl_multi_close($this->curlm);
            $this->curlm = null;
        }

        if (isset($e)) {
            throw $e;
        }
    }
}
