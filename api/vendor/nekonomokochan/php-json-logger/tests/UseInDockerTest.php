<?php
namespace Nekonomokochan\Tests;

use Nekonomokochan\PhpJsonLogger\LoggerBuilder;
use Nekonomokochan\PhpJsonLogger\SlackHandlerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class UseInDockerTest
 * @package Nekonomokochan\Tests
 */
class UseInDockerTest extends TestCase
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
        $this->outputFileBaseName = '/tmp/docker-log-test.log';
        $this->outputFileName = '/tmp/docker-log-test-' . date('Y-m-d') . '.log';

        if (file_exists($this->outputFileName)) {
            unlink($this->outputFileName);
        }
    }

    /**
     * @test
     * @throws \Monolog\Handler\MissingExtensionException
     */
    public function outputStdLogWithSetFileName()
    {
        $exception = new \Exception('testOutputStdLogWithSetFileName', 500);
        $context = [
            'name'    => 'keitakn',
            'email'   => 'dummy@email.com',
            'message' => 'testOutputStdLogWithSetFileName',
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
        $loggerBuilder->setUseInDocker(true);
        $logger = $loggerBuilder->build();
        $logger->critical($exception, $context);

        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['SERVER_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);

        $this->assertFalse(file_exists($this->outputFileName));
    }

    /**
     * @test
     * @throws \Monolog\Handler\MissingExtensionException
     */
    public function outputStdLogWithNoSetFileName()
    {
        $exception = new \Exception('testOutputStdLogWithNoSetFileName', 500);
        $context = [
            'name'    => 'keitakn',
            'email'   => 'dummy@email.com',
            'message' => 'testOutputStdLogWithNoSetFileName',
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
        $loggerBuilder->setSlackHandler($slackHandlerBuilder->build());
        $loggerBuilder->setUseInDocker(true);
        $logger = $loggerBuilder->build();
        $logger->critical($exception, $context);

        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['SERVER_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);

        $this->assertFalse(file_exists($this->outputFileName));
    }
}
