<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * No schema!
 */
class IntermediateModel extends BaseModel
{
    /**
     * @OA\Property()
     *
     * @var string
     */
    public $intermediate;

}