<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analysis;
use OpenApi\Annotations\Response;
use OpenApi\Processors\MergeXmlContent;
use const OpenApi\UNDEFINED;

class MergeXmlContentTest extends OpenApiTestCase
{
    public function testXmlContent()
    {
        $comment = <<<END
        @OA\Response(response=200,
            @OA\XmlContent(type="array",
                @OA\Items(ref="#/components/schemas/repository")
            )
        )
END;
        $analysis = new Analysis($this->parseComment($comment));
        $this->assertCount(3, $analysis->annotations);
        $response = $analysis->getAnnotationsOfType(Response::class)[0];
        $this->assertSame(UNDEFINED, $response->content);
        $this->assertCount(1, $response->_unmerged);
        $analysis->process(new MergeXmlContent());
        $this->assertCount(1, $response->content);
        $this->assertCount(0, $response->_unmerged);
        $json = json_decode(json_encode($response), true);
        $this->assertSame('#/components/schemas/repository', $json['content']['application/xml']['schema']['items']['$ref']);
    }

    public function testMultipleMediaTypes()
    {
        $comment = <<<END
        @OA\Response(response=200,
            @OA\MediaType(mediaType="image/png"),
            @OA\XmlContent(type="array",
                @OA\Items(ref="#/components/schemas/repository")
            )
        )
END;
        $analysis = new Analysis($this->parseComment($comment));
        $response = $analysis->getAnnotationsOfType(Response::class)[0];
        $this->assertCount(1, $response->content);
        $analysis->process(new MergeXmlContent());
        $this->assertCount(2, $response->content);
    }
}
