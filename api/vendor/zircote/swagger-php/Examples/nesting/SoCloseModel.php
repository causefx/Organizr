<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * No schema!
 */
class SoCloseModel extends AlmostModel
{
    /**
     * @OA\Property()
     *
     * @var string
     */
    public $soClose;

}