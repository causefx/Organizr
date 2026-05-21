<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * @OA\Schema()
 */
class ActualModel extends SoCloseModel
{
    /**
     * @OA\Property()
     *
     * @var string
     */
    public $actual;

}