<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analyser;
use OpenApi\Annotations\Property;
use OpenApi\Context;
use OpenApi\StaticAnalyser;

class StaticAnalyserTest extends OpenApiTestCase
{
    public function testWrongCommentType()
    {
        $analyser = new StaticAnalyser();
        $this->assertOpenApiLogEntryStartsWith('Annotations are only parsed inside `/**` DocBlocks');
        $analyser->fromCode("<?php\n/*\n * @OA\Parameter() */", new Context([]));
    }

    public function testIndentationCorrection()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/routes.php');
        $this->assertCount(20, $analysis->annotations);
    }

    public function testTrait()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/HelloTrait.php');
        $this->assertCount(2, $analysis->annotations);
        $property = $analysis->getAnnotationsOfType(Property::class);
        $this->assertSame('Hello', $property[0]->_context->trait);
    }

    public function testThirdPartyAnnotations()
    {
        $backup = Analyser::$whitelist;
        Analyser::$whitelist = ['OpenApi\Annotations\\'];
        $analyser = new StaticAnalyser();
        $defaultAnalysis = $analyser->fromFile(__DIR__.'/Fixtures/ThirdPartyAnnotations.php');
        $this->assertCount(3, $defaultAnalysis->annotations, 'Only read the @OA annotations, skip the others.');
        // Allow the analyser to parse 3rd party annotations, which might
        // contain useful info that could be extracted with a custom processor
        Analyser::$whitelist[] = 'Zend\Form\Annotation';
        $openapi = \OpenApi\scan(__DIR__.'/Fixtures/ThirdPartyAnnotations.php');
        $this->assertSame('api/3rd-party', $openapi->paths[0]->path);
        $this->assertCount(10, $openapi->_unmerged);
        Analyser::$whitelist = $backup;
        $analysis = $openapi->_analysis;
        $annotations = $analysis->getAnnotationsOfType('Zend\Form\Annotation\Name');
        $this->assertCount(1, $annotations);
        $context = $analysis->getContext($annotations[0]);
        $this->assertInstanceOf('OpenApi\Context', $context);
        $this->assertSame('ThirdPartyAnnotations', $context->class);
        $this->assertSame('\OpenApiFixtures\ThirdPartyAnnotations', $context->fullyQualifiedName($context->class));
        $this->assertCount(2, $context->annotations);
    }

    public function testAnonymousClassProducesNoError()
    {
        try {
            $analyser = new StaticAnalyser(__DIR__.'/Fixtures/php7.php');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail("Analyser produced an error: {$e->getMessage()}");
        }
    }
}
