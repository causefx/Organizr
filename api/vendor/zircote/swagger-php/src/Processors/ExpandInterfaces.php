<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;

/**
 * Look at all (direct) interfaces for a schema and:
 * - merge interfaces annotations/methods into the schema if the interface does not have a schema itself
 * - inherit from the interface if it has a schema (allOf).
 */
class ExpandInterfaces
{
    use MergeTrait;

    public function __invoke(Analysis $analysis)
    {
        /** @var Schema[] $schemas */
        $schemas = $analysis->getAnnotationsOfType(Schema::class, true);

        foreach ($schemas as $schema) {
            if ($schema->_context->is('class')) {
                $interfaces = $analysis->getInterfacesOfClass($schema->_context->fullyQualifiedName($schema->_context->class), true);
                $existing = [];
                foreach ($interfaces as $interface) {
                    $interfaceSchema = $analysis->getSchemaForSource($interface['context']->fullyQualifiedName($interface['interface']));
                    if ($interfaceSchema) {
                        $refPath = $interfaceSchema->schema !== Generator::UNDEFINED ? $interfaceSchema->schema : $interface['interface'];
                        $this->inheritFrom($schema, $interfaceSchema, $refPath, $interface['context']);
                    } else {
                        $this->mergeAnnotations($schema, $interface, $existing);
                        $this->mergeMethods($schema, $interface, $existing);
                    }
                }
            }
        }
    }
}
