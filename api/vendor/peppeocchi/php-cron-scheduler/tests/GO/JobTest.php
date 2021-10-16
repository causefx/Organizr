<?php namespace GO\Job\Tests;

use GO\Job;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testShouldAlwaysGenerateAnId()
    {
        $job1 = new Job('ls');
        $this->assertTrue(is_string($job1->getId()));

        $job2 = new Job(function () {
            return true;
        });
        $this->assertTrue(is_string($job2->getId()));

        $job3 = new Job(['MyClass', 'myMethod']);
        $this->assertTrue(is_string($job3->getId()));
    }

    public function testShouldGenerateIdFromSignature()
    {
        $job1 = new Job('ls');
        $this->assertEquals(md5('ls'), $job1->getId());

        $job2 = new Job('whoami');
        $this->assertNotEquals($job1->getId(), $job2->getId());

        $job3 = new Job(['MyClass', 'myMethod']);
        $this->assertNotEquals($job1->getId(), $job3->getId());
    }

    public function testShouldAllowCustomId()
    {
        $job = new Job('ls', [], 'aCustomId');

        $this->assertNotEquals(md5('ls'), $job->getId());
        $this->assertEquals('aCustomId', $job->getId());

        $job2 = new Job(['MyClass', 'myMethod'], null, 'myCustomId');
        $this->assertEquals('myCustomId', $job2->getId());
    }

    public function testShouldKnowIfDue()
    {
        $job1 = new Job('ls');
        $this->assertTrue($job1->isDue());

        $job2 = new Job('ls');
        $job2->at('* * * * *');
        $this->assertTrue($job2->isDue());

        $job3 = new Job('ls');
        $job3->at('10 * * * *');
        $this->assertTrue($job3->isDue(\DateTime::createFromFormat('i', '10')));
        $this->assertFalse($job3->isDue(\DateTime::createFromFormat('i', '12')));
    }

    public function testShouldKnowIfCanRunInBackground()
    {
        $job = new Job('ls');
        $this->assertTrue($job->canRunInBackground());

        $job2 = new Job(function () {
            return "I can't run in background";
        });
        $this->assertFalse($job2->canRunInBackground());
    }

    public function testShouldForceTheJobToRunInForeground()
    {
        $job = new Job('ls');

        $this->assertTrue($job->canRunInBackground());
        $this->assertFalse($job->inForeground()->canRunInBackground());
    }

    public function testShouldReturnCompiledJobCommand()
    {
        $job1 = new Job('ls');
        $this->assertEquals('ls', $job1->inForeground()->compile());

        $fn = function () {
            return true;
        };
        $job2 = new Job($fn);
        $this->assertEquals($fn, $job2->compile());
    }

    public function testShouldCompileWithArguments()
    {
        $job = new Job('ls', [
            '-l' => null,
            '-arg' => 'value',
        ]);

        $this->assertEquals("ls '-l' '-arg' 'value'", $job->inForeground()->compile());
    }

    public function testShouldCompileCommandInBackground()
    {
        $job1 = new Job('ls');
        $job1->at('* * * * *');

        $this->assertEquals('(ls) > /dev/null 2>&1 &', $job1->compile());
    }

    public function testShouldRunInBackground()
    {
        // This script has a 5 seconds sleep
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $startTime = microtime(true);
        $job->at('* * * * *')->run();
        $endTime = microtime(true);

        $this->assertTrue(5 > ($endTime - $startTime));

        $startTime = microtime(true);
        $job->at('* * * * *')->inForeground()->run();
        $endTime = microtime(true);

        $this->assertTrue(($endTime - $startTime) >= 5);
    }

    public function testShouldRunInForegroundIfSendsEmails()
    {
        $job = new Job('ls');
        $job->email('test@mail.com');

        $this->assertFalse($job->canRunInBackground());
    }

    public function testShouldAcceptSingleOrMultipleEmails()
    {
        $job = new Job('ls');

        $this->assertInstanceOf(Job::class, $job->email('test@mail.com'));
        $this->assertInstanceOf(Job::class, $job->email(['test@mail.com', 'other@mail.com']));
    }

    public function testShouldFailIfEmailInputIsNotStringOrArray()
    {
        $this->expectException(\InvalidArgumentException::class);

        $job = new Job('ls');

        $job->email(1);
    }

    public function testShouldAcceptEmailConfigurationAndItShouldBeChainable()
    {
        $job = new Job('ls');
        $this->assertInstanceOf(Job::class, $job->configure([
            'email' => [],
        ]));
    }

    public function testShouldFailIfEmailConfigurationIsNotArray()
    {
        $this->expectException(\InvalidArgumentException::class);

        $job = new Job('ls');
        $job->configure([
            'email' => 123,
        ]);
    }

    public function testShouldCreateLockFileIfOnlyOne()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        // Default temp dir
        $tmpDir = sys_get_temp_dir();
        $lockFile = $tmpDir . '/' . $job->getId() . '.lock';

        @unlink($lockFile);

        $this->assertFalse(file_exists($lockFile));

        $job->onlyOne()->run();

        $this->assertTrue(file_exists($lockFile));
    }

    public function testShouldCreateLockFilesInCustomPath()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        // Default temp dir
        $tmpDir = __DIR__ . '/../tmp';
        $lockFile = $tmpDir . '/' . $job->getId() . '.lock';

        @unlink($lockFile);

        $this->assertFalse(file_exists($lockFile));

        $job->onlyOne($tmpDir)->run();

        $this->assertTrue(file_exists($lockFile));
    }

    public function testShouldRemoveLockFileAfterRunningClosures()
    {
        $job = new Job(function () {
            sleep(3);
        });

        // Default temp dir
        $tmpDir = __DIR__ . '/../tmp';
        $lockFile = $tmpDir . '/' . $job->getId() . '.lock';

        $job->onlyOne($tmpDir)->run();

        $this->assertFalse(file_exists($lockFile));
    }

    public function testShouldRemoveLockFileAfterRunningCommands()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        // Default temp dir
        $tmpDir = __DIR__ . '/../tmp';
        $lockFile = $tmpDir . '/' . $job->getId() . '.lock';

        $job->onlyOne($tmpDir)->run();

        sleep(1);

        $this->assertTrue(file_exists($lockFile));

        sleep(5);

        $this->assertFalse(file_exists($lockFile));
    }

    public function testShouldKnowIfOverlapping()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $this->assertFalse($job->isOverlapping());

        $tmpDir = __DIR__ . '/../tmp';

        $job->onlyOne($tmpDir)->run();

        sleep(1);

        $this->assertTrue($job->isOverlapping());

        sleep(5);

        $this->assertFalse($job->isOverlapping());
    }

    public function testShouldNotRunIfOverlapping()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $this->assertFalse($job->isOverlapping());

        $tmpDir = __DIR__ . '/../tmp';

        $job->onlyOne($tmpDir);

        sleep(1);

        $this->assertTrue($job->run());
        $this->assertFalse($job->run());

        sleep(6);
        $this->assertTrue($job->run());
    }

    public function testShouldRunIfOverlappingCallbackReturnsTrue()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $this->assertFalse($job->isOverlapping());

        $tmpDir = __DIR__ . '/../tmp';

        $job->onlyOne($tmpDir, function ($lastExecution) {
            return time() - $lastExecution > 2;
        })->run();

        // The job should not run as it is overlapping
        $this->assertFalse($job->run());
        sleep(3);
        // The job should run now as the function should now return true,
        // while it's still being executed
        $lockFile = $tmpDir . '/' . $job->getId() . '.lock';
        $this->assertTrue(file_exists($lockFile));
        $this->assertTrue($job->run());
    }

    public function testShouldAcceptTempDirInConfiguration()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $tmpDir = __DIR__ . '/../tmp';

        $job->configure([
            'tempDir' => $tmpDir,
        ])->onlyOne()->run();

        sleep(1);

        $this->assertTrue(file_exists($tmpDir . '/' . $job->getId() . '.lock'));
    }

    public function testWhenMethodShouldBeChainable()
    {
        $job = new Job('ls');

        $this->assertInstanceOf(Job::class, $job->when(function () {
            return true;
        }));
    }

    public function testShouldNotRunIfTruthTestFails()
    {
        $job = new Job('ls');

        $this->assertFalse($job->when(function () {
            return false;
        })->run());

        $this->assertTrue($job->when(function () {
            return true;
        })->run());
    }

    public function testShouldReturnOutputOfJobExecution()
    {
        $job1 = new Job(function () {
            echo 'hi';
        });
        $job1->run();
        $this->assertEquals('hi', $job1->getOutput());

        $job2 = new Job(function () {
            return 'hello';
        });
        $job2->run();
        $this->assertEquals('hello', $job2->getOutput());

        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $job3 = new Job($command);
        $job3->inForeground()->run();
        $this->assertEquals(['hi'], $job3->getOutput());
    }

    public function testShouldRunCallbackBeforeJobExecution()
    {
        $job = new Job(function () {
            return 'Job for testing before function';
        });

        $callbackWasExecuted = false;
        $outputWasSet = false;

        $job->before(function () use ($job, &$callbackWasExecuted, &$outputWasSet) {
            $callbackWasExecuted = true;
            $outputWasSet = ! is_null($job->getOutput());
        })->run();

        $this->assertTrue($callbackWasExecuted);
        $this->assertFalse($outputWasSet);
    }

    public function testShouldRunCallbackAfterJobExecution()
    {
        $job = new Job(function () {
            $visitors = 1000;

            return 'Daily visitors: ' . $visitors;
        });

        $jobResult = null;

        $job->then(function ($output) use (&$jobResult) {
            $jobResult = $output;
        })->run();

        $this->assertEquals($jobResult, $job->getOutput());

        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $job2 = new Job($command);

        $job2Result = null;

        $job2->then(function ($output) use (&$job2Result) {
            $job2Result = $output;
        }, true)->run();

        // Commands in background should return an empty string
        $this->assertTrue(empty($job2Result));

        $job2Result = null;
        $job2->then(function ($output) use (&$job2Result) {
            $job2Result = $output;
        })->inForeground()->run();
        $this->assertTrue(! empty($job2Result) &&
            $job2Result === $job2->getOutput());
    }

    public function testThenMethodShouldPassReturnCode()
    {
        $command_success = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $command_fail = $command_success . ' fail';

        $run = function ($command) {
            $job = new Job($command);
            $testReturnCode = null;

            $job->then(function ($output, $returnCode) use (&$testReturnCode, &$testOutput) {
                $testReturnCode = $returnCode;
            })->run();

            return $testReturnCode;
        };

        $this->assertEquals(0, $run($command_success));
        $this->assertNotEquals(0, $run($command_fail));
    }

    public function testThenMethodShouldBeChainable()
    {
        $job = new Job('ls');

        $this->assertInstanceOf(Job::class, $job->then(function () {
            return true;
        }));
    }

    public function testShouldDefaultExecutionInForegroundIfMethodThenIsDefined()
    {
        $job = new Job('ls');

        $job->then(function () {
            return true;
        });

        $this->assertFalse($job->canRunInBackground());
    }

    public function testShouldAllowForcingTheJobToRunInBackgroundIfMethodThenIsDefined()
    {
        // This is a use case when you want to execute a callback every time your
        // job is executed, but you don't care about the output of the job

        $job = new Job('ls');

        $job->then(function () {
            return true;
        }, true);

        $this->assertTrue($job->canRunInBackground());
    }
}
