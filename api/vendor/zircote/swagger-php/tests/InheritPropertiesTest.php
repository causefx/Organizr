<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Annotations\Components;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Schema;
use OpenApi\Processors\AugmentProperties;
use OpenApi\Processors\AugmentSchemas;
use OpenApi\Processors\InheritProperties;
use OpenApi\Processors\MergeIntoComponents;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\StaticAnalyser;
use const OpenApi\UNDEFINED;

class InheritPropertiesTest extends OpenApiTestCase
{
    public function testInheritProperties()
    {
        $analyser = new StaticAnalyser();
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Child.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/GrandAncestor.php'));
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/Ancestor.php'));
        $analysis->process(
            [
            new MergeIntoOpenApi(),
            new AugmentSchemas(),
            new AugmentProperties(),
            ]
        );
        $schemas = $analysis->getAnnotationsOfType(Schema::class);
        $childSchema = $schemas[0];
        $this->assertSame('Child', $childSchema->schema);
        $this->assertCount(1, $childSchema->properties);
        $analysis->process(new InheritProperties());
        $this->assertCount(3, $childSchema->properties);

        $analysis->openapi->info = new Info(['title' => 'test', 'version' => '1.0.0']);
        $analysis->openapi->paths = [new PathItem(['path' => '/test'])];
        $analysis->validate();
    }

    /**
     * Tests, if InheritProperties works even without any
     * docBlocks at all in the parent class.
     */
    public function testInheritPropertiesWithoutDocBlocks()
    {
        $analyser = new StaticAnalyser();

        // this class has docblocks
        $analysis = $analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/ChildWithDocBlocks.php');
        // this one doesn't
        $analysis->addAnalysis($analyser->fromFile(__DIR__.'/Fixtures/InheritProperties/AncestorWithoutDocBlocks.php'));

        $analysis->process(
            [
            new MergeIntoOpenApi(),
            new AugmentSchemas(),
            new AugmentProperties(),
            ]
        );
        $schemas = $analysis->getAnnotationsOfType(Schema::class);
        $childSchema = $schemas[0];
        $this->assertSame('ChildWithDocBlocks', $childSchema->schema);
        $this->assertCount(1, $childSchema->properties);

        // no error occurs
        $analysis->process(new InheritProperties());
        $this->assertCount(1, $childSchema->properties);

        $analysis->openapi->info = new Info(['title' => 'test', 'version' => '1.0.0']);
        $analysis->openapi->paths = [new PathItem(['path' => '/test'])];
        $analysis->validate();
    }

    /**
     * Tests inherit properties with all of block
     */
    public function testInheritPropertiesWithAllOf()
    {
        $analyser = new StaticAnalyser();
        // this class has all of
        $analysis = $analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/Extended.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/Base.php'));

        $analysis->process(
            [
                new MergeIntoOpenApi(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new MergeIntoComponents(),
                new InheritProperties()
            ]
        );

        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);
        $this->assertCount(3, $schemas);

        /* @var Schema $extendedSchema */
        $extendedSchema = $schemas[0];
        $this->assertSame('ExtendedModel', $extendedSchema->schema);
        $this->assertSame(UNDEFINED, $extendedSchema->properties);

        $this->assertArrayHasKey(1, $extendedSchema->allOf);
        $this->assertEquals($extendedSchema->allOf[1]->properties[0]->property, 'extendedProperty');

        /* @var $includeSchemaWithRef Schema */
        $includeSchemaWithRef = $schemas[1];
        $this->assertSame(UNDEFINED, $includeSchemaWithRef->properties);

        $analysis->openapi->info = new Info(['title' => 'test', 'version' => "1.0.0"]);
        $analysis->openapi->paths = [new PathItem(['path' => '/test'])];
        $analysis->validate();
    }

    /**
     * Tests for inherit properties without all of block
     */
    public function testInheritPropertiesWithOtAllOf()
    {
        $analyser = new StaticAnalyser();
        // this class has all of
        $analysis = $analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/ExtendedWithoutAllOf.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/Base.php'));

        $analysis->process(
            [
                new MergeIntoOpenApi(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new MergeIntoComponents(),
                new InheritProperties()
            ]
        );

        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);
        $this->assertCount(2, $schemas);

        /* @var Schema $extendedSchema */
        $extendedSchema = $schemas[0];
        $this->assertSame('ExtendedWithoutAllOf', $extendedSchema->schema);
        $this->assertSame(UNDEFINED, $extendedSchema->properties);

        $this->assertCount(2, $extendedSchema->allOf);

        $this->assertEquals($extendedSchema->allOf[0]->ref, Components::SCHEMA_REF . 'Base');
        $this->assertEquals($extendedSchema->allOf[1]->properties[0]->property, 'extendedProperty');

        $analysis->openapi->info = new Info(['title' => 'test', 'version' => '1.0.0']);
        $analysis->openapi->paths = [new PathItem(['path' => '/test'])];
        $analysis->validate();
    }

    /**
     * Tests for inherit properties in object with two schemas in the same context
     */
    public function testInheritPropertiesWitTwoChildSchemas()
    {
        $analyser = new StaticAnalyser();
        // this class has all of
        $analysis = $analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/ExtendedWithTwoSchemas.php');
        $analysis->addAnalysis($analyser->fromFile(__DIR__ . '/Fixtures/InheritProperties/Base.php'));

        $analysis->process(
            [
                new MergeIntoOpenApi(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new MergeIntoComponents(),
                new InheritProperties()
            ]
        );

        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);
        $this->assertCount(3, $schemas);

        /* @var Schema $extendedSchema */
        $extendedSchema = $schemas[0];
        $this->assertSame('ExtendedWithTwoSchemas', $extendedSchema->schema);
        $this->assertSame(UNDEFINED, $extendedSchema->properties);

        $this->assertCount(2, $extendedSchema->allOf);
        $this->assertEquals($extendedSchema->allOf[0]->ref, Components::SCHEMA_REF . 'Base');
        $this->assertEquals($extendedSchema->allOf[1]->properties[0]->property, 'nested');
        $this->assertEquals($extendedSchema->allOf[1]->properties[1]->property, 'extendedProperty');

        /* @var  $nestedSchema Schema */
        $nestedSchema = $schemas[1];
        $this->assertSame(UNDEFINED, $nestedSchema->allOf);
        $this->assertCount(1, $nestedSchema->properties);
        $this->assertEquals($nestedSchema->properties[0]->property, 'nestedProperty');

        $analysis->openapi->info = new Info(['title' => 'test', 'version' => '1.0.0']);
        $analysis->openapi->paths = [new PathItem(['path' => '/test'])];
        $analysis->validate();
    }
}
