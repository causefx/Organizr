<?php

namespace Buzz\Message\Form;

use Buzz\Message\MessageInterface;

interface FormUploadInterface extends MessageInterface
{
    public function setName($name);
    public function getFile();
    public function getFilename();
    public function getContentType();
}
