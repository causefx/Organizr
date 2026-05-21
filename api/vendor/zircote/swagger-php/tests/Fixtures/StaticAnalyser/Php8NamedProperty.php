<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\StaticAnalyser;

/**
 * @OA\Schema()
 */
class Php8NamedProperty
{
    public function __construct(
        /**
         * Label List
         *
         * @var Label[]|null $labels
         * @OA\Property()
         */
        public ?array $labels
    )
    {
    }
}
