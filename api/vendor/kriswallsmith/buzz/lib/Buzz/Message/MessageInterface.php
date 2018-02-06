<?php

namespace Buzz\Message;

/**
 * An HTTP message.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface MessageInterface
{
    /**
     * Returns a header value.
     *
     * @param string         $name A header name
     * @param string|boolean $glue Glue for implode, or false to return an array
     *
     * @return string|array|null The header value(s)
     */
    public function getHeader($name, $glue = "\r\n");

    /**
     * Returns an array of header lines.
     *
     * @return array An array of header lines (integer indexes, e.g. ["Header: value"])
     */
    public function getHeaders();

    /**
     * Sets all headers on the current message.
     *
     * Headers can be complete ["Header: value"] pairs or an associative array ["Header" => "value"]
     *
     * @param array $headers An array of header lines
     */
    public function setHeaders(array $headers);

    /**
     * Adds a header to this message.
     *
     * @param string $header A header line
     */
    public function addHeader($header);

    /**
     * Adds a set of headers to this message.
     *
     * Headers can be complete ["Header: value"] pairs or an associative array ["Header" => "value"]
     *
     * @param array $headers Headers
     */
    public function addHeaders(array $headers);

    /**
     * Returns the content of the message.
     *
     * @return string The message content
     */
    public function getContent();

    /**
     * Sets the content of the message.
     *
     * @param string $content The message content
     */
    public function setContent($content);

    /**
     * Returns the message document.
     *
     * @return string The message
     */
    public function __toString();
}
