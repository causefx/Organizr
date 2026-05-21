<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApi\Tests\Processors;

use OpenApi\Annotations\Property;
use OpenApi\Generator;
use OpenApi\Processors\AugmentProperties;
use OpenApi\Processors\AugmentSchemas;
use OpenApi\Processors\MergeIntoComponents;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\Tests\OpenApiTestCase;

/**
 * @group Properties
 */
class AugmentPropertiesTest extends OpenApiTestCase
{
    public function testAugmentProperties()
    {
        $analysis = $this->analysisFromFixtures('Customer.php');
        $analysis->process(new MergeIntoOpenApi());
        $analysis->process(new MergeIntoComponents());
        $analysis->process(new AugmentSchemas());
        $customer = $analysis->openapi->components->schemas[0];
        $firstName = $customer->properties[0];
        $secondName = $customer->properties[1];
        $thirdName = $customer->properties[2];
        $fourthName = $customer->properties[3];
        $lastName = $customer->properties[4];
        $tags = $customer->properties[5];
        $submittedBy = $customer->properties[6];
        $friends = $customer->properties[7];
        $bestFriend = $customer->properties[8];
        $endorsedFriends = $customer->properties[9];

        // Verify no values where defined in the annotation.
        $this->assertSame(Generator::UNDEFINED, $firstName->property);
        $this->assertSame(Generator::UNDEFINED, $firstName->description);
        $this->assertSame(Generator::UNDEFINED, $firstName->type);

        $this->assertSame(Generator::UNDEFINED, $lastName->property);
        $this->assertSame(Generator::UNDEFINED, $lastName->description);
        $this->assertSame(Generator::UNDEFINED, $lastName->type);

        $this->assertSame(Generator::UNDEFINED, $tags->property);
        $this->assertSame(Generator::UNDEFINED, $tags->type);
        $this->assertSame(Generator::UNDEFINED, $tags->items);

        $this->assertSame(Generator::UNDEFINED, $submittedBy->property);
        $this->assertSame(Generator::UNDEFINED, $submittedBy->ref);

        $this->assertSame(Generator::UNDEFINED, $friends->property);
        $this->assertSame(Generator::UNDEFINED, $friends->type);

        $this->assertSame(Generator::UNDEFINED, $bestFriend->property);
        $this->assertSame(Generator::UNDEFINED, $bestFriend->nullable);
        $this->assertSame(Generator::UNDEFINED, $bestFriend->allOf);

        $this->assertSame(Generator::UNDEFINED, $endorsedFriends->property);
        $this->assertSame(Generator::UNDEFINED, $endorsedFriends->nullable);
        $this->assertSame(Generator::UNDEFINED, $endorsedFriends->allOf);

        $analysis->process(new AugmentProperties());

        $expectedValues = [
            'property' => 'firstname',
            'example' => 'John',
            'description' => 'The first name of the customer.',
            'type' => 'string',
        ];
        $this->assertName($firstName, $expectedValues);

        $expectedValues = [
            'property' => 'secondname',
            'example' => 'Allan',
            'description' => 'The second name of the customer.',
            'type' => 'string',
            'nullable' => true,
        ];
        $this->assertName($secondName, $expectedValues);

        $expectedValues = [
            'property' => 'thirdname',
            'example' => 'Peter',
            'description' => 'The third name of the customer.',
            'type' => 'string',
            'nullable' => true,
        ];
        $this->assertName($thirdName, $expectedValues);

        $expectedValues = [
            'property' => 'fourthname',
            'example' => 'Unknown',
            'description' => 'The unknown name of the customer.',
            'type' => Generator::UNDEFINED,
            'nullable' => true,
        ];
        $this->assertName($fourthName, $expectedValues);

        $expectedValues = [
            'property' => 'lastname',
            'example' => Generator::UNDEFINED,
            'description' => 'The lastname of the customer.',
            'type' => 'string',
        ];
        $this->assertName($lastName, $expectedValues);

        $this->assertSame('tags', $tags->property);
        $this->assertSame('array', $tags->type, 'Detect array notation: @var string[]');
        $this->assertSame('string', $tags->items->type);

        $this->assertSame('submittedBy', $submittedBy->property);
        $this->assertSame('#/components/schemas/Customer', $submittedBy->ref);

        $this->assertSame('friends', $friends->property);
        $this->assertSame('array', $friends->type);
        $this->assertSame('#/components/schemas/Customer', $friends->items->ref);

        $this->assertSame('bestFriend', $bestFriend->property);
        $this->assertTrue($bestFriend->nullable);
        $this->assertSame('#/components/schemas/Customer', $bestFriend->oneOf[0]->ref);

        $this->assertSame('endorsedFriends', $endorsedFriends->property);
        $this->assertSame('array', $endorsedFriends->type);
        $this->assertTrue($endorsedFriends->nullable);
        $this->assertSame('#/components/schemas/Customer', $endorsedFriends->items->ref);
    }

