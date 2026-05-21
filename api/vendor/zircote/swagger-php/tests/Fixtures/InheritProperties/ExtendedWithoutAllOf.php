<?php

namespace OpenApi\Tests\Fixtures\InheritProperties;

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
