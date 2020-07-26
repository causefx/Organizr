<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

class AbstractAnnotationTest extends OpenApiTestCase
{
    public function testVendorFields()
    {
        $annotations = $this->parseComment('@OA\Get(x={"internal-id": 123})');
        $output = $annotations[0]->jsonSerialize();
        $prefixedProperty = 'x-internal-id';
        $this->assertSame(123, $output->$prefixedProperty);
    }

    public function testInvalidField()
    {
        $this->assertOpenApiLogEntryStartsWith('Unexpected field "doesnot" for @OA\Get(), expecting');
        $this->parseComment('@OA\Get(doesnot="exist")');
    }

    public function testUmergedAnnotation()
    {
        $openapi = $this->createOpenApiWithInfo();
        $openapi->merge($this->parseComment('@OA\Items()'));
        $this->assertOpenApiLogEntryStartsWith('Unexpected @OA\Items(), expected to be inside @OA\\');
        $openapi->validate();
    }

    public function testConflictedNesting()
    {
        $comment = <<<END
@OA\Info(
    title="Info only has one contact field..",
    version="test",
    @OA\Contact(name="first"),
    @OA\Contact(name="second")
)
END;
        $annotations = $this->parseComment($comment);
        $this->assertOpenApiLogEntryStartsWith('Only one @OA\Contact() allowed for @OA\Info() multiple found in:');
        $annotations[0]->validate();
    }

    public function testKey()
    {
        $comment = <<<END
@OA\Response(
    @OA\Header(header="X-CSRF-Token",description="Token to prevent Cross Site Request Forgery")
)
END;
        $annotations = $this->parseComment($comment);
        $this->assertEquals('{"headers":{"X-CSRF-Token":{"description":"Token to prevent Cross Site Request Forgery"}}}', json_encode($annotations[0]));
    }

    public function testConflictingKey()
    {
        $comment = <<<END
@OA\Response(
    description="The headers in response must have unique header values",
    @OA\Header(header="X-CSRF-Token", @OA\Schema(type="string"), description="first"),
    @OA\Header(header="X-CSRF-Token", @OA\Schema(type="string"), description="second")
)
END;
        $annotations = $this->parseComment($comment);
        $this->assertOpenApiLogEntryStartsWith('Multiple @OA\Header() with the same header="X-CSRF-Token":');
        $annotations[0]->validate();
    }

    public function testRequiredFields()
    {
        $annotations = $this->parseComment('@OA\Info()');
        $info = $annotations[0];
        $this->assertOpenApiLogEntryStartsWith('Missing required field "title" for @OA\Info() in ');
        $this->assertOpenApiLogEntryStartsWith('Missing required field "version" for @OA\Info() in ');
        $info->validate();
    }

    public function testTypeValidation()
    {
        $comment = <<<END
@OA\Parameter(
    name=123,
    in="dunno",
    required="maybe",
    @OA\Schema(
      type="strig",
    )
)
END;
        $annotations = $this->parseComment($comment);
        $parameter = $annotations[0];
        $this->assertOpenApiLogEntryStartsWith('@OA\Parameter(name=123,in="dunno")->name is a "integer", expecting a "string" in ');
        $this->assertOpenApiLogEntryStartsWith('@OA\Parameter(name=123,in="dunno")->in "dunno" is invalid, expecting "query", "header", "path", "cookie" in ');
        $this->assertOpenApiLogEntryStartsWith('@OA\Parameter(name=123,in="dunno")->required is a "string", expecting a "boolean" in ');
//        $this->assertOpenApiLogEntryStartsWith('@OA\Parameter(name=123,in="dunno")->maximum is a "string", expecting a "number" in ');
//        $this->assertOpenApiLogEntryStartsWith('@OA\Parameter(name=123,in="dunno")->type must be "string", "number", "integer", "boolean", "array", "file" when @OA\Parameter()->in != "body" in ');
        $parameter->validate();
    }
}
