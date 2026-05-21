<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\MediaType;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\RequestBody;
use OpenApi\Annotations\Response;
use OpenApi\Annotations\XmlContent;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * Split XmlContent into Schema and MediaType.
 */
class MergeXmlContent
{
    public function __invoke(Analysis $analysis)
    {
        /** @var XmlContent[] $annotations */
        $annotations = $analysis->getAnnotationsOfType(XmlContent::class);

        foreach ($annotations as $xmlContent) {
            $parent = $xmlContent->_context->nested;
            if (!($parent instanceof Response) && !($parent instanceof RequestBody) && !($parent instanceof Parameter)) {
                if ($parent) {
                    $xmlContent->_context->logger->warning('Unexpected ' . $xmlContent->identity() . ' in ' . $parent->identity() . ' in ' . $parent->_context);
                } else {
                    $xmlContent->_context->logger->warning('Unexpected ' . $xmlContent->identity() . ' must be nested');
                }
                continue;
            }
            if ($parent->content === Generator::UNDEFINED) {
                $parent->content = [];
            }
            $parent->content['application/xml'] = new MediaType([
                'schema' => $xmlContent,
                'example' => $xmlContent->example,
                'examples' => $xmlContent->examples,
                '_context' => new Context(['generated' => true], $xmlContent->_context),
            ]);
            if (!$parent instanceof Parameter) {
                $parent->content['application/xml']->mediaType = 'application/xml';
            }
            $xmlContent->example = Generator::UNDEFINED;
            $xmlContent->examples = Generator::UNDEFINED;

            $index = array_search($xmlContent, $parent->_unmerged, true);
            if ($index !== false) {
                array_splice($parent->_unmerged, $index, 1);
            }
        }
    }
}
