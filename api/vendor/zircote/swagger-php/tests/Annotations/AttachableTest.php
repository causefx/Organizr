<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests\Annotations;

use OpenApi\Analysis;
use OpenApi\Annotations\Attachable;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use OpenApi\Tests\OpenApiTestCase;

class AttachableTest extends OpenApiTestCase
{
    public function testAttachablesAreAttached()
    {
        $analysis = $this->analysisFromFixtures('UsingVar.php');

        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);

        $this->assertCount(2, $schemas[0]->attachables);
        $this->assertInstanceOf(Attachable::class, $schemas[0]->attachables[0]);
    }

    public function testCustomAttachableImplementationsAreAttached()
    {
        $analysis = new Analysis([], $this->getContext());
        (new Generator())
            //->setAliases(['oaf' => 'OpenApi\\Tests\\Annotations'])
            ->setNamespaces(['OpenApi\\Tests\\Annotations\\'])
            ->generate($this->fixtures('UsingCustomAttachables.php'), $analysis);

        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);

        $this->assertCount(2, $schemas[0]->attachables);
        $this->assertInstanceOf(CustomAttachable::class, $schemas[0]->attachables[0]);
    }
}
