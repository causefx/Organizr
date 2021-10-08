<?php
namespace Nekonomokochan\Tests;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Nekonomokochan\PhpJsonLogger\ErrorsContextFormatter;
use Nekonomokochan\PhpJsonLogger\JsonFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class ExtendedMonologTest
 *
 * @package Nekonomokochan\Tests
 */
class ExtendedMonologTest extends TestCase
{
    use ErrorsContextFormatter;

    /**
     * @var string
     */
    private $logFileName = '/tmp/extended-monolog-test.log';

    /**
     * @var Logger
     */
    private $extendedMonolog;

    /**
     * create extendedMonolog Instance
     *
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();
        // Delete the log file to assert the log file
        $defaultFile = '/tmp/extended-monolog-test-' . date('Y-m-d') . '.log';
        if (file_exists($defaultFile)) {
            unlink($defaultFile);
        }

        // create extendedMonolog Instance
        $formatter = new JsonFormatter();

        $rotating = new RotatingFileHandler(
            $this->logFileName,
            7,
            Logger::INFO
        );
        $rotating->setFormatter($formatter);

        $introspection = new IntrospectionProcessor(
            Logger::INFO,
            ['Nekonomokochan\\PhpJsonLogger\\'],
            0
        );

        $extraRecords = function ($record) {
            $record['extra']['trace_id'] = 'ExtendedMonologTestTraceId';
            $record['extra']['created_time'] = microtime(true);

            return $record;
        };

        $this->extendedMonolog = new Logger(
            'ExtendedMonolog',
            [$rotating],
            [$introspection, $extraRecords]
        );
    }

    /**
     * @test
     */
    public function outputInfoLog()
    {
        $context = [
            'cat'    => '🐱',
            'dog'    => '🐶',
            'rabbit' => '🐰',
        ];

        $this->extendedMonolog->info('outputInfoLogTest', $context);

        $resultJson = file_get_contents('/tmp/extended-monolog-test-' . date('Y-m-d') . '.log');
        $resultArray = json_decode($resultJson, true);

        echo "\n ---- Output Log Begin ---- \n";
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n ---- Output Log End   ---- \n";

        $expectedLog = [
            'log_level'         => 'INFO',
            'message'           => 'outputInfoLogTest',
            'channel'           => 'ExtendedMonolog',
            'trace_id'          => 'ExtendedMonologTestTraceId',
            'file'              => __FILE__,
            'line'              => 85,
            'context'           => $context,
            'remote_ip_address' => '127.0.0.1',
            'server_ip_address' => '127.0.0.1',
            'user_agent'        => 'unknown',
            'datetime'          => $resultArray['datetime'],
            'timezone'          => date_default_timezone_get(),
            'process_time'      => $resultArray['process_time'],
        ];

        $this->assertSame('ExtendedMonolog', $this->extendedMonolog->getName());
        $this->assertSame($expectedLog, $resultArray);
    }

    /**
     * @test
     */
    public function outputErrorLog()
    {
        $exception = new \Exception('ExtendedMonologTest.outputErrorLog', 500);
        $context = [
            'cat'    => '🐱(=^・^=)🐱',
            'dog'    => '🐶Uo･ｪ･oU🐶',
            'rabbit' => '🐰🐰🐰',
        ];

        $this->extendedMonolog->error(
            get_class($exception),
            $this->formatPhpJsonLoggerErrorsContext($exception, $context)
        );

        $resultJson = file_get_contents('/tmp/extended-monolog-test-' . date('Y-m-d') . '.log');
        $resultArray = json_decode($resultJson, true);

        echo "\n ---- Output Log Begin ---- \n";
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n ---- Output Log End   ---- \n";

        $expectedLog = [
            'log_level'         => 'ERROR',
            'message'           => get_class($exception),
            'channel'           => 'ExtendedMonolog',
            'trace_id'          => 'ExtendedMonologTestTraceId',
            'file'              => __FILE__,
            'line'              => 128,
            'context'           => $context,
            'remote_ip_address' => '127.0.0.1',
            'server_ip_address' => '127.0.0.1',
            'user_agent'        => 'unknown',
            'datetime'          => $resultArray['datetime'],
            'timezone'          => date_default_timezone_get(),
            'process_time'      => $resultArray['process_time'],
            'errors'            => [
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $resultArray['errors']['trace'],
            ],
        ];

        $this->assertSame('ExtendedMonolog', $this->extendedMonolog->getName());
        $this->assertSame($expectedLog, $resultArray);
    }
}
