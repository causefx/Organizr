<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi;

use Exception;

/**
 * Logger reports the parser and validation messages.
 *
 * @deprecated use \OpenApi\Generator and PSR logger instead
 */
class Logger
{
    /**
     * Singleton.
     *
     * @var Logger
     */
    public static $instance;

    /**
     * @var callable
     */
    public $log;

    protected function __construct()
    {
        /*
         * @param \Exception|string $entry
         * @param int $type Error type
         */
        $this->log = function ($entry, $type) {
            if ($entry instanceof Exception) {
                $entry = $entry->getMessage();
            }
            trigger_error($entry, $type);
        };
    }

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * Log a OpenApi warning.
     *
     * @param Exception|string $entry
     */
    public static function warning($entry): void
    {
        call_user_func(self::getInstance()->log, $entry, E_USER_WARNING);
    }

    /**
     * Log a OpenApi notice.
     *
     * @param Exception|string $entry
     */
    public static function notice($entry): void
    {
        call_user_func(self::getInstance()->log, $entry, E_USER_NOTICE);
    }

    /**
     * Shorten class name(s).
     *
     * @param array|object|string $classes Class(es) to shorten
     *
     * @return string|string[] One or more shortened class names
     */
    public static function shorten($classes)
    {
        return Util::shorten($classes);
    }
}
