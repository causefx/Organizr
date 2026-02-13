<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\OpenApi;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * Merge all @OA\OpenApi annotations into one.
 */
class MergeIntoOpenApi
{
    public function __invoke(Analysis $analysis)
    {
        // Auto-create the OpenApi annotation.
        if (!$analysis->openapi) {
            $context = new Context([], $analysis->context);
            $analysis->addAnnotation(new OpenApi(['_context' => $context]), $context);
        }
        $openapi = $analysis->openapi;
        $openapi->_analysis = $analysis;

        // Merge annotations into the target openapi
        $merge = [];
        /** @var AbstractAnnotation $annotation */
        foreach ($analysis->annotations as $annotation) {
            if ($annotation === $openapi) {
                continue;
            }
            if ($annotation instanceof OpenApi) {
                $paths = $annotation->paths;
                unset($annotation->paths);
                $openapi->mergeProperties($annotation);
                if ($paths !== Generator::UNDEFINED) {
                    foreach ($paths as $path) {
                        if ($openapi->paths === Generator::UNDEFINED) {
                            $openapi->paths = [];
                        }
                        $openapi->paths[] = $path;
                    }
                }
            } elseif (OpenApi::matchNested(get_class($annotation)) && property_exists($annotation, '_context') && $annotation->_context->is('nested') === false) {
                // A top level annotation.
                $merge[] = $annotation;
            }
        }
        $openapi->merge($merge, true);
    }
}
