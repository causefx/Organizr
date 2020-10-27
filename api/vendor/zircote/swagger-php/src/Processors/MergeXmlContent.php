<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Processors;

use OpenApi\Annotations\MediaType;
use OpenApi\Annotations\RequestBody;
use OpenApi\Annotations\Response;
use OpenApi\Analysis;
use OpenApi\Annotations\XmlContent;
use OpenApi\Context;

/**
 * Split XmlContent into Schema and MediaType
 */
class MergeXmlContent
{
    public function __invoke(Analysis $analysis)
    {
        $annotations = $analysis->getAnnotationsOfType(XmlContent::class);
        foreach ($annotations as $xmlContent) {
            $parent = $xmlContent->_context->nested;
            if (!($parent instanceof Response) && !($parent instanceof RequestBody) && !($parent instanceof Parameter)) {
                if ($parent) {
                    Logger::notice('Unexpected '.$xmlContent->identity() .' in ' . $parent->identity() . ' in ' . $this->_context);
                } else {
                    Logger::notice('Unexpected '.$xmlContent->identity() .' must be nested');
                }
                continue;
            }
            if ($parent->content === UNDEFINED) {
                $parent->content = [];
            }
            $parent->content['application/xml'] = new MediaType(
                [
                    'mediaType' => 'application/xml',
                    'schema' => $xmlContent,
                    'example' => $xmlContent->example,
                    'examples' => $xmlContent->examples,
                    '_context' => new Context(['generated' => true], $xmlContent->_context)
                ]
            );
            $xmlContent->example = UNDEFINED;
            $xmlContent->examples = UNDEFINED;

            $index = array_search($xmlContent, $parent->_unmerged, true);
            if ($index !== false) {
                array_splice($parent->_unmerged, $index, 1);
            }
        }
    }
}
