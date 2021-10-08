<?php
namespace Nekonomokochan\Tests\Logger;

use Nekonomokochan\PhpJsonLogger\InvalidArgumentException;
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class ErrorTest
 *
 * @package Nekonomokochan\Tests\Logger
 * @see \Nekonomokochan\PhpJsonLogger\Logger::error
 */
class ErrorTest extends TestCase
{
    /**
     * @var string
     */
    private $outputFileBaseName;

    /**
     * @var string
     */
    private $outputFileName;

    /**
     * Delete the log file used last time to test the contents of the log file
     */
    public function setUp()
    {
        parent::setUp();
        $this->outputFileBaseName = '/tmp/error-log-test.log';
        $this->outputFileName = '/tmp/error-log-test-' . date('Y-m-d') . '.log';

        if (file_exists($this->outputFileName)) {
            unlink($this->outputFileName);
        }
    }

    /**
     * @test
     */
    public function outputErrorLog()
    {
        $exception = new \Exception('TestException', 500);
        $context = [
            'name'  => 'keitakn',
            'email' => 'dummy@email.com',
        ];

        $loggerBuilder = new LoggerBuilder();
        $loggerBuilder->setFileName($this->outputFileBaseName);
        $logger = $loggerBuilder->build();
        $logger->error($exception, $context);

        $resultJson = file_get_contents($this->outputFileName);
        $resultArray = json_decode($resultJson, true);

        echo "\n ---- Output Log Begin ---- \n";
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n ---- Output Log End   ---- \n";

        $expectedLog = [
            'log_level'         => 'ERROR',
            'message'           => 'Exception',
            'channel'           => 'PhpJsonLogger',
            'trace_id'          => $logger->getTraceId(),
            'file'              => __FILE__,
            'line'              => 54,
            'context'           => $context,
            'remote_ip_address' => '127.0.0.1',
            'server_ip_address' => '127.0.0.1',
            'user_agent'        => 'unknown',
            'datetime'          => $resultArray['datetime'],
            'timezone'          => date_default_timezone_get(),
            'process_time'      => $resultArray['process_time'],
            'errors'            => [
                'message' => 'TestException',
                'code'    => 500,
                'file'    => __FILE__,
                'line'    => 45,
                'trace'   => $resultArray['errors']['trace'],
            ],
        ];

        $this->assertSame('PhpJsonLogger', $logger->getChannel());
        $this->assertSame($expectedLog, $resultArray);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Please give the exception class to the Nekonomokochan\PhpJsonLogger\Logger::error
     */
    public function invalidArgumentException()
    {
        $message = '';

        $context = [
            'name'  => 'keitakn',
            'email' => 'dummy@email.com',
        ];

        $loggerBuilder = new LoggerBuilder();
        $loggerBuilder->setFileName($this->outputFileBaseName);
        $logger = $loggerBuilder->build();
        $logger->error($message, $context);
    }
}
