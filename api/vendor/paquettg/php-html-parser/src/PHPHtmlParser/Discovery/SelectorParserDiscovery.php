<?php

declare(strict_types=1);

namespace PHPHtmlParser\Discovery;

use PHPHtmlParser\Contracts\Selector\ParserInterface;
use PHPHtmlParser\Selector\Parser;

class SelectorParserDiscovery
{
    /**
     * @var ParserInterface|null
     */
    private static $parser = null;

    public static function find(): ParserInterface
    {
        if (self::$parser == null) {
            self::$parser = new Parser();
        }

        return self::$parser;
    }
}
