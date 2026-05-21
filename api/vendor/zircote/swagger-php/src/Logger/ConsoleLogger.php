<?php

namespace OpenApi\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ConsoleLogger extends AbstractLogger implements LoggerInterface
{
    public const COLOR_ERROR = "\033[31m";
    public const COLOR_WARNING = "\033[33m";
    public const COLOR_STOP = "\033[0m";

    /** @var bool */
    protected $called = false;

    /** @var bool */
    protected $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function called()
    {
        return $this->called;
    }

    /**
     * @param array $context additional details; supports custom `prefix` and `exception`
     */
    public function log($level, $message, array $context = []): void
    {
        $this->called = true;

        $prefix = '';
        $color = '';
        // level adjustments
        switch ($level) {
            case LogLevel::WARNING:
                $prefix = $context['prefix'] ?? 'Warning: ';
                $color = static::COLOR_WARNING;
                break;
            case LogLevel::ERROR:
                $prefix = $context['prefix'] ?? 'Error: ';
                $color = static::COLOR_ERROR;
                break;
        }
        $stop = !empty($color) ? static::COLOR_STOP : '';

        /** @var ?\Exception $exception */
        $exception = $context['exception'] ?? null;
        if ($message instanceof \Exception) {
            $exception = $message;
            $message = $exception->getMessage();
        }

        $logLine = sprintf('%s%s%s%s', $color, $prefix, $message, $stop);
        error_log($logLine);

        if ($this->debug) {
            if ($exception) {
                error_log($exception->getTraceAsString());
            } elseif (!empty($logLine)) {
                $stack = explode(PHP_EOL, (new \Exception())->getTraceAsString());
                // self
                array_shift($stack);
                // AbstractLogger
                array_shift($stack);
                foreach ($stack as $line) {
                    error_log($line);
                }
            }
        }
    }
}
