<?php
namespace Nekonomokochan\Tests\Logger;

use Nekonomokochan\PhpJsonLogger\LoggerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class WarningTest
 *
 * @package Nekonomokochan\Tests\Logger
 * @see \Nekonomokochan\PhpJsonLogger\Logger::warning
 */
class WarningTest extends TestCase
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
        $this->outputFileBaseName = '/tmp/warning-log-test.log';
        $this->outputFileName = '/tmp/warning-log-test-' . date('Y-m-d') . '.log';

        if (file_exists($this->outputFileName)) {
            unlink($this->outputFileName);
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function outputWarningLog()
    {
        $context = [
            'title' => 'Test',
        ];

        $loggerBuilder = new LoggerBuilder();
        $loggerBuilder->setFileName($this->outputFileBaseName);
        $loggerBuilder->setLogLevel(LoggerBuilder::DEBUG);
        $logger = $loggerBuilder->build();
        $logger->warning('🐶', $context);

        $resultJson = file_get_contents($this->outputFileName);
        $resultArray = json_decode($resultJson, true);

        echo "\n ---- Output Log Begin ---- \n";
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n ---- Output Log End   ---- \n";

        $expectedLog = [
            'log_level'         => 'WARNING',
            'message'           => '🐶',
            'channel'           => 'PhpJsonLogger',
            'trace_id'          => $logger->getTraceId(),
            'file'              => __FILE__,
            'line'              => 53,
            'context'           => $context,
            'remote_ip_address' => '127.0.0.1',
            'server_ip_address' => '127.0.0.1',
            'user_agent'        => 'unknown',
            'datetime'          => $resultArray['datetime'],
            'timezone'          => date_default_timezone_get(),
            'process_time'      => $resultArray['process_time'],
        ];

        $this->assertSame('PhpJsonLogger', $logger->getChannel());
        $this->assertSame($expectedLog, $resultArray);
    }
}
