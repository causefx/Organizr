<?php namespace GO\Job\Tests;

use GO\Job;
use DateTime;
use GO\FailedJob;
use GO\Scheduler;
use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{
    public function testShouldQueueJobs()
    {
        $scheduler = new Scheduler();

        $this->assertEquals(count($scheduler->getQueuedJobs()), 0);

        $scheduler->raw('ls');

        $this->assertEquals(count($scheduler->getQueuedJobs()), 1);
    }

    public function testShouldQueueAPhpScript()
    {
        $scheduler = new Scheduler();

        $script = __DIR__ . '/../test_job.php';

        $this->assertEquals(count($scheduler->getQueuedJobs()), 0);

        $scheduler->php($script);

        $this->assertEquals(count($scheduler->getQueuedJobs()), 1);
    }

    public function testShouldAllowCustomPhpBin()
    {
        $scheduler = new Scheduler();
        $script = __DIR__ . '/../test_job.php';

        // Create fake bin
        $bin = __DIR__ . '/../custom_bin';
        touch($bin);

        $job = $scheduler->php($script, $bin)->inForeground();

        unlink($bin);

        $this->assertEquals($bin . ' ' . $script, $job->compile());
    }

    public function testShouldUseSystemPhpBinIfCustomBinDoesNotExist()
    {
        $scheduler = new Scheduler();
        $script = __DIR__ . '/../test_job.php';

        // Create fake bin
        $bin = '/my/custom/php/bin';

        $job = $scheduler->php($script, $bin)->inForeground();

        $this->assertNotEquals($bin . ' ' . $script, $job->compile());
        $this->assertEquals(PHP_BINARY . ' ' . $script, $job->compile());
    }

    public function testShouldThrowExceptionIfScriptIsNotAString()
    {
        $this->expectException(\InvalidArgumentException::class);

        $scheduler = new Scheduler();
        $scheduler->php(function () {
            return false;
        });

        $scheduler->run();
    }

    public function testShouldMarkJobAsFailedIfScriptPathIsInvalid()
    {
        $scheduler = new Scheduler();
        $scheduler->php('someInvalidPathToAScript');

        $scheduler->run();
        $fail = $scheduler->getFailedJobs();
        $this->assertCount(1, $fail);
        $this->assertContainsOnlyInstancesOf(FailedJob::class, $fail);
    }

    public function testShouldQueueAShellCommand()
    {
        $scheduler = new Scheduler();

        $this->assertEquals(count($scheduler->getQueuedJobs()), 0);

        $scheduler->raw('ls');

        $this->assertEquals(count($scheduler->getQueuedJobs()), 1);
    }

    public function testShouldQueueAFunction()
    {
        $scheduler = new Scheduler();

        $this->assertEquals(count($scheduler->getQueuedJobs()), 0);

        $scheduler->call(function () {
            return true;
        });

        $this->assertEquals(count($scheduler->getQueuedJobs()), 1);
    }

    public function testShouldKeepTrackOfExecutedJobs()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function () {
            return true;
        });

        $this->assertEquals(count($scheduler->getQueuedJobs()), 1);
        $this->assertEquals(count($scheduler->getExecutedJobs()), 0);

        $scheduler->run();

        $this->assertEquals(count($scheduler->getExecutedJobs()), 1);
    }

    public function testShouldPassParametersToAFunction()
    {
        $scheduler = new Scheduler();

        $outputFile = __DIR__ . '/../tmp/output.txt';
        $scheduler->call(function ($phrase) {
            return $phrase;
        }, [
            'Hello World!',
        ])->output($outputFile);

        @unlink($outputFile);

        $this->assertFalse(file_exists($outputFile));

        $scheduler->run();

        $this->assertNotEquals('Hello', file_get_contents($outputFile));
        $this->assertEquals('Hello World!', file_get_contents($outputFile));

        @unlink($outputFile);
    }

    public function testShouldKeepTrackOfFailedJobs()
    {
        $scheduler = new Scheduler();

        $exception = new \Exception('Something failed');
        $scheduler->call(function () use ($exception) {
            throw $exception;
        });

        $this->assertEquals(count($scheduler->getFailedJobs()), 0);

        $scheduler->run();

        $this->assertEquals(count($scheduler->getExecutedJobs()), 0);
        $this->assertEquals(count($scheduler->getFailedJobs()), 1);
        $failedJob = $scheduler->getFailedJobs()[0];
        $this->assertInstanceOf(FailedJob::class, $failedJob);
        $this->assertSame($exception, $failedJob->getException());
        $this->assertInstanceOf(Job::class, $failedJob->getJob());
    }

    public function testShouldKeepExecutingJobsIfOneFails()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function () {
            throw new \Exception('Something failed');
        });

        $scheduler->call(function () {
            return true;
        });

        $scheduler->run();

        $this->assertEquals(count($scheduler->getExecutedJobs()), 1);
        $this->assertEquals(count($scheduler->getFailedJobs()), 1);
    }

    public function testShouldInjectConfigToTheJobs()
    {
        $schedulerConfig = [
            'email' => [
                'subject' => 'My custom subject',
            ],
        ];
        $scheduler = new Scheduler($schedulerConfig);

        $job = $scheduler->raw('ls');

        $this->assertEquals($job->getEmailConfig()['subject'], $schedulerConfig['email']['subject']);
    }

    public function testShouldPrioritizeJobConfigOverSchedulerConfig()
    {
        $schedulerConfig = [
            'email' => [
                'subject' => 'My custom subject',
            ],
        ];
        $scheduler = new Scheduler($schedulerConfig);

        $jobConfig = [
            'email' => [
                'subject' => 'My job subject',
            ],
        ];
        $job = $scheduler->raw('ls')->configure($jobConfig);

        $this->assertNotEquals($job->getEmailConfig()['subject'], $schedulerConfig['email']['subject']);
        $this->assertEquals($job->getEmailConfig()['subject'], $jobConfig['email']['subject']);
    }

    public function testShouldShowClosuresVerboseOutputAsText()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function ($phrase) {
            return $phrase;
        }, [
            'Hello World!',
        ]);

        $scheduler->run();

        $this->assertMatchesRegularExpression('/ Executing Closure$/', $scheduler->getVerboseOutput());
        $this->assertMatchesRegularExpression('/ Executing Closure$/', $scheduler->getVerboseOutput('text'));
    }

    public function testShouldShowClosuresVerboseOutputAsHtml()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function ($phrase) {
            return $phrase;
        }, [
            'Hello World!',
        ]);

        $scheduler->call(function () {
            return true;
        });

        $scheduler->run();

        $this->assertMatchesRegularExpression('/<br>/', $scheduler->getVerboseOutput('html'));
    }

    public function testShouldShowClosuresVerboseOutputAsArray()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function ($phrase) {
            return $phrase;
        }, [
            'Hello World!',
        ]);

        $scheduler->call(function () {
            return true;
        });

        $scheduler->run();

        $this->assertTrue(is_array($scheduler->getVerboseOutput('array')));
        $this->assertEquals(count($scheduler->getVerboseOutput('array')), 2);
    }

    public function testShouldThrowExceptionWithInvalidOutputType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $scheduler = new Scheduler();

        $scheduler->call(function ($phrase) {
            return $phrase;
        }, [
            'Hello World!',
        ]);

        $scheduler->call(function () {
            return true;
        });

        $scheduler->run();

        $scheduler->getVerboseOutput('multiline');
    }

    public function testShouldPrioritizeJobsInBackround()
    {
        $scheduler = new Scheduler();

        $scheduler->php(__DIR__ . '/../async_job.php', null, null, 'async_foreground')->then(function () {
            return true;
        });

        $scheduler->php(__DIR__ . '/../async_job.php', null, null, 'async_background');

        $jobs = $scheduler->getQueuedJobs();

        $this->assertEquals('async_background', $jobs[0]->getId());
        $this->assertEquals('async_foreground', $jobs[1]->getId());
    }

    public function testCouldRunTwice()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function () {
            return true;
        });

        $scheduler->run();

        $this->assertCount(1, $scheduler->getExecutedJobs(), 'Number of executed jobs');

        $scheduler->resetRun();
        $scheduler->run();

        $this->assertCount(1, $scheduler->getExecutedJobs(), 'Number of executed jobs');
    }

    public function testClearJobs()
    {
        $scheduler = new Scheduler();

        $scheduler->call(function () {
            return true;
        });

        $this->assertCount(1, $scheduler->getQueuedJobs(), 'Number of queued jobs');

        $scheduler->clearJobs();

        $this->assertCount(0, $scheduler->getQueuedJobs(), 'Number of queued jobs');
    }

    public function testShouldRunDelayedJobsIfDueWhenCreated()
    {
        $scheduler = new Scheduler();
        $currentTime = date('H:i');

        $scheduler->call(function () {
            $s = (int) date('s');
            sleep(60 - $s + 1);
        })->daily($currentTime);

        $scheduler->call(function () {
            // do nothing
        })->daily($currentTime);

        $executed = $scheduler->run();

        $this->assertEquals(2, count($executed));
    }

    public function testShouldRunAtSpecificTime()
    {
        $scheduler = new Scheduler();
        $runTime = new DateTime('2017-09-13 00:00:00');

        $scheduler->call(function () {
            // do nothing
        })->daily('00:00');

        $executed = $scheduler->run($runTime);

        $this->assertEquals(1, count($executed));
    }
}
