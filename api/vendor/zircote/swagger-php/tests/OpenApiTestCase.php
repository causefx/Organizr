<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use Closure;
use DirectoryIterator;
use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use OpenApi\Analyser;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\PathItem;
use OpenApi\Context;
use OpenApi\Logger;

class OpenApiTestCase extends TestCase
{
    protected $countExceptions = 0;

    /**
     * @var array
     */
    private $expectedLogMessages;

    /**
     * @var Closure
     */
    private $originalLogger;

    /**
     * @param string  $expectedFile  File containing the excepted json.
     * @param OpenApi $actualOpenApi
     * @param string  $message
     */
    public function assertOpenApiEqualsFile($expectedFile, $actualOpenApi, $message = '')
    {
        $expected = json_decode(file_get_contents($expectedFile));
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            $this->fail('File: "'.$expectedFile.'" doesn\'t contain valid json, error '.$error);
        }
        $json = json_encode($actualOpenApi);
        if ($json === false) {
            $this->fail('Failed to encode openapi object');
        }
        $actual = json_decode($json);
        $expectedJson = json_encode($this->sorted($expected, $expectedFile), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $actualJson = json_encode($this->sorted($actual, 'OpenApi'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->assertEquals($expectedJson, $actualJson, $message);
    }

    public function assertOpenApiLog($expectedEntry, $expectedType, $message = '')
    {
        $this->expectedLogMessages[] = function ($actualEntry, $actualType) use ($expectedEntry, $expectedType, $message) {
            $this->assertSame($expectedEntry, $actualEntry, $message);
            $this->assertSame($expectedType, $actualType, $message);
        };
    }

    public function assertOpenApiLogType($expectedType, $message = '')
    {
        $this->expectedLogMessages[] = function ($entry, $actualType) use ($expectedType, $message) {
            $this->assertSame($expectedType, $actualType, $message);
        };
    }

    public function assertOpenApiLogEntry($expectedEntry, $message = '')
    {
        $this->expectedLogMessages[] = function ($actualEntry, $type) use ($expectedEntry, $message) {
            $this->assertSame($expectedEntry, $actualEntry, $message);
        };
    }

    public function assertOpenApiLogEntryStartsWith($entryPrefix, $message = '')
    {
        $this->expectedLogMessages[] = function ($entry, $type) use ($entryPrefix, $message) {
            if ($entry instanceof Exception) {
                $entry = $entry->getMessage();
            }
            $this->assertStringStartsWith($entryPrefix, $entry, $message);
        };
    }

    protected function setUp(): void
    {
        $this->expectedLogMessages = [];
        $this->originalLogger = Logger::getInstance()->log;
        Logger::getInstance()->log = function ($entry, $type) {
            if (count($this->expectedLogMessages)) {
                $assertion = array_shift($this->expectedLogMessages);
                $assertion($entry, $type);
            } else {
                $map = [
                    E_USER_NOTICE => 'notice',
                    E_USER_WARNING => 'warning',
                ];
                if (isset($map[$type])) {
                    $this->fail('Unexpected \OpenApi\Logger::'.$map[$type].'("'.$entry.'")');
                } else {
                    $this->fail('Unexpected \OpenApi\Logger->getInstance()->log("'.$entry.'",'.$type.')');
                }
            }
        };
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->assertCount($this->countExceptions, $this->expectedLogMessages, count($this->expectedLogMessages).' OpenApi\Logger messages were not triggered');
        Logger::getInstance()->log = $this->originalLogger;
        parent::tearDown();
    }

    /**
     *
     * @param string $comment Contents of a comment block
     *
     * @return AbstractAnnotation[]
     */
    protected function parseComment($comment)
    {
        $analyser = new Analyser();
        $context = Context::detect(1);
        return $analyser->fromComment("<?php\n/**\n * ".implode("\n * ", explode("\n", $comment))."\n*/", $context);
    }

    /**
     * Create a OpenApi object with Info.
     * (So it will pass validation.)
     */
    protected function createOpenApiWithInfo()
    {
        $openapi = new OpenApi(
            [
            'info' => new Info(
                [
                'title' => 'swagger-php Test-API',
                'version' => 'test',
                '_context' => new Context(['unittest' => true]),
                ]
            ),
            'paths' => [
                new PathItem(['path' => '/test'])
            ],
            '_context' => new Context(['unittest' => true]),
            ]
        );
        return $openapi;
    }

    /**
     * Sorts the object to improve matching and debugging the differences.
     * Used by assertOpenApiEqualsFile
     *
     * @param stdClass $object
     * @param string   $origin
     *
     * @return stdClass The sorted object
     */
    protected function sorted(stdClass $object, $origin = 'unknown')
    {
        static $sortMap = null;
        if ($sortMap === null) {
            $sortMap = [
                // property -> algorithm
                'parameters' => function ($a, $b) {
                    return strcasecmp($a->name, $b->name);
                },
                // 'responses' => function ($a, $b) {
                //     return strcasecmp($a->name, $b->name);
                // },
                'headers' => function ($a, $b) {
                    return strcasecmp($a->header, $b->header);
                },
                'tags' => function ($a, $b) {
                    return strcasecmp($a->name, $b->name);
                },
                'allOf' => function ($a, $b) {
                    return strcasecmp(implode(',', array_keys(get_object_vars($a))), implode(',', array_keys(get_object_vars($b))));
                },
                'security' => function ($a, $b) {
                    return strcasecmp(implode(',', array_keys(get_object_vars($a))), implode(',', array_keys(get_object_vars($b))));
                },
            ];
        }
        $data = unserialize(serialize((array)$object));
        ksort($data);
        foreach ($data as $property => $value) {
            if (is_object($value)) {
                $data[$property] = $this->sorted($value, $origin.'->'.$property);
            } elseif (is_array($value)) {
                if (count($value) > 1) {
                    if (gettype($value[0]) === 'string') {
                        $sortFn = 'strcasecmp';
                    } else {
                        $sortFn = isset($sortMap[$property]) ? $sortMap[$property] : null;
                    }
                    if ($sortFn) {
                        usort($value, $sortFn);
                        $data[$property] = $value;
                    } else {
                        echo 'no sort for '.$origin.'->'.$property."\n";
                        die;
                    }
                }
                foreach ($value as $i => $element) {
                    if (is_object($element)) {
                        $data[$property][$i] = $this->sorted($element, $origin.'->'.$property.'['.$i.']');
                    }
                }
            }
        }
        return (object)$data;
    }

    public function allAnnotations()
    {
        $data = [];
        $dir = new DirectoryIterator(__DIR__.'/../src/Annotations');
        foreach ($dir as $entry) {
            if ($entry->isFile() === false) {
                continue;
            }
            $class = substr($entry->getFilename(), 0, -4);
            if (in_array($class, ['AbstractAnnotation','Operation'])) {
                continue; // skip abstract classes
            }
            $data[] = ['OpenApi\\Annotations\\'.$class];
        }
        return $data;
    }
}
