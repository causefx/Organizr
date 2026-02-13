<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests\Processors;

use OpenApi\Annotations\Operation;
use OpenApi\Generator;
use OpenApi\Processors\DocBlockDescriptions;
use OpenApi\Tests\OpenApiTestCase;

class DocBlockDescriptionsTest extends OpenApiTestCase
{
    public function testDocBlockDescription()
    {
        $analysis = $this->analysisFromFixtures('UsingPhpDoc.php');
        $analysis->process(
            [
            new DocBlockDescriptions(),
            ]
        );
        $operations = $analysis->getAnnotationsOfType(Operation::class);

        $this->assertSame('api/test1', $operations[0]->path);
        $this->assertSame('Example summary', $operations[0]->summary, 'Operation summary should be taken from phpDoc');
        $this->assertSame("Example description...\nMore description...", $operations[0]->description, 'Operation description should be taken from phpDoc');

        $this->assertSame('api/test2', $operations[1]->path);
        $this->assertSame('Example summary', $operations[1]->summary, 'Operation summary should be taken from phpDoc');
        $this->assertSame(Generator::UNDEFINED, $operations[1]->description, 'This operation only has summary in the phpDoc, no description');
    }
}
