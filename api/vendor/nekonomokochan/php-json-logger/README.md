# php-json-logger
[![Latest Stable Version](https://poser.pugx.org/nekonomokochan/php-json-logger/v/stable)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![Total Downloads](https://poser.pugx.org/nekonomokochan/php-json-logger/downloads)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![Latest Unstable Version](https://poser.pugx.org/nekonomokochan/php-json-logger/v/unstable)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![License](https://poser.pugx.org/nekonomokochan/php-json-logger/license)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![Monthly Downloads](https://poser.pugx.org/nekonomokochan/php-json-logger/d/monthly)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![Daily Downloads](https://poser.pugx.org/nekonomokochan/php-json-logger/d/daily)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![composer.lock](https://poser.pugx.org/nekonomokochan/php-json-logger/composerlock)](https://packagist.org/packages/nekonomokochan/php-json-logger)
[![Build Status](https://travis-ci.org/nekonomokochan/php-json-logger.svg?branch=master)](https://travis-ci.org/nekonomokochan/php-json-logger)
[![Coverage Status](https://coveralls.io/repos/github/nekonomokochan/php-json-logger/badge.svg?branch=master)](https://coveralls.io/github/nekonomokochan/php-json-logger?branch=master)

LoggingLibrary for PHP. Output by JSON Format

This Library is mainly intended for use in web applications.

## Getting Started

### Install composer package

```
composer require nekonomokochan/php-json-logger
```

## How To Use

### Basic usage

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

$context = [
    'title' => 'Test',
    'price' => 4000,
    'list'  => [1, 2, 3],
    'user'  => [
        'id'   => 100,
        'name' => 'keitakn',
    ],
];

$loggerBuilder = new LoggerBuilder();
$logger = $loggerBuilder->build();
$logger->info('🐱', $context);
```

It is output as follows.

```json
{
    "log_level": "INFO",
    "message": "🐱",
    "channel": "PhpJsonLogger",
    "trace_id": "35b627ce-55e0-4729-9da0-fbda2a7d817d",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/LoggerTest.php",
    "line": 42,
    "context": {
        "title": "Test",
        "price": 4000,
        "list": [
            1,
            2,
            3
        ],
        "user": {
            "id": 100,
            "name": "keitakn"
        }
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-04 17:21:03.631409",
    "timezone": "Asia\/Tokyo",
    "process_time": 631.50811195373535
}
```

The unit of `process_time` is ms(millisecond).

#### How to change output filepath

Default output filepath is `/tmp/php-json-logger-yyyy-mm-dd.log` .

If you want to change the output filepath, please set the output filepath to the builder class.

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

$fileName = '/tmp/test-php-json-logger.log';

$context = [
    'cat'    => '🐱',
    'dog'    => '🐶',
    'rabbit' => '🐰',
];

$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setFileName($fileName);
$logger = $loggerBuilder->build();
$logger->info('testSetLogFileName', $context);
```

The output filepath is `/tmp/test-php-json-logger-yyyy-mm-dd.log` .

It is output as follows.

```json
{
    "log_level": "INFO",
    "message": "testSetLogFileName",
    "channel": "PhpJsonLogger",
    "trace_id": "20f39cdb-dbd8-470c-babd-093a2974d169",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/LoggerTest.php",
    "line": 263,
    "context": {
        "cat": "🐱",
        "dog": "🐶",
        "rabbit": "🐰"
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-05 11:28:03.214995",
    "timezone": "Asia\/Tokyo",
    "process_time": 215.09790420532227
}
```

#### How to Set `trace_id`

Any value can be set for `trace_id`.

This will help you when looking for logs you want.

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

$context = [
    'name' => 'keitakn',
];

$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setTraceId('MyTraceID');
$logger = $loggerBuilder->build();
$logger->info('testSetTraceIdIsOutput', $context);
```

It is output as follows.

```json
{
    "log_level": "INFO",
    "message": "testSetTraceIdIsOutput",
    "channel": "PhpJsonLogger",
    "trace_id": "MyTraceID",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/LoggerTest.php",
    "line": 214,
    "context": {
        "name": "keitakn"
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-05 11:36:02.394269",
    "timezone": "Asia\/Tokyo",
    "process_time": 394.35911178588867
}
```

#### How to change logLevel

Please use `\Nekonomokochan\PhpJsonLogger\LoggerBuilder::setLogLevel()` .

For example, the following code does not output logs.

Because the level is set to `CRITICAL`.

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

$context = [
    'cat'    => '🐱',
    'dog'    => '🐶',
    'rabbit' => '🐰',
];

$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setLogLevel(LoggerBuilder::CRITICAL);
$logger = $loggerBuilder->build();
$logger->info('testSetLogLevel', $context);
```

You can set the following values for `logLevel` .

These are the same as `logLevel` defined in [Monolog](https://github.com/Seldaek/monolog).

- DEBUG = 100
- INFO = 200
- NOTICE = 250
- WARNING = 300
- ERROR = 400
- CRITICAL = 500
- ALERT = 550
- EMERGENCY = 600

#### How to change channel

Default channel is `PhpJsonLogger`.

If you want to change the channel, you can change it with the following code.

```php
$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setChannel('My Favorite Animals');
```

For example, the output is as follows.

```json
{
    "log_level": "INFO",
    "message": "testCanSetChannel",
    "channel": "My Favorite Animals",
    "trace_id": "4b8aa070-a533-4376-9bf5-270c8fcc6d87",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/Logger\/LoggerTest.php",
    "line": 347,
    "context": {
        "animals": "🐱🐶🐰🐱🐹"
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-07 17:56:48.538117",
    "timezone": "Asia\/Tokyo",
    "process_time": 538.48695755004883
}
```

#### How to change Log Rotation Date

This is the default setting to save logs for 7 days.

If you want to change the log Rotation date, you can change it with the following code.

The following code sets the log retention period to 2 days.

```php
$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setMaxFiles(2);
```

### Extend and use `\Nekonomokochan\PhpJsonLogger\JsonFormatter`

You can make your own `\Monolog\Logger` using only `\Nekonomokochan\PhpJsonLogger\JsonFormatter`.

This method is useful when you need `\Monolog\Logger` in web application framework(e.g. [Laravel](https://github.com/laravel/laravel)).

The following is sample code.

```php
<?php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;
use Nekonomokochan\PhpJsonLogger\JsonFormatter;

$logFileName = '/tmp/extended-monolog-test.log';

// create extendedMonolog Instance
$formatter = new JsonFormatter();

$rotating = new RotatingFileHandler(
    $logFileName,
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

$extendedMonolog = new Logger(
    'ExtendedMonolog',
    [$rotating],
    [$introspection, $extraRecords]
);

// output info log
$context = [
    'cat'    => '🐱',
    'dog'    => '🐶',
    'rabbit' => '🐰',
];

$extendedMonolog->info('outputInfoLogTest', $context);
```

It is output to `extended-monolog-test-yyyy-mm-dd.log` as follows

```json
{
    "log_level": "INFO",
    "message": "outputInfoLogTest",
    "channel": "ExtendedMonolog",
    "trace_id": "ExtendedMonologTestTraceId",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/ExtendedMonologTest.php",
    "line": 85,
    "context": {
        "cat": "🐱",
        "dog": "🐶",
        "rabbit": "🐰"
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-06 17:14:26.042013",
    "timezone": "Asia\/Tokyo",
    "process_time": 0.1678466796875
}
```

The following code is necessary to output `trace_id` and `process_time`.

```php
<?php
$extraRecords = function ($record) {
    $record['extra']['trace_id'] = 'ExtendedMonologTestTraceId';
    $record['extra']['created_time'] = microtime(true);

    return $record;
};
```

The code below is the code necessary to normally display the stack trace with JSON.

```php
<?php
$introspection = new IntrospectionProcessor(
    Logger::INFO,
    ['Nekonomokochan\\PhpJsonLogger\\'],
    0
);
```

To output the stack trace to the log, execute the following code.

```php
<?php
$exception = new \Exception('ExtendedMonologTest.outputErrorLog', 500);
$context = [
    'cat'    => '🐱(=^・^=)🐱',
    'dog'    => '🐶Uo･ｪ･oU🐶',
    'rabbit' => '🐰🐰🐰',
];

$extendedMonolog->error(
    get_class($exception),
    $this->formatPhpJsonLoggerErrorsContext($exception, $context)
);
```

Please pay attention to the part `$this->formatPhpJsonLoggerErrorsContext($exception, $context)`.

This is necessary processing to format the error log into JSON and output it.

This is the method implemented in `\Nekonomokochan\PhpJsonLogger\ErrorsContextFormat`.

It is output to `extended-monolog-test-yyyy-mm-dd.log` as follows.

If you want to know more detailed usage, please look at `php-json-logger/tests/ExtendedMonologTest.php`.

```json
{
    "log_level": "ERROR",
    "message": "Exception",
    "channel": "PhpJsonLogger",
    "trace_id": "ExtendedMonologTestTraceId",
    "file": "\/home\/vagrant\/php-json-logger\/tests\/ExtendedMonologTest.php",
    "line": 126,
    "context": {
        "cat": "🐱(=^・^=)🐱",
        "dog": "🐶Uo･ｪ･oU🐶",
        "rabbit": "🐰🐰🐰"
    },
    "remote_ip_address": "127.0.0.1",
    "server_ip_address": "127.0.0.1",
    "user_agent": "unknown",
    "datetime": "2018-06-06 17:37:57.440757",
    "timezone": "Asia\/Tokyo",
    "process_time": 0.16093254089355469,
    "errors": {
        "message": "ExtendedMonologTest.outputErrorLog",
        "code": 500,
        "file": "\/home\/vagrant\/php-json-logger\/tests\/ExtendedMonologTest.php",
        "line": 117,
        "trace": [
            "#0 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php(1145): Nekonomokochan\\Tests\\ExtendedMonologTest->outputErrorLog()",
            "#1 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php(840): PHPUnit\\Framework\\TestCase->runTest()",
            "#2 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestResult.php(645): PHPUnit\\Framework\\TestCase->runBare()",
            "#3 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestCase.php(798): PHPUnit\\Framework\\TestResult->run()",
            "#4 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/Framework\/TestSuite.php(776): PHPUnit\\Framework\\TestCase->run()",
            "#5 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/TextUI\/TestRunner.php(529): PHPUnit\\Framework\\TestSuite->run()",
            "#6 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/TextUI\/Command.php(198): PHPUnit\\TextUI\\TestRunner->doRun()",
            "#7 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/src\/TextUI\/Command.php(151): PHPUnit\\TextUI\\Command->run()",
            "#8 \/home\/vagrant\/php-json-logger\/vendor\/phpunit\/phpunit\/phpunit(53): PHPUnit\\TextUI\\Command::main()"
        ]
    }
}
```

### Notification To Slack

To send the log to Slack please execute the following code.

This code will be sent to slack if the log level is `CRITICAL` or higher.

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;
use Nekonomokochan\PhpJsonLogger\SlackHandlerBuilder;

$exception = new \Exception('TestException', 500);
$context = [
    'name'  => 'keitakn',
    'email' => 'dummy@email.com',
];

$slackToken = 'YOUR_SLACK_TOKEN';
$slackChannel = 'YOUR_SLACK_CHANNEL';

$slackHandlerBuilder = new SlackHandlerBuilder($slackToken, $slackChannel);
$slackHandlerBuilder->setLevel(LoggerBuilder::CRITICAL);

$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setFileName($this->outputFileBaseName);
$loggerBuilder->setSlackHandler($slackHandlerBuilder->build());
$logger = $loggerBuilder->build();
$logger->critical($exception, $context);
```

### Use in Docker

Please use `LoggerBuilder.setUseInDocker` in order to use it on Docker.

When setUseInDocker is set to true, no file output is done and the log is output as `stdout`.

```php
<?php
use Nekonomokochan\PhpJsonLogger\LoggerBuilder;

$exception = new \Exception('TestException', 500);
$context = [
    'name'  => 'keitakn',
    'email' => 'dummy@email.com',
];

$loggerBuilder = new LoggerBuilder();
$loggerBuilder->setFileName($this->outputFileBaseName);
$loggerBuilder->setUseInDocker(true);
$logger = $loggerBuilder->build();
$logger->critical($exception, $context);
```

### Caution

`\Nekonomokochan\PhpJsonLogger\Logger` is a subclass that extends `\Monolog\Logger`

You can use it like `\Monolog\Logger`.

However, for the following methods, you can pass only classes that extend `\Exception` or `\Error` as arguments.

- `\Nekonomokochan\PhpJsonLogger\Logger::error()`
- `\Nekonomokochan\PhpJsonLogger\Logger::critical()`
- `\Nekonomokochan\PhpJsonLogger\Logger::alert()`
- `\Nekonomokochan\PhpJsonLogger\Logger::emergency()`

In case of violation, `\Nekonomokochan\PhpJsonLogger\Logger` will Throw `\Nekonomokochan\PhpJsonLogger\InvalidArgumentException`

## License
MIT
