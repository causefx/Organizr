<?php

namespace OpenApiFixtures;

use OpenApi\Annotations\Property;
use OpenApi\Annotations\Schema;

/**
 * @Schema(@Property(property="nested",ref="#/components/schemas/NestedSchema")),
 * @Schema(schema="NestedSchema", @Property(property="nestedProperty", type="string"))
 */
class ExtendedWithTwoSchemas extends Base
{

    /**
     * @OA\Property();
     * @var string
     */
    public $extendedProperty;
}
