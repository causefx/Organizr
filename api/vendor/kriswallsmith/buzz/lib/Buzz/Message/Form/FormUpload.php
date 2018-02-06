<?php

namespace Buzz\Message\Form;

use Buzz\Message\AbstractMessage;

class FormUpload extends AbstractMessage implements FormUploadInterface
{
    private $name;
    private $filename;
    private $contentType;
    private $file;

    public function __construct($file = null, $contentType = null)
    {
        if ($file) {
            $this->loadContent($file);
        }

        $this->contentType = $contentType;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getFilename()
    {
        if ($this->filename) {
            return $this->filename;
        } elseif ($this->file) {
            return basename($this->file);
        }
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getContentType()
    {
        return $this->contentType ?: $this->detectContentType() ?: 'application/octet-stream';
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Prepends Content-Disposition and Content-Type headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = array('Content-Disposition: form-data');

        if ($name = $this->getName()) {
            $headers[0] .= sprintf('; name="%s"', $name);
        }

        if ($filename = $this->getFilename()) {
            $headers[0] .= sprintf('; filename="%s"', $filename);
        }

        if ($contentType = $this->getContentType()) {
            $headers[] = 'Content-Type: '.$contentType;
        }

        return array_merge($headers, parent::getHeaders());
    }

    /**
     * Loads the content from a file.
     *
     * @param string $file
     */
    public function loadContent($file)
    {
        $this->file = $file;

        parent::setContent(null);
    }

    public function setContent($content)
    {
        parent::setContent($content);

        $this->file = null;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getContent()
    {
        return $this->file ? file_get_contents($this->file) : parent::getContent();
    }

    // private

    private function detectContentType()
    {
        if (!class_exists('finfo', false)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $this->file ? $finfo->file($this->file) : $finfo->buffer(parent::getContent());
    }
}
