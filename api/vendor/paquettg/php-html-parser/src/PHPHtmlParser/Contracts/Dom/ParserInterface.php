<?php

namespace PHPHtmlParser\Contracts\Dom;

use PHPHtmlParser\Content;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\StrictException;
use PHPHtmlParser\Options;

interface ParserInterface
{
    /**
     * Attempts to parse the html in content.
     *
     * @throws ChildNotFoundException
     * @throws CircularException
     * @throws ContentLengthException
     * @throws LogicalException
     * @throws StrictException
     */
    public function parse(Options $options, Content $content, int $size): AbstractNode;

    /**
     * Attempts to detect the charset that the html was sent in.
     *
     * @throws ChildNotFoundException
     */
    public function detectCharset(Options $options, string $defaultCharset, AbstractNode $root): bool;
}
