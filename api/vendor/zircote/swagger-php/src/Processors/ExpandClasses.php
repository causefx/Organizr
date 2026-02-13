<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

/**
 * Iterate over the chain of anchestors of a schema and:
 * - merge anchestor annotations/methods/properties into the schema if the anchestor doesn't have a schema itself
 * - inherit from the anchestor if it has a schema (allOf) and stop.
 */
class ExpandClasses
{
    use MergeTrait;

    public function __invoke(Analysis $analysis)
    {
        /** @var Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);

        foreach ($schemas as $schema) {
            if ($schema->_context->is('class')) {
                $anchestors = $analysis->getSuperClasses($schema->_context->fullyQualifiedName($schema->_context->class));
                $existing = [];
                foreach ($anchestors as $anchestor) {
                    $anchestorSchema = $analysis->getSchemaForSource($anchestor['context']->fullyQualifiedName($anchestor['class']));
                    if ($anchestorSchema) {
                        $refPath = $anchestorSchema->schema !== Generator::UNDEFINED ? $anchestorSchema->schema : $anchestor['class'];
                        $this->inheritFrom($schema, $anchestorSchema, $refPath, $anchestor['context']);

                        // one anchestor is enough
                        break;
                    } else {
                        $this->mergeAnnotations($schema, $anchestor, $existing);
                        $this->mergeMethods($schema, $anchestor, $existing);
                        $this->mergeProperties($schema, $anchestor, $existing);
                    }
                }
            }
        }
    }
}
