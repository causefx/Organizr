<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * @OA\Schema()
 */
class AlmostModel extends IntermediateModel
{
    /**
     * @OA\Property()
     *
     * @var string
     */
    public $almost;

}