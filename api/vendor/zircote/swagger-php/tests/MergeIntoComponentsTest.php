<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Analysis;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Response;
use OpenApi\Processors\MergeIntoComponents;
use const OpenApi\UNDEFINED;

class MergeIntoComponentsTest extends OpenApiTestCase
{
    public function testProcessor()
    {
        $openapi = new OpenApi([]);
        $response = new Response(['response' => '2xx']);
        $analysis = new Analysis(
            [
                $openapi,
                $response,
            ]
        );
        $this->assertSame(UNDEFINED, $openapi->components);
        $analysis->process(new MergeIntoComponents());
        $this->assertCount(1, $openapi->components->responses);
        $this->assertSame($response, $openapi->components->responses[0]);
        $this->assertCount(0, $analysis->unmerged()->annotations);
    }
}
