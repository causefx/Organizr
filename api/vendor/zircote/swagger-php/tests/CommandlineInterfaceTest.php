<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests;

class CommandlineInterfaceTest extends OpenApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testStdout()
    {
        $path = __DIR__ . '/../Examples/swagger-spec/petstore-simple';
        exec(__DIR__ . '/../bin/openapi --format yaml ' . escapeshellarg($path) . ' 2> /dev/null', $output, $retval);
        $this->assertSame(0, $retval);
        $yaml = implode(PHP_EOL, $output);
        $this->assertSpecEquals(file_get_contents($path . '/petstore-simple.yaml'), $yaml);
    }

    public function testOutputTofile()
    {
        $path = __DIR__ . '/../Examples/swagger-spec/petstore-simple';
        $filename = sys_get_temp_dir() . '/swagger-php-clitest.yaml';
        exec(__DIR__ . '/../bin/openapi --format yaml -o ' . escapeshellarg($filename) . ' ' . escapeshellarg($path) . ' 2> /dev/null', $output, $retval);
        $this->assertSame(0, $retval);
        $this->assertCount(0, $output, 'No output to stdout');
        $yaml = file_get_contents($filename);
        unlink($filename);
        $this->assertSpecEquals(file_get_contents($path . '/petstore-simple.yaml'), $yaml);
    }

    public function testAddProcessor()
    {
        $path = __DIR__ . '/../Examples/swagger-spec/petstore-simple';
        exec(__DIR__ . '/../bin/openapi --processor OperationId --format yaml ' . escapeshellarg($path) . ' 2> /dev/null', $output, $retval);
        $this->assertSame(0, $retval);
    }

    public function testExcludeListWarning()
    {
        $path = __DIR__ . '/../Examples/swagger-spec/petstore-simple';
        exec(__DIR__ . '/../bin/openapi -e foo,bar ' . escapeshellarg($path) . ' 2>&1', $output, $retval);
        $this->assertSame(1, $retval);
        $output = implode(PHP_EOL, $output);
        $this->assertStringContainsString('Comma-separated exclude paths are deprecated', $output);
    }

    public function testMissingArg()
    {
        $path = __DIR__ . '/../Examples/swagger-spec/petstore-simple';
        exec(__DIR__ . '/../bin/openapi ' . escapeshellarg($path) . ' -e 2>&1', $output, $retval);
        $this->assertSame(1, $retval);
        $output = implode(PHP_EOL, $output);
        $this->assertStringContainsString('Error: Missing argument for "-e"', $output);
    }
}
