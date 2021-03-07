<?php

namespace PHPHtmlParser\Contracts\Dom;

use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Options;

interface CleanerInterface
{
    /**
     * Cleans the html of any none-html information.
     *
     * @throws LogicalException
     */
    public function clean(string $str, Options $options, string $defaultCharset): string;
}
