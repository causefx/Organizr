<?php declare(strict_types=1);

namespace SchemaQueryParameterProcessor;

use OpenApi\Analysis;

/**
 * Sorts components so they appear in alphabetical order in the generated specs.
 */
class SortComponents
{
    public function __invoke(Analysis $analysis)
    {
        if (is_object($analysis->openapi->components) && is_iterable($analysis->openapi->components->schemas)) {
            usort($analysis->openapi->components->schemas, function ($a, $b) {
                return strcmp($a->schema, $b->schema);
            });
        }
    }
}
