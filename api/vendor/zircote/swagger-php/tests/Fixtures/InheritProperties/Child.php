<?php declare(strict_types=1);

namespace AnotherNamespace;

/**
 * @OA\Schema()
 */
class Child extends \OpenApiFixtures\Ancestor
{

    /**
     * @var bool
     * @OA\Property()
     */
    public $isBaby;
}
