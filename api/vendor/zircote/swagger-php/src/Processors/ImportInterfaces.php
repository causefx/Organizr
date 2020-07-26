<?php

namespace OpenApi\Processors;

use OpenApi\Analyser;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Definition;
use OpenApi\Annotations\Schema;
use OpenApi\Analysis;
use Traversable;

class ImportInterfaces
{
    public function __invoke(Analysis $analysis)
    {
        $schemas = $analysis->getAnnotationsOfType(Schema::class);
        foreach ($schemas as $schema) {
            $existing = [];
            if ($schema->_context->is('class')) {
                $interfaces = $analysis->getInterfacesOfClass($schema->_context->fullyQualifiedName($schema->_context->class));
                foreach ($interfaces as $interface) {
                    foreach ($interface['properties'] as $property) {
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
}
