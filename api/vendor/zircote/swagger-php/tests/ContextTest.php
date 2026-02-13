<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests;

use OpenApi\Context;
use OpenApi\Generator;

class ContextTest extends OpenApiTestCase
{
    public function testDetect()
    {
        $context = Context::detect();
        $line = __LINE__ - 1;
        $this->assertSame('ContextTest', $context->class);
        $this->assertSame('\OpenApi\Tests\ContextTest', $context->fullyQualifiedName($context->class));
        $this->assertSame('testDetect', $context->method);
        $this->assertSame(__FILE__, $context->filename);
        $this->assertSame($line, $context->line);
        $this->assertSame('OpenApi\Tests', $context->namespace);
    }

    public function testFullyQualifiedName()
    {
        $this->assertOpenApiLogEntryContains('Required @OA\PathItem() not found');
        $openapi = Generator::scan([__DIR__ . '/Fixtures/Customer.php'], ['logger' => $this->getTrackingLogger()]);
        $context = $openapi->components->schemas[0]->_context;
        // resolve with namespace
        $this->assertSame('\FullyQualified', $context->fullyQualifiedName('\FullyQualified'));
        $this->assertSame('\OpenApi\Tests\Fixtures\Unqualified', $context->fullyQualifiedName('Unqualified'));
        $this->assertSame('\OpenApi\Tests\Fixtures\Namespace\Qualified', $context->fullyQualifiedName('Namespace\Qualified'));
        // respect use statements
        $this->assertSame('\Exception', $context->fullyQualifiedName('Exception'));
        $this->assertSame('\OpenApi\Tests\Fixtures\Customer', $context->fullyQualifiedName('Customer'));
        $this->assertSame('\OpenApi\Generator', $context->fullyQualifiedName('Generator'));
        $this->assertSame('\OpenApi\Generator', $context->fullyQualifiedName('gEnerator')); // php has case-insensitive class names :-(
        $this->assertSame('\OpenApi\Generator', $context->fullyQualifiedName('OpenApiGenerator'));
        $this->assertSame('\OpenApi\Annotations\QualifiedAlias', $context->fullyQualifiedName('OA\QualifiedAlias'));
    }

    public function testPhpdocContent()
    {
        $singleLine = $this->getContext(['comment' => <<<END
    /**
     * A single line.
     *
     * @OA\Get(path="api/test1", @OA\Response(response="200", description="a response"))
     */
END
        ]);
        $this->assertEquals('A single line.', $singleLine->phpdocContent());

        $multiline = $this->getContext(['comment' => <<<END
/**
 * A description spread across
 * multiple lines.
 *
 * even blank lines
 *
 * @OA\Get(path="api/test1", @OA\Response(response="200", description="a response"))
 */
END
        ]);
        $this->assertEquals("A description spread across\nmultiple lines.\n\neven blank lines", $multiline->phpdocContent());

        $escapedLinebreak = $this->getContext(['comment' => <<<END
/**
 * A single line spread across \
 * multiple lines.
 *
 * @OA\Get(path="api/test1", @OA\Response(response="200", description="a response"))
 */
END
        ]);
        $this->assertEquals('A single line spread across multiple lines.', $escapedLinebreak->phpdocContent());
    }

    /**
     * https://phpdoc.org/docs/latest/guides/docblocks.html.
     */
    public function testPhpdocSummaryAndDescription()
    {
        $single = $this->getContext(['comment' => '/** This is a single line DocComment. */']);
        $this->assertEquals('This is a single line DocComment.', $single->phpdocContent());
        $multi = $this->getContext(['comment' => "/**\n * This is a multi-line DocComment.\n */"]);
        $this->assertEquals('This is a multi-line DocComment.', $multi->phpdocContent());

        $emptyWhiteline = $this->getContext(['comment' => <<<END
    /**
     * This is a summary
     *
     * This is a description
     */
END
        ]);
        $this->assertEquals('This is a summary', $emptyWhiteline->phpdocSummary());
        $periodNewline = $this->getContext(['comment' => <<<END
     /**
     * This is a summary.
     * This is a description
     */
END
        ]);
        $this->assertEquals('This is a summary.', $periodNewline->phpdocSummary());
        $multilineSummary = $this->getContext(['comment' => <<<END
     /**
     * This is a summary
     * but this is part of the summary
     */
END
        ]);
    }
}
