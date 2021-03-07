<?php

namespace PHPHtmlParser\Contracts\Selector;

use PHPHtmlParser\DTO\Selector\RuleDTO;
use PHPHtmlParser\Exceptions\ChildNotFoundException;

interface SeekerInterface
{
    /**
     * Attempts to find all children that match the rule
     * given.
     *
     * @throws ChildNotFoundException
     */
    public function seek(array $nodes, RuleDTO $rule, array $options): array;
}
