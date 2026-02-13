<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests\Processors;

use OpenApi\Analysis;
use OpenApi\Annotations\Get;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\PathItem;
use OpenApi\Annotations\Post;
use OpenApi\Generator;
use OpenApi\Processors\BuildPaths;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\Tests\OpenApiTestCase;

class BuildPathsTest extends OpenApiTestCase
{
    public function testMergePathsWithSamePath()
    {
        $openapi = new OpenApi(['_context' => $this->getContext()]);
        $openapi->paths = [
            new PathItem(['path' => '/comments', '_context' => $this->getContext()]),
            new PathItem(['path' => '/comments', '_context' => $this->getContext()]),
        ];
        $analysis = new Analysis([$openapi], $this->getContext());
        $analysis->openapi = $openapi;
        $analysis->process(new BuildPaths());
        $this->assertCount(1, $openapi->paths);
        $this->assertSame('/comments', $openapi->paths[0]->path);
    }

    public function testMergeOperationsWithSamePath()
    {
        $openapi = new OpenApi(['_context' => $this->getContext()]);
        $analysis = new Analysis(
            [
                $openapi,
                new Get(['path' => '/comments', '_context' => $this->getContext()]),
                new Post(['path' => '/comments', '_context' => $this->getContext()]),
            ],
            $this->getContext()
        );
        $analysis->process(new MergeIntoOpenApi());
        $analysis->process(new BuildPaths());
        $this->assertCount(1, $openapi->paths);
        $path = $openapi->paths[0];
        $this->assertSame('/comments', $path->path);
        $this->assertInstanceOf(PathItem::class, $path);
        $this->assertInstanceOf(Get::class, $path->get);
        $this->assertInstanceOf(Post::class, $path->post);
        $this->assertSame(Generator::UNDEFINED, $path->put);
    }
}
