<?php

namespace OpenApiFixtures;

/**
 * @OA\Schema()
 */
class ExtendedWithoutAllOf extends Base
{

    /**
     * @OA\Property();
     * @var string
     */
    public $extendedProperty;
}