    public function testTypedProperties()
    {
        $analysis = $this->analysisFromFixtures('TypedProperties.php');
        $analysis->process(new MergeIntoOpenApi());
        $analysis->process(new MergeIntoComponents());
        $analysis->process(new AugmentSchemas());
        [
            $stringType,
            $intType,
            $nullableString,
            $arrayType,
            $dateTime,
            $qualified,
            $namespaced,
            $importedNamespace,
            $nativeTrumpsVar,
            $annotationTrumpsNative,
            $annotationTrumpsAll,
            $undefined,
            $onlyAnnotated,
            $onlyVar,
            $staticUndefined,
            $staticString,
            $staticNullableString,
        ] = $analysis->openapi->components->schemas[0]->properties;

        $this->assertName($stringType, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($intType, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($nullableString, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($arrayType, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($dateTime, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($qualified, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($namespaced, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($importedNamespace, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($nativeTrumpsVar, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($annotationTrumpsNative, [
            'property' => Generator::UNDEFINED,
            'type' => 'integer',
        ]);
        $this->assertName($annotationTrumpsAll, [
            'property' => Generator::UNDEFINED,
            'type' => 'integer',
        ]);
        $this->assertName($undefined, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($onlyAnnotated, [
            'property' => Generator::UNDEFINED,
            'type' => 'integer',
        ]);
        $this->assertName($onlyVar, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($staticUndefined, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($staticString, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($staticNullableString, [
            'property' => Generator::UNDEFINED,
            'type' => Generator::UNDEFINED,
        ]);

        $analysis->process(new AugmentProperties());

        $this->assertName($stringType, [
            'property' => 'stringType',
            'type' => 'string',
        ]);
        $this->assertName($intType, [
            'property' => 'intType',
            'type' => 'integer',
        ]);
        $this->assertName($nullableString, [
            'property' => 'nullableString',
            'type' => 'string',
        ]);
        $this->assertName($arrayType, [
            'property' => 'arrayType',
            'type' => 'array',
        ]);
        $this->assertObjectHasAttribute(
            'ref',
            $arrayType->items
        );
        $this->assertEquals(
            '#/components/schemas/TypedProperties',
            $arrayType->items->ref
        );
        $this->assertName($dateTime, [
            'property' => 'dateTime',
            'type' => 'string',
            'format' => 'date-time',
        ]);
        $this->assertName($qualified, [
            'property' => 'qualified',
            'type' => 'string',
            'format' => 'date-time',
        ]);
        $this->assertName($namespaced, [
            'property' => 'namespaced',
            'ref' => '#/components/schemas/TypedProperties',
        ]);
        $this->assertName($importedNamespace, [
            'property' => 'importedNamespace',
            'ref' => '#/components/schemas/TypedProperties',
        ]);
        $this->assertName($nativeTrumpsVar, [
            'property' => 'nativeTrumpsVar',
            'type' => 'string',
        ]);
        $this->assertName($annotationTrumpsNative, [
            'property' => 'annotationTrumpsNative',
            'type' => 'integer',
        ]);
        $this->assertName($annotationTrumpsAll, [
            'property' => 'annotationTrumpsAll',
            'type' => 'integer',
        ]);
        $this->assertName($undefined, [
            'property' => 'undefined',
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($onlyAnnotated, [
            'property' => 'onlyAnnotated',
            'type' => 'integer',
        ]);
        $this->assertName($onlyVar, [
            'property' => 'onlyVar',
            'type' => 'integer',
        ]);
        $this->assertName($staticUndefined, [
            'property' => 'staticUndefined',
            'type' => Generator::UNDEFINED,
        ]);
        $this->assertName($staticString, [
            'property' => 'staticString',
            'type' => 'string',
        ]);
        $this->assertName($staticNullableString, [
            'property' => 'staticNullableString',
            'type' => 'string',
        ]);
    }

    protected function assertName(Property $property, array $expectedValues)
    {
        foreach ($expectedValues as $key => $val) {
            $this->assertSame($val, $property->$key, '@OA\Property()->property based on propertyname');
        }
    }
}
