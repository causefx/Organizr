<?php

namespace PhpXmlRpc\Traits;

use PhpXmlRpc\Helper\XMLParser;

/**
 * NB: if a class implements this trait, and it is subclassed, instances of the class and of the subclass will share
 * the same parser instance, unless the subclass reimplements these methods
 */
trait ParserAware
{
    protected static $parser;

    /// @todo feature-creep: allow passing in $options (but then, how to deal with changing options between invocations?)
    public function getParser()
    {
        if (self::$parser === null) {
            self::$parser = new XMLParser();
        }
        return self::$parser;
    }

    /**
     * @param $parser
     * @return void
     */
    public static function setParser($parser)
    {
        self::$parser = $parser;
    }
}
