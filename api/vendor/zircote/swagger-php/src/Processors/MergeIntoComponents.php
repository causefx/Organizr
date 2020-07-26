<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Annotations\Components;
use OpenApi\Analysis;
use OpenApi\Context;
use OpenApi\UNDEFINED;

/**
 * Merge reusable annotation into @OA\Schemas
 */
class MergeIntoComponents
{
    public function __invoke(Analysis $analysis)
    {
        $components = $analysis->openapi->components;
        if ($components === UNDEFINED) {
            $components = new Components([]);
            $components->_context->generated = true;
        }
        $classes = array_keys(Components::$_nested);
        foreach ($analysis->annotations as $annotation) {
            $class = get_class($annotation);
            if (in_array($class, $classes) && $annotation->_context->is('nested') === false) { // A top level annotation.
                $components->merge([$annotation], true);
                $analysis->openapi->components = $components;
            }
        }
    }
}
