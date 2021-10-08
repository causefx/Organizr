<?php
namespace Nekonomokochan\Tests\Logger;

use Nekonomokochan\PhpJsonLogger\LoggerBuilder;
use Nekonomokochan\PhpJsonLogger\SlackHandlerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class SlackNotificationTest
 *
 * @package Nekonomokochan\Tests\Logger
 */
class SlackNotificationTest extends TestCase
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
        $this->outputFileBaseName = '/tmp/slack-log-test.log';
        $this->outputFileName = '/tmp/slack-log-test-' . date('Y-m-d') . '.log';

        if (file_exists($this->outputFileName)) {
            unlink($this->outputFileName);
        }
    }

    /**
     * @test
     */
    public function notificationToSlack()
    {
        $exception = new \Exception('TestException', 500);
        $context = [
            'name'  => 'keitakn',
            'email' => 'dummy@email.com',
        ];

        $slackToken = getenv('PHP_JSON_LOGGER_SLACK_TOKEN', true) ?: getenv('PHP_JSON_LOGGER_SLACK_TOKEN');
        $slackChannel = getenv('PHP_JSON_LOGGER_SLACK_CHANNEL', true) ?: getenv('PHP_JSON_LOGGER_SLACK_CHANNEL');

        $slackHandlerBuilder = new SlackHandlerBuilder($slackToken, $slackChannel);
        $slackHandlerBuilder->setLevel(LoggerBuilder::CRITICAL);

        $_SERVER['REQUEST_URI'] = '/tests/notifications';
        $_SERVER['REMOTE_ADDR'] = '192.168.10.10';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SERVER_NAME'] = 'cat-moko.localhost';
        $_SERVER['HTTP_REFERER'] = 'https://github.com/nekonomokochan/php-json-logger/issues/50';
        $_SERVER['SERVER_ADDR'] = '10.0.0.11';
        $_SERVER['HTTP_USER_AGENT'] = 'Chrome';

        $loggerBuilder = new LoggerBuilder();
        $loggerBuilder->setFileName($this->outputFileBaseName);
        $loggerBuilder->setSlackHandler($slackHandlerBuilder->build());
        $logger = $loggerBuilder->build();
        $logger->critical($exception, $context);

        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['SERVER_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);

        $resultJson = file_get_contents($this->outputFileName);
        $resultArray = json_decode($resultJson, true);

        echo "\n ---- Output Log Begin ---- \n";
        echo json_encode($resultArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n ---- Output Log End   ---- \n";

        $expectedLog = [
            'log_level'         => 'CRITICAL',
            'message'           => 'Exception',
            'channel'           => 'PhpJsonLogger',
            'trace_id'          => $logger->getTraceId(),
            'file'              => __FILE__,
            'line'              => 68,
            'context'           => $context,
            'remote_ip_address' => '192.168.10.10',
            'server_ip_address' => '10.0.0.11',
            'user_agent'        => 'Chrome',
            'datetime'          => $resultArray['datetime'],
            'timezone'          => date_default_timezone_get(),
            'process_time'      => $resultArray['process_time'],
            'errors'            => [
                'message' => 'TestException',
                'code'    => 500,
                'file'    => __FILE__,
                'line'    => 44,
                'trace'   => $resultArray['errors']['trace'],
            ],
        ];

        $this->assertSame('PhpJsonLogger', $logger->getChannel());
        $this->assertSame($expectedLog, $resultArray);
    }
}
