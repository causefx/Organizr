<?php

namespace PhpXmlRpc\Traits;

use PhpXmlRpc\Helper\Logger;

/**
 * NB: if a class implements this trait, and it is subclassed, instances of the class and of the subclass will share
 * the same logger instance, unless the subclass reimplements these methods
 */
trait LoggerAware
{
    protected static $logger;

    public function getLogger()
    {
        if (self::$logger === null) {
            self::$logger = Logger::instance();
        }
        return self::$logger;
    }

    /**
     * @param $logger
     * @return void
     */
    public static function setLogger($logger)
    {
        self::$logger = $logger;
    }
}
