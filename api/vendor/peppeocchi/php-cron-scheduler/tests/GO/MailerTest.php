<?php namespace GO\Job\Tests;

use GO\Job;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testShouldHaveDefaultConfigToSendAnEmail()
    {
        $job = new Job('ls');
        $config = $job->getEmailConfig();

        $this->assertTrue(isset($config['subject']));
        $this->assertTrue(isset($config['from']));
        $this->assertTrue(isset($config['body']));
        $this->assertTrue(isset($config['transport']));
    }

    public function testShouldAllowCustomTransportWhenSendingEmails()
    {
        $job = new Job(function () {
            return 'hi';
        });

        $job->configure([
            'email' => [
                'transport' => new \Swift_NullTransport(),
            ],
        ]);

        $this->assertInstanceOf(\Swift_NullTransport::class, $job->getEmailConfig()['transport']);
    }

    public function testEmailTransportShouldAlwaysBeInstanceOfSwift_Transport()
    {
        $job = new Job(function () {
            return 'hi';
        });

        $job->configure([
            'email' => [
                'transport' => 'Something not allowed',
            ],
        ]);

        $this->assertInstanceOf(\Swift_Transport::class, $job->getEmailConfig()['transport']);
    }

    public function testShouldSendJobOutputToEmail()
    {
        $emailAddress = 'local@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $job1 = new Job($command);

        $job2 = new Job(function () {
            return 'Hello World!';
        });

        $nullTransportConfig = [
            'email' => [
                'transport' => new \Swift_NullTransport(),
            ],
        ];
        $job1->configure($nullTransportConfig);
        $job2->configure($nullTransportConfig);

        $outputFile1 = __DIR__ . '/../tmp/output001.log';
        $this->assertTrue($job1->output($outputFile1)->email($emailAddress)->run());
        $outputFile2 = __DIR__ . '/../tmp/output002.log';
        $this->assertTrue($job2->output($outputFile2)->email($emailAddress)->run());

        unlink($outputFile1);
        unlink($outputFile2);
    }

    public function testShouldSendMultipleFilesToEmail()
    {
        $emailAddress = 'local@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile1 = __DIR__ . '/../tmp/output003.log';
        $outputFile2 = __DIR__ . '/../tmp/output004.log';

        $nullTransportConfig = [
            'email' => [
                'transport' => new \Swift_NullTransport(),
            ],
        ];
        $job->configure($nullTransportConfig);

        $this->assertTrue($job->output([
            $outputFile1, $outputFile2,
        ])->email([$emailAddress])->run());

        unlink($outputFile1);
        unlink($outputFile2);
    }

    public function testShouldSendToMultipleEmails()
    {
        $emailAddress1 = 'local@localhost.com';
        $emailAddress2 = 'local1@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile = __DIR__ . '/../tmp/output005.log';

        $nullTransportConfig = [
            'email' => [
                'transport' => new \Swift_NullTransport(),
            ],
        ];
        $job->configure($nullTransportConfig);

        $this->assertTrue($job->output($outputFile)->email([
            $emailAddress1, $emailAddress2,
        ])->run());

        unlink($outputFile);
    }

    public function testShouldAcceptCustomEmailConfig()
    {
        $emailAddress = 'local@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile = __DIR__ . '/../tmp/output6.log';

        $this->assertTrue(
            $job->output($outputFile)->email($emailAddress)
                ->configure([
                    'email' => [
                        'subject' => 'My custom subject',
                        'from' => 'my@custom.from',
                        'body' => 'My custom body',
                        'transport' => new \Swift_NullTransport(),
                    ],
                ])->run()
        );

        unlink($outputFile);
    }

    public function testShouldIgnoreEmailIfSpecifiedInConfig()
    {
        $job = new Job(function () {
            $tot = 1 + 2;
            // Return nothing....
        });

        $nullTransportConfig = [
            'email' => [
                'transport' => new \Swift_NullTransport(),
                'ignore_empty_output' => true,
            ],
        ];
        $job->configure($nullTransportConfig);

        $outputFile = __DIR__ . '/../tmp/output.log';
        $this->assertTrue($job->output($outputFile)->email('local@localhost.com')->run());

        @unlink($outputFile);
    }
}
