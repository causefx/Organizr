<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Logger;

use OpenApi\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class DefaultLogger extends AbstractLogger implements LoggerInterface
{
    public function log($level, $message, array $context = []): void
    {
        // BC: delegate to the static instance
        if (in_array($level, [LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG])) {
            Logger::notice($message);
        } else {
            Logger::warning($message);
        }
    }
}
