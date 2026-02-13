<?php

namespace PhpXmlRpc\Traits;

use PhpXmlRpc\Helper\Charset;

/**
 * NB: if a class implements this trait, and it is subclassed, instances of the class and of the subclass will share
 * the same charset encoder instance, unless the subclass reimplements these methods
 */
trait CharsetEncoderAware
{
    protected static $charsetEncoder;

    public function getCharsetEncoder()
    {
        if (self::$charsetEncoder === null) {
            self::$charsetEncoder = Charset::instance();
        }
        return self::$charsetEncoder;
    }

    /**
     * @param $charsetEncoder
     * @return void
     */
    public static function setCharsetEncoder($charsetEncoder)
    {
        self::$charsetEncoder = $charsetEncoder;
    }
}
