<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests;

use DirectoryIterator;
use Exception;
use OpenApi\Analyser;
use OpenApi\Analysis;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Context;
use OpenApi\StaticAnalyser;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class OpenApiTestCase extends TestCase
{
    /**
     * @var array
     */
    public $expectedLogMessages = [];

    protected function setUp(): void
    {
        $this->expectedLogMessages = [];

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->assertEmpty(
            $this->expectedLogMessages,
            implode(PHP_EOL . '  => ', array_merge(
                ['OpenApi\Logger messages were not triggered:'],
                array_map(function (array $value) {
                    return $value[1];
                }, $this->expectedLogMessages)
            ))
        );

        parent::tearDown();
    }

    public function getTrackingLogger(): ?LoggerInterface
    {
        return new class($this) extends AbstractLogger {
            protected $testCase;

            public function __construct($testCase)
            {
                $this->testCase = $testCase;
            }

            public function log($level, $message, array $context = []): void
            {
                if (count($this->testCase->expectedLogMessages)) {
                    list($assertion, $needle) = array_shift($this->testCase->expectedLogMessages);
                    $assertion($message, $level);
                } else {
                    $this->testCase->fail('Unexpected log line ::' . $level . '("' . $message . '")');
                }
            }
        };
    }

    public function getContext(array $properties = [])
    {
        return new Context(['logger' => $this->getTrackingLogger()] + $properties);
    }

    public function assertOpenApiLogEntryContains($needle, $message = '')
    {
        $this->expectedLogMessages[] = [function ($entry, $type) use ($needle, $message) {
            if ($entry instanceof Exception) {
                $entry = $entry->getMessage();
            }
            $this->assertStringContainsString($needle, $entry, $message);
        }, $needle];
    }

    /**
     * Compare OpenApi specs assuming strings to contain YAML.
     *
     * @param array|OpenApi|\stdClass|string $actual     The generated output
     * @param array|OpenApi|\stdClass|string $expected   The specification
     * @param string                         $message
     * @param bool                           $normalized flag indicating whether the inputs are already normalized or not
     */
    protected function assertSpecEquals($actual, $expected, $message = '', $normalized = false)
    {
        $normalize = function ($in) {
            if ($in instanceof OpenApi) {
                $in = $in->toYaml();
            }
            if (is_string($in)) {
                // assume YAML
                try {
                    $in = Yaml::parse($in);
                } catch (ParseException $e) {
                    $this->fail('Invalid YAML: ' . $e->getMessage() . PHP_EOL . $in);
                }
            }

            return $in;
        };

        if (!$normalized) {
            $actual = $normalize($actual);
            $expected = $normalize($expected);
        }

        if (is_iterable($actual) && is_iterable($expected)) {
            foreach ($actual as $key => $value) {
                $this->assertArrayHasKey($key, (array) $expected, $message . ': property: "' . $key . '" should be absent, but has value: ' . $this->formattedValue($value));
                $this->assertSpecEquals($value, ((array) $expected)[$key], $message . ' > ' . $key, true);
            }
            foreach ($expected as $key => $value) {
                $this->assertArrayHasKey($key, (array) $actual, $message . ': property: "' . $key . '" is missing');
                $this->assertSpecEquals(((array) $actual)[$key], $value, $message . ' > ' . $key, true);
            }
        } else {
            $this->assertEquals($actual, $expected, $message);
        }
    }

    private function formattedValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return '"' . $value . '"';
        }
        if (is_object($value)) {
            return get_class($value);
        }

        return gettype($value);
    }

    protected function parseComment($comment, ?Context $context = null)
    {
        $analyser = new Analyser();
        $context = $context ?: $this->getContext();

        return $analyser->fromComment("<?php\n/**\n * " . implode("\n * ", explode("\n", $comment)) . "\n*/", $context);
    }

    /**
     * Create a valid OpenApi object with Info.
     */
    protected function createOpenApiWithInfo()
    {
        return new OpenApi([
            'info' => new Info([
                'title' => 'swagger-php Test-API',
                'version' => 'test',
                '_context' => $this->getContext(),
            ]),
            'paths' => [
                new PathItem(['path' => '/test', '_context' => $this->getContext()]),
            ],
            '_context' => $this->getContext(),
        ]);
    }

    /**
     * Resolve fixture filenames.
     *
     * @param array|string $files one ore more files
     *
     * @return array resolved filenames for loading scanning etc
     */
    public function fixtures($files): array
    {
        return array_map(function ($file) {
            return __DIR__ . '/Fixtures/' . $file;
        }, (array) $files);
    }

    public function analysisFromFixtures($files): Analysis
    {
        $analyser = new StaticAnalyser();
        $analysis = new Analysis([], $this->getContext());

        foreach ((array) $files as $file) {
            $analysis->addAnalysis($analyser->fromFile($this->fixtures($file)[0], $this->getContext()));
        }

        return $analysis;
    }

    public function analysisFromCode(string $code, ?Context $context = null)
    {
        return (new StaticAnalyser())->fromCode("<?php\n" . $code, $context ?: $this->getContext());
    }

    public function analysisFromDockBlock($comment)
    {
        return (new Analyser())->fromComment($comment, $this->getContext());
    }

    /**
     * Collect list of all non abstract annotation classes.
     *
     * @return array
     */
    public function allAnnotationClasses()
    {
        $classes = [];
        $dir = new DirectoryIterator(__DIR__ . '/../src/Annotations');
        foreach ($dir as $entry) {
            if (!$entry->isFile() || $entry->getExtension() != 'php') {
                continue;
            }
            $class = $entry->getBasename('.php');
            if (in_array($class, ['AbstractAnnotation', 'Operation'])) {
                continue;
            }
            $classes[$class] = ['OpenApi\\Annotations\\' . $class];
        }

        return $classes;
    }
}
