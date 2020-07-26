<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analysis;
use OpenApi\Annotations\Response;
use OpenApi\Processors\MergeJsonContent;
use const OpenApi\UNDEFINED;

class MergeJsonContentTest extends OpenApiTestCase
{
    public function testJsonContent()
    {
        $comment = <<<END
        @OA\Response(response=200,
            @OA\JsonContent(type="array",
                @OA\Items(ref="#/components/schemas/repository")
            )
        )
END;
        $analysis = new Analysis($this->parseComment($comment));
        $this->assertCount(3, $analysis->annotations);
        $response = $analysis->getAnnotationsOfType(Response::class)[0];
        $this->assertSame(UNDEFINED, $response->content);
        $this->assertCount(1, $response->_unmerged);
        $analysis->process(new MergeJsonContent());
        $this->assertCount(1, $response->content);
        $this->assertCount(0, $response->_unmerged);
        $json = json_decode(json_encode($response), true);
        $this->assertSame('#/components/schemas/repository', $json['content']['application/json']['schema']['items']['$ref']);
    }

    public function testMultipleMediaTypes()
    {
        $comment = <<<END
        @OA\Response(response=200,
            @OA\MediaType(mediaType="image/png"),
            @OA\JsonContent(type="array",
                @OA\Items(ref="#/components/schemas/repository")
            )
        )
END;
        $analysis = new Analysis($this->parseComment($comment));
        $response = $analysis->getAnnotationsOfType(Response::class)[0];
        $this->assertCount(1, $response->content);
        $analysis->process(new MergeJsonContent());
        $this->assertCount(2, $response->content);
    }
}
