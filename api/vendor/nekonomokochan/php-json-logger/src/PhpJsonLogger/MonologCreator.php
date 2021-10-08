<?php
namespace Nekonomokochan\PhpJsonLogger;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Trait MonologCreator
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
trait MonologCreator
{
    /**
     * @param string $traceId
     * @param LoggerBuilder $loggerBuilder
     * @return array
     * @throws \Exception
     */
    public function createConstructParams(string $traceId, LoggerBuilder $loggerBuilder): array
    {
        $formatter = new JsonFormatter();
        $handlers = [];

        $rotating = new RotatingFileHandler(
            $loggerBuilder->getFileName(),
            $loggerBuilder->getMaxFiles(),
            $loggerBuilder->getLogLevel()
        );
        $rotating->setFormatter($formatter);

        if ($loggerBuilder->isUseInDocker()) {
            $streamHandler = new StreamHandler('php://stdout', $loggerBuilder->getLogLevel());
            $streamHandler->setFormatter($formatter);
            array_push($handlers, $streamHandler);
        } else {
            $rotating = new RotatingFileHandler(
                $loggerBuilder->getFileName(),
                $loggerBuilder->getMaxFiles(),
                $loggerBuilder->getLogLevel()
            );
            $rotating->setFormatter($formatter);
            array_push($handlers, $rotating);
        }

        $introspection = new IntrospectionProcessor(
            $loggerBuilder->getLogLevel(),
            $loggerBuilder->getSkipClassesPartials(),
            $loggerBuilder->getSkipStackFramesCount()
        );

        $extraRecords = function ($record) use ($traceId) {
            $record['extra']['trace_id'] = $traceId;
            $record['extra']['created_time'] = microtime(true);

            return $record;
        };

        $processors = [
            $introspection,
            $extraRecords,
        ];

        if ($loggerBuilder->getSlackHandler() instanceof SlackHandler) {
            $slack = $loggerBuilder->getSlackHandler();
            $slack->setFormatter($formatter);

            array_push(
                $handlers,
                $slack
            );

            $webProcessor = new WebProcessor();
            $webProcessor->addExtraField('server_ip_address', 'SERVER_ADDR');
            $webProcessor->addExtraField('user_agent', 'HTTP_USER_AGENT');
            array_push($processors, $webProcessor);
        }

        return [
            'channel'    => $loggerBuilder->getChannel(),
            'handlers'   => $handlers,
            'processors' => $processors
        ];
    }
}
