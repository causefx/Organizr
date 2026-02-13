<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\StaticAnalyser;

/**
 * @OA\Schema()
*/
class Php8AttrMix
{
    #[label('Id', [1])]
    /** @OA\Property() */
    public string $id = '';

    /** @OA\Property() */
    #[
        label('OtherId', [2, 3]),
        label('OtherId', [2, 3]),
    ]
    public string $otherId = '';

}