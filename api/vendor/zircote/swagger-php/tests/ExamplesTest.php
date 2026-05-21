<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests;

use OpenApi\Generator;

class ExamplesTest extends OpenApiTestCase
{
    public function exampleMappings()
    {
        return [
            'misc' => ['misc', 'misc.yaml'],
            'openapi-spec' => ['openapi-spec', 'openapi-spec.yaml'],
            'petstore.swagger.io' => ['petstore.swagger.io', 'petstore.swagger.io.yaml'],
            'petstore-3.0' => ['petstore-3.0', 'petstore-3.0.yaml'],
            'swagger-spec/petstore' => ['swagger-spec/petstore', 'petstore.yaml'],
            'swagger-spec/petstore-simple' => ['swagger-spec/petstore-simple', 'petstore-simple.yaml'],
            'swagger-spec/petstore-with-external-docs' => ['swagger-spec/petstore-with-external-docs', 'petstore-with-external-docs.yaml'],
            'using-refs' => ['using-refs', 'using-refs.yaml'],
            'example-object' => ['example-object', 'example-object.yaml'],
            'using-interfaces' => ['using-interfaces', 'using-interfaces.yaml'],
            'using-traits' => ['using-traits', 'using-traits.yaml'],
            'nesting' => ['nesting', 'nesting.yaml'],
        ];
    }

    /**
     * Validate openapi definitions of the included examples.
     *
     * @dataProvider exampleMappings
     */
    public function testExamples($example, $spec)
    {
        $path = __DIR__ . '/../Examples/' . $example;
        $openapi = Generator::scan([$path], ['validate' => true]);
        //file_put_contents($path . '/' . $spec, $openapi->toYaml());
        $this->assertSpecEquals(file_get_contents($path . '/' . $spec), $openapi, 'Examples/' . $example . '/' . $spec);
    }
}
