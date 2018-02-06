<?php

namespace Buzz\Message\Form;

use Buzz\Message\RequestInterface;

/**
 * An HTTP request message sent by a web form.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface FormRequestInterface extends RequestInterface
{
    /**
     * Returns an array of field names and values.
     *
     * @return array A array of names and values
     */
    public function getFields();

    /**
     * Sets the form fields for the current request.
     *
     * @param array $fields An array of field names and values
     */
    public function setFields(array $fields);
}
