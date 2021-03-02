<?php

declare(strict_types=1);

namespace PHPHtmlParser\Contracts\Selector;

use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\Collection;
use PHPHtmlParser\DTO\Selector\ParsedSelectorCollectionDTO;
use PHPHtmlParser\Exceptions\ChildNotFoundException;

interface SelectorInterface
{
    /**
     * Constructs with the selector string.
     */
    public function __construct(string $selector, ?ParserInterface $parser = null, ?SeekerInterface $seeker = null);

    /**
     * Returns the selectors that where found.
     */
    public function getParsedSelectorCollectionDTO(): ParsedSelectorCollectionDTO;

    /**
     * Attempts to find the selectors starting from the given
     * node object.
     *
     * @throws ChildNotFoundException
     */
    public function find(AbstractNode $node): Collection;
}
