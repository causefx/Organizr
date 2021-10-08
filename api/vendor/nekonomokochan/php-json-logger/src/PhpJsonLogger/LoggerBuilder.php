<?php
namespace Nekonomokochan\PhpJsonLogger;

/**
 * Class LoggerBuilder
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
class LoggerBuilder
{
    /**
     * Detailed debug information
     */
    const DEBUG = 100;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;

    /**
     * Uncommon events
     */
    const NOTICE = 250;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;

    /**
     * Runtime errors
     */
    const ERROR = 400;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;

    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;

    /**
     * @see \Monolog\Logger::$name
     */
    const DEFAULT_CHANNEL = 'PhpJsonLogger';

    /**
     * @see \Monolog\Processor\IntrospectionProcessor::$skipClassesPartials
     */
    const DEFAULT_SKIP_CLASSES_PARTIALS = ['Nekonomokochan\\PhpJsonLogger\\'];

    /**
     * @see @see \Monolog\Processor\IntrospectionProcessor::$skipStackFramesCount
     */
    const DEFAULT_SKIP_STACK_FRAMES_COUNT = 0;

    /**
     * @see \Monolog\Handler\RotatingFileHandler::$maxFiles
     */
    const DEFAULT_MAX_FILES = 7;

    /**
     * @var string
     */
    private $traceId;

    /**
     * @var string
     * @see \Monolog\Logger::$name
     */
    private $channel;

    /**
     * @var int
     */
    private $logLevel;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var array
     * @see \Monolog\Processor\IntrospectionProcessor::$skipClassesPartials
     */
    private $skipClassesPartials = [];

    /**
     * @var int
     * @see \Monolog\Processor\IntrospectionProcessor::$skipStackFramesCount
     */
    private $skipStackFramesCount;

    /**
     * @var int
     * @see \Monolog\Handler\RotatingFileHandler::$maxFiles
     */
    private $maxFiles;

    /**
     * @var \Monolog\Handler\SlackHandler
     */
    private $slackHandler;

    /**
     * @var boolean
     */
    private $useInDocker = false;

    /**
     * LoggerBuilder constructor.
     *
     * @param string $traceId
     */
    public function __construct(string $traceId = '')
    {
        $this->traceId = $traceId;
        $this->setChannel(self::DEFAULT_CHANNEL);
        $this->setLogLevel(self::INFO);
        $this->setFileName('/tmp/php-json-logger.log');
        $this->setSkipClassesPartials(self::DEFAULT_SKIP_CLASSES_PARTIALS);
        $this->setSkipStackFramesCount(self::DEFAULT_SKIP_STACK_FRAMES_COUNT);
        $this->setMaxFiles(self::DEFAULT_MAX_FILES);
    }

    /**
     * @return string
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * @param string $traceId
     */
    public function setTraceId(string $traceId)
    {
        $this->traceId = $traceId;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return int
     */
    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    /**
     * @param int $logLevel
     */
    public function setLogLevel(int $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return array
     */
    public function getSkipClassesPartials(): array
    {
        return $this->skipClassesPartials;
    }

    /**
     * @param array $skipClassesPartials
     */
    public function setSkipClassesPartials(array $skipClassesPartials)
    {
        $this->skipClassesPartials = array_merge($this->skipClassesPartials, $skipClassesPartials);
    }

    /**
     * @return int
     */
    public function getSkipStackFramesCount(): int
    {
        return $this->skipStackFramesCount;
    }

    /**
     * @param int $skipStackFramesCount
     */
    public function setSkipStackFramesCount(int $skipStackFramesCount)
    {
        $this->skipStackFramesCount = $skipStackFramesCount;
    }

    /**
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    /**
     * @param int $maxFiles
     */
    public function setMaxFiles(int $maxFiles)
    {
        $this->maxFiles = $maxFiles;
    }

    /**
     * @return \Monolog\Handler\SlackHandler
     */
    public function getSlackHandler():? \Monolog\Handler\SlackHandler
    {
        return $this->slackHandler;
    }

    /**
     * @param \Monolog\Handler\SlackHandler $slackHandler
     */
    public function setSlackHandler(\Monolog\Handler\SlackHandler $slackHandler)
    {
        $this->slackHandler = $slackHandler;
    }

    /**
     * @return bool
     */
    public function isUseInDocker(): bool
    {
        return $this->useInDocker;
    }

    /**
     * @param bool $useInDocker
     */
    public function setUseInDocker(bool $useInDocker): void
    {
        $this->useInDocker = $useInDocker;
    }

    /**
     * @return Logger
     * @throws \Exception
     */
    public function build()
    {
        return new Logger($this);
    }
}
