<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

class ExamplesTest extends OpenApiTestCase
{

    /**
     * Test the processed Examples against json files in ExamplesOutput.
     *
     * @dataProvider getExamples
     *
     * @param string $example Example path
     * @param string $output  Expected output (path to a json file)
     */
    public function testExample($example, $output)
    {
        $openapi = \OpenApi\scan(__DIR__.'/../Examples/'.$example);
        $this->assertOpenApiEqualsFile(__DIR__.'/ExamplesOutput/'.$output, $openapi);
    }

    /**
     * dataProvider for testExample
     *
     * @return array
     */
    public function getExamples()
    {
        return [
            ['misc', 'misc.json'],
            ['openapi-spec', 'openapi-spec.json'],
            ['petstore.swagger.io', 'petstore.swagger.io.json'],
            ['petstore-3.0', 'petstore-3.0.json'],
            ['swagger-spec/petstore', 'petstore.json'],
            ['swagger-spec/petstore-simple', 'petstore-simple.json'],
            ['swagger-spec/petstore-with-external-docs', 'petstore-with-external-docs.json'],
            ['using-refs', 'using-refs.json'],
            ['example-object', 'example-object.json'],
        ];
    }
}
