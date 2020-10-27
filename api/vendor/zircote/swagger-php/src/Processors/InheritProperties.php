<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Annotations\Components;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Analysis;
use Traversable;

/**
 * Copy the annotated properties from parent classes;
 */
class InheritProperties
{
    public function __invoke(Analysis $analysis)
    {
        /* @var  $schemas Schema[] */
        $schemas = $analysis->getAnnotationsOfType(Schema::class);

        $processedSchemas = [];

        foreach ($schemas as $schema) {
            if ($schema->_context->is('class')) {
                if (in_array($schema->_context, $processedSchemas, true)) {
                    //We should process only first schema in the same context
                    continue;
                }

                $processedSchemas[] = $schema->_context;

                if ($schema->allOf !== UNDEFINED) {
                    //if the allOf in the child is set, do noting
                    continue;
                }

                $existing = [];

                if (is_array($schema->properties) || $schema->properties instanceof Traversable) {
                    foreach ($schema->properties as $property) {
                        if ($property->property) {
                            $existing[] = $property->property;
                        }
                    }
                }
                $classes = $analysis->getSuperClasses($schema->_context->fullyQualifiedName($schema->_context->class));
                foreach ($classes as $class) {
                    if ($class['context']->annotations) {
                        foreach ($class['context']->annotations as $annotation) {
                            if ($annotation instanceof Schema && $annotation->schema !== UNDEFINED) {
                                $this->addAllOfProperty($schema, $annotation);

                                continue 2;
                            }
                        }
                    }

                    foreach ($class['properties'] as $property) {
                        if (is_array($property->annotations) === false && !($property->annotations instanceof Traversable)) {
                            continue;
                        }
                        foreach ($property->annotations as $annotation) {
                            if ($annotation instanceof Property && in_array($annotation->property, $existing) === false) {
                                $existing[] = $annotation->property;
                                $schema->merge([$annotation], true);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Add to child schema allOf property
     *
     * @param \OpenApi\Annotations\Schema $childSchema
     * @param \OpenApi\Annotations\Schema $parentSchema
     */
    private function addAllOfProperty(Schema $childSchema, Schema $parentSchema)
    {
        $currentSchema = new Schema(['_context' => $childSchema->_context]);

        $currentSchema->mergeProperties($childSchema);

        $defaultValues = get_class_vars(Schema::class);

        foreach (get_object_vars($currentSchema) as $property => $val) {
            $childSchema->{$property} = $defaultValues[$property];
        }

        $childSchema->schema = $currentSchema->schema;
        unset($currentSchema->schema);
        if ($childSchema->allOf === UNDEFINED) {
            $childSchema->allOf = [];
        }
        $childSchema->allOf[] = new Schema(
            [
            '_context' => $parentSchema->_context,
            'ref' => Components::SCHEMA_REF . $parentSchema->schema
            ]
        );
        $childSchema->allOf[] = $currentSchema;
    }
}
