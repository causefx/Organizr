<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Components;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

/**
 * Use the Schema context to extract useful information and inject that into the annotation.
 *
 * Merges properties.
 */
class AugmentSchemas
{
    public function __invoke(Analysis $analysis)
    {
        /** @var Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(Schema::class);

        // Use the class names for @OA\Schema()
        foreach ($schemas as $schema) {
            if ($schema->schema === Generator::UNDEFINED) {
                if ($schema->_context->is('class')) {
                    $schema->schema = $schema->_context->class;
                } elseif ($schema->_context->is('interface')) {
                    $schema->schema = $schema->_context->interface;
                } elseif ($schema->_context->is('trait')) {
                    $schema->schema = $schema->_context->trait;
                }
            }
        }

        // Merge unmerged @OA\Property annotations into the @OA\Schema of the class
        $unmergedProperties = $analysis->unmerged()->getAnnotationsOfType(Property::class);
        foreach ($unmergedProperties as $property) {
            if ($property->_context->nested) {
                continue;
            }
            $schemaContext = $property->_context->with('class') ?: $property->_context->with('trait') ?: $property->_context->with('interface');
            if ($schemaContext->annotations) {
                foreach ($schemaContext->annotations as $annotation) {
                    if ($annotation instanceof Schema) {
                        if ($annotation->_context->nested) {
                            // we shouldn't merge property into nested schemas
                            continue;
                        }

                        if ($annotation->allOf !== Generator::UNDEFINED) {
                            $schema = null;
                            foreach ($annotation->allOf as $nestedSchema) {
                                if ($nestedSchema->ref !== Generator::UNDEFINED) {
                                    continue;
                                }

                                $schema = $nestedSchema;
                            }

                            if ($schema === null) {
                                $schema = new Schema(['_context' => $annotation->_context]);
                                $annotation->allOf[] = $schema;
                            }

                            $schema->merge([$property], true);
                            break;
                        }

                        $annotation->merge([$property], true);
                        break;
                    }
                }
            }
        }

        // set schema type based on various properties
        foreach ($schemas as $schema) {
            if ($schema->type === Generator::UNDEFINED) {
                if (is_array($schema->properties) && count($schema->properties) > 0) {
                    $schema->type = 'object';
                } elseif (is_array($schema->additionalProperties) && count($schema->additionalProperties) > 0) {
                    $schema->type = 'object';
                } elseif (is_array($schema->patternProperties) && count($schema->patternProperties) > 0) {
                    $schema->type = 'object';
                } elseif (is_array($schema->propertyNames) && count($schema->propertyNames) > 0) {
                    $schema->type = 'object';
                }
            }
        }

        // move schema properties into allOf if both exist
        foreach ($schemas as $schema) {
            if ($schema->properties !== Generator::UNDEFINED and $schema->allOf !== Generator::UNDEFINED) {
                $allOfPropertiesSchema = null;
                foreach ($schema->allOf as $allOfSchema) {
                    if ($allOfSchema->properties !== Generator::UNDEFINED) {
                        $allOfPropertiesSchema = $allOfSchema;
                        break;
                    }
                }
                if (!$allOfPropertiesSchema) {
                    $allOfPropertiesSchema = new Schema(['_context' => $schema->_context, 'properties' => []]);
                    $schema->allOf[] = $allOfPropertiesSchema;
                }
                $allOfPropertiesSchema->properties = array_merge($allOfPropertiesSchema->properties, $schema->properties);
                $schema->properties = Generator::UNDEFINED;
            }
        }

        // ref rewriting
        $updatedRefs = [];
        foreach ($schemas as $schema) {
            if ($schema->allOf !== Generator::UNDEFINED) {
                // do we have to keep track of properties refs that need updating?
                foreach ($schema->allOf as $allOfSchema) {
                    if ($allOfSchema->properties !== Generator::UNDEFINED) {
                        $updatedRefs[Components::SCHEMA_REF . $schema->schema . '/properties'] = Components::SCHEMA_REF . $schema->schema . '/allOf/0/properties';
                        break;
                    }
                }

                // rewriting is simpler if properties is first...
                usort($schema->allOf, function ($a, $b) {
                    return $a->properties !== Generator::UNDEFINED ? -1 : ($b->properties !== Generator::UNDEFINED ? 1 : 0);
                });
            }
        }

        if ($updatedRefs) {
            foreach ($analysis->annotations as $annotation) {
                if (property_exists($annotation, 'ref') && $annotation->ref !== Generator::UNDEFINED && $annotation->ref !== null) {
                    foreach ($updatedRefs as $origRef => $updatedRef) {
                        if (0 === strpos($annotation->ref, $origRef)) {
                            $annotation->ref = str_replace($origRef, $updatedRef, $annotation->ref);
                        }
                    }
                }
            }
        }
    }
}
