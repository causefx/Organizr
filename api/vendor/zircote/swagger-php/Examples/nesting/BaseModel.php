<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * @OA\Schema()
 */
class BaseModel
{
    /**
     * @OA\Property()
     *
     * @var string
     */
    public $base;

}