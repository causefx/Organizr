<?php
namespace Nekonomokochan\PhpJsonLogger;

use Ramsey\Uuid\Uuid;

/**
 * Class Logger
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
class Logger extends \Monolog\Logger
{
    use ErrorsContextFormatter;

    use MonologCreator;

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
    private $logFileName;

    /**
     * @var int
     * @see \Monolog\Handler\RotatingFileHandler::$maxFiles
     */
    private $maxFiles;

    /**
     * Logger constructor.
     *
     * @param LoggerBuilder $builder
     * @throws \Exception
     */
    public function __construct(LoggerBuilder $builder)
    {
        $this->traceId = $builder->getTraceId();
        $this->generateTraceIdIfNeeded();
        $this->channel = $builder->getChannel();
        $this->logFileName = $builder->getFileName();
        $this->logLevel = $builder->getLogLevel();
        $this->maxFiles = $builder->getMaxFiles();

        $constructParams = $this->createConstructParams($this->traceId, $builder);

        parent::__construct(
            $constructParams['channel'],
            $constructParams['handlers'],
            $constructParams['processors']
        );
    }

    /**
     * @param $message
     * @param $context
     */
    public function debug($message, array $context = [])
    {
        $this->addDebug($message, $context);
    }

    /**
     * @param $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->addInfo($message, $context);
    }

    /**
     * @param $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->addNotice($message, $context);
    }

    /**
     * @param $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->addWarning($message, $context);
    }

    /**
     * @param \Throwable $e
     * @param array $context
     */
    public function error($e, array $context = [])
    {
        if ($this->isErrorObject($e) === false) {
            throw new \InvalidArgumentException(
                $this->generateInvalidArgumentMessage(__METHOD__)
            );
        }

        $this->addError(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
    }

    /**
     * @param \Throwable $e
     * @param array $context
     */
    public function critical($e, array $context = [])
    {
        if ($this->isErrorObject($e) === false) {
            throw new \InvalidArgumentException(
                $this->generateInvalidArgumentMessage(__METHOD__)
            );
        }

        $this->addCritical(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
    }

    /**
     * @param \Throwable $e
     * @param array $context
     */
    public function alert($e, array $context = [])
    {
        if ($this->isErrorObject($e) === false) {
            throw new \InvalidArgumentException(
                $this->generateInvalidArgumentMessage(__METHOD__)
            );
        }

        $this->addAlert(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
    }

    /**
     * @param \Throwable $e
     * @param array $context
     */
    public function emergency($e, array $context = [])
    {
        if ($this->isErrorObject($e) === false) {
            throw new \InvalidArgumentException(
                $this->generateInvalidArgumentMessage(__METHOD__)
            );
        }

        $this->addEmergency(get_class($e), $this->formatPhpJsonLoggerErrorsContext($e, $context));
    }

    /**
     * @return string
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return int
     */
    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    /**
     * @return string
     */
    public function getLogFileName(): string
    {
        return $this->logFileName;
    }

    /**
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    /**
     * Generate if TraceID is empty
     */
    private function generateTraceIdIfNeeded()
    {
        if (empty($this->traceId)) {
            $this->traceId = Uuid::uuid4()->toString();
        }
    }

    /**
     * @param $value
     * @return bool
     */
    private function isErrorObject($value): bool
    {
        if ($value instanceof \Exception || $value instanceof \Error) {
            return true;
        }

        return false;
    }

    /**
     * @param string $method
     * @return string
     */
    private function generateInvalidArgumentMessage(string $method)
    {
        return 'Please give the exception class to the ' . $method;
    }
}
