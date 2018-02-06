<?php

namespace Buzz\Message\Form;

use Buzz\Message\Request;
use Buzz\Exception\LogicException;

/**
 * FormRequest.
 *
 *     $request = new FormRequest();
 *     $request->setField('user[name]', 'Kris Wallsmith');
 *     $request->setField('user[image]', new FormUpload('/path/to/image.jpg'));
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FormRequest extends Request implements FormRequestInterface
{
    private $fields = array();
    private $boundary;

    /**
     * Constructor.
     *
     * Defaults to POST rather than GET.
     */
    public function __construct($method = self::METHOD_POST, $resource = '/', $host = null)
    {
        parent::__construct($method, $resource, $host);
    }

    /**
     * Sets the value of a form field.
     *
     * If the value is an array it will be flattened and one field value will
     * be added for each leaf.
     */
    public function setField($name, $value)
    {
        if (is_array($value)) {
            $this->addFields(array($name => $value));

            return;
        }

        if ('[]' == substr($name, -2)) {
            $this->fields[substr($name, 0, -2)][] = $value;
        } else {
            $this->fields[$name] = $value;
        }
    }

    public function addFields(array $fields)
    {
        foreach ($this->flattenArray($fields) as $name => $value) {
            $this->setField($name, $value);
        }
    }

    public function setFields(array $fields)
    {
        $this->fields = array();
        $this->addFields($fields);
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getResource()
    {
        $resource = parent::getResource();

        if (!$this->isSafe() || !$this->fields) {
            return $resource;
        }

        // append the query string
        $resource .= false === strpos($resource, '?') ? '?' : '&';
        $resource .= http_build_query($this->fields);

        return $resource;
    }

    public function setContent($content)
    {
        throw new \BadMethodCallException('It is not permitted to set the content.');
    }

    public function getHeaders()
    {
        $headers = parent::getHeaders();

        if ($this->isSafe()) {
            return $headers;
        }

        if ($this->isMultipart()) {
            $headers[] = 'Content-Type: multipart/form-data; boundary='.$this->getBoundary();
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        return $headers;
    }

    public function getContent()
    {
        if ($this->isSafe()) {
            return;
        }

        if (!$this->isMultipart()) {
            return http_build_query($this->fields, '', '&');
        }

        $content = '';

        foreach ($this->fields as $name => $values) {
            $content .= '--'.$this->getBoundary()."\r\n";
            if ($values instanceof FormUploadInterface) {
                if (!$values->getFilename()) {
                    throw new LogicException(sprintf('Form upload at "%s" does not include a filename.', $name));
                }

                $values->setName($name);
                $content .= (string) $values;
            } else {
                foreach (is_array($values) ? $values : array($values) as $value) {
                    $content .= "Content-Disposition: form-data; name=\"$name\"\r\n";
                    $content .= "\r\n";
                    $content .= $value."\r\n";
                }
            }
        }

        $content .= '--'.$this->getBoundary().'--';

        return $content;
    }

    // private

    private function flattenArray(array $values, $prefix = '', $format = '%s')
    {
        $flat = array();

        foreach ($values as $name => $value) {
            $flatName = $prefix.sprintf($format, $name);

            if (is_array($value)) {
                $flat += $this->flattenArray($value, $flatName, '[%s]');
            } else {
                $flat[$flatName] = $value;
            }
        }

        return $flat;
    }

    private function isSafe()
    {
        return in_array($this->getMethod(), array(self::METHOD_GET, self::METHOD_HEAD));
    }

    private function isMultipart()
    {
        foreach ($this->fields as $name => $value) {
            if (is_object($value) && $value instanceof FormUploadInterface) {
                return true;
            }
        }

        return false;
    }

    private function getBoundary()
    {
        if (!$this->boundary) {
            $this->boundary = sha1(rand(11111, 99999).time().uniqid());
        }

        return $this->boundary;
    }
}
