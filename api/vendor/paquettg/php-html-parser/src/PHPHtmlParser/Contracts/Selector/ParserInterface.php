<?php

declare(strict_types=1);

namespace PHPHtmlParser\Contracts\Selector;

use PHPHtmlParser\DTO\Selector\ParsedSelectorCollectionDTO;

interface ParserInterface
{
    public function parseSelectorString(string $selector): ParsedSelectorCollectionDTO;
}
