<?php declare(strict_types=1);

// NOTE: this file uses "\r\n" linebreaks on purpose

namespace OpenApi\Tests\Fixtures;

use Exception;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use OpenApi\Generator as OpenApiGenerator;

/**
 * A customer.
 *
 * @OA\Info(title="Fixture for ClassPropertiesTest", version="test")
 * @OA\Schema()
 */
class Customer
{

    /**
     * The first name of the customer.
     *
     * @var string
     * @example John
     * @OA\Property()
     */
    public $firstname;

    /**
     * @var null|string The second name of the customer.
     * @example Allan
     * @OA\Property()
     */
    public $secondname;

    /**
     * The third name of the customer.
     *
     * @var string|null
     * @example Peter
     * @OA\Property()
     */
    public $thirdname;

    /**
     * The unknown name of the customer.
     *
     * @var unknown|null
     * @example Unknown
     * @OA\Property()
     */
    public $fourthname;

    /**
     * @var string The lastname of the customer.
     * @OA\Property()
     */
    public $lastname;

    /**
     * @OA\Property()
     * @var string[]
     */
    public $tags;

    /**
     * @OA\Property()
     * @var Customer
     */
    public $submittedBy;

    /**
     * @OA\Property()
     * @var Customer[]
     */
    public $friends;

    /**
     * @OA\Property()
     * @var Customer|null
     */
    public $bestFriend;

    /**
     * @OA\Property()
     * @var Customer[]|null
     */
    public $endorsedFriends;

    /**
     * for ContextTest
     */
    public function testResolvingFullyQualifiedNames()
    {
        (new OpenApiGenerator())->getLogger();
        (new Generator())->getLogger();
        new OA\Contact([]);
        throw new Exception();
    }
}
