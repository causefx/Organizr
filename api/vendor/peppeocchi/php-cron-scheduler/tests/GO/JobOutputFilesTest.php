<?php namespace GO\Job\Tests;

use GO\Job;
use PHPUnit\Framework\TestCase;

class JobOutputFilesTest extends TestCase
{
    public function testShouldWriteCommandOutputToSingleFile()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $job = new Job($command);
        $outputFile = __DIR__ . '/../tmp/output.log';

        @unlink($outputFile);

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile));
        $job->output($outputFile)->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile));

        // Content should be 'hi'
        $this->assertEquals('hi', file_get_contents($outputFile));

        unlink($outputFile);
    }

    public function testShouldWriteCommandOutputToMultipleFiles()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';
        $job = new Job($command);
        $outputFile1 = __DIR__ . '/../tmp/output1.log';
        $outputFile2 = __DIR__ . '/../tmp/output2.log';
        $outputFile3 = __DIR__ . '/../tmp/output3.log';

        @unlink($outputFile1);
        @unlink($outputFile2);
        @unlink($outputFile3);

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile1));
        $this->assertFalse(file_exists($outputFile2));
        $this->assertFalse(file_exists($outputFile3));
        $job->output([
            $outputFile1,
            $outputFile2,
            $outputFile3,
        ])->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile1));
        $this->assertTrue(file_exists($outputFile2));
        $this->assertTrue(file_exists($outputFile3));

        $this->assertEquals('hi', file_get_contents($outputFile1));
        $this->assertEquals('hi', file_get_contents($outputFile2));
        $this->assertEquals('hi', file_get_contents($outputFile3));

        unlink($outputFile1);
        unlink($outputFile2);
        unlink($outputFile3);
    }

    public function testShouldWriteFunctionOutputToSingleFile()
    {
        $job = new Job(function () {
            echo 'Hello ';

            return 'World!';
        });
        $outputFile = __DIR__ . '/../tmp/output.log';

        @unlink($outputFile);

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile));
        $job->output($outputFile)->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile));

        $this->assertEquals('Hello World!', file_get_contents($outputFile));

        unlink($outputFile);
    }

    public function testShouldWriteFunctionOutputToMultipleFiles()
    {
        $job = new Job(function () {
            echo 'Hello';
        });
        $outputFile1 = __DIR__ . '/../tmp/output1.log';
        $outputFile2 = __DIR__ . '/../tmp/output2.log';
        $outputFile3 = __DIR__ . '/../tmp/output3.log';

        @unlink($outputFile1);
        @unlink($outputFile2);
        @unlink($outputFile3);

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile1));
        $this->assertFalse(file_exists($outputFile2));
        $this->assertFalse(file_exists($outputFile3));
        $job->output([
            $outputFile1,
            $outputFile2,
            $outputFile3,
        ])->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile1));
        $this->assertTrue(file_exists($outputFile2));
        $this->assertTrue(file_exists($outputFile3));

        $this->assertEquals('Hello', file_get_contents($outputFile1));
        $this->assertEquals('Hello', file_get_contents($outputFile2));
        $this->assertEquals('Hello', file_get_contents($outputFile3));

        unlink($outputFile1);
        unlink($outputFile2);
        unlink($outputFile3);
    }

    public function testShouldWriteFunctionReturnToSingleFile()
    {
        $job = new Job(function () {
            return 'Hello World!';
        });
        $outputFile = __DIR__ . '/../tmp/output1.log';

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile));
        $job->output($outputFile)->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile));

        $this->assertEquals('Hello World!', file_get_contents($outputFile));

        unlink($outputFile);
    }

    public function testShouldWriteFunctionReturnToMultipleFiles()
    {
        $job = new Job(function () {
            return ['Hello ', 'World!'];
        });
        $outputFile1 = __DIR__ . '/../tmp/output1.log';
        $outputFile2 = __DIR__ . '/../tmp/output2.log';
        $outputFile3 = __DIR__ . '/../tmp/output3.log';

        @unlink($outputFile1);
        @unlink($outputFile2);
        @unlink($outputFile3);

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile1));
        $this->assertFalse(file_exists($outputFile2));
        $this->assertFalse(file_exists($outputFile3));
        $job->output([
            $outputFile1,
            $outputFile2,
            $outputFile3,
        ])->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile1));
        $this->assertTrue(file_exists($outputFile2));
        $this->assertTrue(file_exists($outputFile3));

        $this->assertEquals('Hello World!', file_get_contents($outputFile1));
        $this->assertEquals('Hello World!', file_get_contents($outputFile2));
        $this->assertEquals('Hello World!', file_get_contents($outputFile3));

        unlink($outputFile1);
        unlink($outputFile2);
        unlink($outputFile3);
    }

    public function testShouldWriteFunctionOutputAndReturnToFile()
    {
        $job = new Job(function () {
            echo 'Hello ';

            return 'World!';
        });
        $outputFile = __DIR__ . '/../tmp/output1.log';

        // Test fist that the file doesn't exist yet
        $this->assertFalse(file_exists($outputFile));
        $job->output($outputFile)->run();

        sleep(2);
        $this->assertTrue(file_exists($outputFile));

        $this->assertEquals('Hello World!', file_get_contents($outputFile));

        unlink($outputFile);
    }
}
