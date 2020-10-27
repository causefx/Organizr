<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Annotations\Operation;
use OpenApi\Processors\AugmentOperations;
use OpenApi\StaticAnalyser;
use const OpenApi\UNDEFINED;

class AugmentOperationTest extends OpenApiTestCase
{
    public function testAugmentOperation()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/UsingPhpDoc.php');
        $analysis->process(
            [
            new AugmentOperations(),
            ]
        );
        $operations = $analysis->getAnnotationsOfType(Operation::class);

        $this->assertSame('api/test1', $operations[0]->path);
        $this->assertSame('Example summary', $operations[0]->summary, 'Operation summary should be taken from phpDoc');
        $this->assertSame("Example description...\nMore description...", $operations[0]->description, 'Operation description should be taken from phpDoc');

        $this->assertSame('api/test2', $operations[1]->path);
        $this->assertSame('Example summary', $operations[1]->summary, 'Operation summary should be taken from phpDoc');
        $this->assertSame(UNDEFINED, $operations[1]->description, 'This operation only has summary in the phpDoc, no description');
    }
}
