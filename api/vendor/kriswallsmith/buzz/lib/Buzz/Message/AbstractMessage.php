<?php

namespace Buzz\Message;

abstract class AbstractMessage implements MessageInterface
{
    private $headers = array();
    private $content;

    /**
     * Returns the value of a header.
     *
     * @param string         $name
     * @param string|boolean $glue Glue for implode, or false to return an array
     *
     * @return string|array|null
     */
    public function getHeader($name, $glue = "\r\n")
    {
        $needle = $name.':';

        $values = array();
        foreach ($this->getHeaders() as $header) {
            if (0 === stripos($header, $needle)) {
                $values[] = trim(substr($header, strlen($needle)));
            }
        }

        if (false === $glue) {
            return $values;
        } else {
            return count($values) ? implode($glue, $values) : null;
        }
    }

    /**
     * Returns a header's attributes.
     *
     * @param string $name A header name
     *
     * @return array An associative array of attributes
     */
    public function getHeaderAttributes($name)
    {
        $attributes = array();
        foreach ($this->getHeader($name, false) as $header) {
            if (false !== strpos($header, ';')) {
                // remove header value
                list(, $header) = explode(';', $header, 2);

                // loop through attribute key=value pairs
                foreach (array_map('trim', explode(';', trim($header))) as $pair) {
                    list($key, $value) = explode('=', $pair);
                    $attributes[$key] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Returns the value of a particular header attribute.
     *
     * @param string $header    A header name
     * @param string $attribute An attribute name
     *
     * @return string|null The value of the attribute or null if it isn't set
     */
    public function getHeaderAttribute($header, $attribute)
    {
        $attributes = $this->getHeaderAttributes($header);

        if (isset($attributes[$attribute])) {
            return $attributes[$attribute];
        }
    }

    /**
     * Returns the current message as a DOMDocument.
     *
     * @return \DOMDocument
     */
    public function toDomDocument()
    {
        $revert = libxml_use_internal_errors(true);

        $document = new \DOMDocument('1.0', $this->getHeaderAttribute('Content-Type', 'charset') ?: 'UTF-8');
        if (0 === strpos($this->getHeader('Content-Type'), 'text/xml')) {
            $document->loadXML($this->getContent());
        } else {
            $document->loadHTML($this->getContent());
        }

        libxml_use_internal_errors($revert);

        return $document;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $this->flattenHeaders($headers);
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $this->flattenHeaders($headers));
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function __toString()
    {
        $string = implode("\r\n", $this->getHeaders())."\r\n";

        if ($content = $this->getContent()) {
            $string .= "\r\n$content\r\n";
        }

        return $string;
    }

    protected function flattenHeaders(array $headers)
    {
        $flattened = array();
        foreach ($headers as $key => $header) {
            if (is_int($key)) {
                $flattened[] = $header;
            } else {
                $flattened[] = $key.': '.$header;
            }
        }

        return $flattened;
    }
}
