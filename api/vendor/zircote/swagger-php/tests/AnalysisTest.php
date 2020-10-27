<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analysis;
use OpenApi\StaticAnalyser;

class AnalysisTest extends OpenApiTestCase
{
    public function testRegisterProcessor()
    {
        $counter = 0;
        $analysis = new Analysis();
        $analysis->process();
        $this->assertSame(0, $counter);
        $countProcessor = function (Analysis $a) use (&$counter) {
            $counter++;
        };
        Analysis::registerProcessor($countProcessor);
        $analysis->process();
        $this->assertSame(1, $counter);
        Analysis::unregisterProcessor($countProcessor);
        $analysis->process();
        $this->assertSame(1, $counter);
    }

    public function testGetSubclasses()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Child.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/GrandAncestor.php'));
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Ancestor.php'));

        $this->assertCount(3, $analysis->classes, '3 classes should\'ve been detected');

        $subclasses = $analysis->getSubClasses('\OpenApiFixtures\GrandAncestor');
        $this->assertCount(2, $subclasses, 'GrandAncestor has 2 subclasses');
        $this->assertSame(['\OpenApiFixtures\Ancestor', '\AnotherNamespace\Child'], array_keys($subclasses));
        $this->assertSame(['\AnotherNamespace\Child'], array_keys($analysis->getSubClasses('\OpenApiFixtures\Ancestor')));
    }

    public function testGetAncestorClasses()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Child.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/GrandAncestor.php'));
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Ancestor.php'));

        $this->assertCount(3, $analysis->classes, '3 classes should\'ve been detected');

        $superclasses = $analysis->getSuperClasses('\AnotherNamespace\Child');
        $this->assertCount(2, $superclasses, 'Child has a chain of 2 super classes');
        $this->assertSame(['\OpenApiFixtures\Ancestor', '\OpenApiFixtures\GrandAncestor'], array_keys($superclasses));
        $this->assertSame(['\OpenApiFixtures\GrandAncestor'], array_keys($analysis->getSuperClasses('\OpenApiFixtures\Ancestor')));
    }
}
