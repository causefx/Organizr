<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Parser;

use OpenApi\Tests\Fixtures\Parser\HelloTrait as Hello;
use OpenApi\Tests\Fixtures\Parser\Sub\SubClass as ParentClass;

/**
 * @OA\Schema(
 *     example=User::CONSTANT,
 * )
 */
class User extends ParentClass implements \OpenApi\Tests\Fixtures\Parser\UserInterface
{
    use Hello;

    const CONSTANT = 'value';

    /**
     * {@inheritDoc}
     */
    public function getFirstName()
    {
        return 'Joe';
    }
}
