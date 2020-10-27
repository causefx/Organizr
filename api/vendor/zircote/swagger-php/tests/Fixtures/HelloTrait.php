<?php declare(strict_types=1);

namespace OpenApiFixures;

/**
 * @OA\Schema(schema="trait")
 */
trait Hello
{

    /**
     * @OA\Property()
     */
    public $greet = 'Hello!';
}
