<?php

declare(strict_types=1);

namespace PHPHtmlParser\Discovery;

use PHPHtmlParser\Contracts\Dom\CleanerInterface;
use PHPHtmlParser\Dom\Cleaner;

class CleanerDiscovery
{
    /**
     * @var Cleaner|null
     */
    private static $parser = null;

    public static function find(): CleanerInterface
    {
        if (self::$parser == null) {
            self::$parser = new Cleaner();
        }

        return self::$parser;
    }
}
