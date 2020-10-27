<?php declare(strict_types=1);

/**
 * @license Apache 2.0
 */

namespace OpenApiTests;

use OpenApi\Annotations\AbstractAnnotation;
use const OpenApi\UNDEFINED;
use function get_class_vars;

class UndefinedTest extends OpenApiTestCase
{
    /**
     * @dataProvider allAnnotations
     */
    public function testDefaultPropertiesAreUndefined($annotation)
    {
        $properties = get_class_vars($annotation);
        $skip = AbstractAnnotation::$_blacklist;
        foreach ($properties as $property => $value) {
            if (in_array($property, $skip)) {
                continue;
            }
            if ($value === null) {
                $this->fail("Property ".basename($annotation).'->'.$property.' should be UNDEFINED');
            }
        }
    }
}
