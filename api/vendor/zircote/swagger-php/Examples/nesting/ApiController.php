<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures\Processors\Nesting;

/**
 * An entity controller class.
 *
 * @OA\Info(version="1.0.0", title="Nested schemas")
 */
class ApiController
{
    /**
     * @OA\Get(
     *   tags={"api"},
     *   path="/entity/{id}",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *       format="int64",
     *     )
     *   ),
     *   @OA\Response(
     *       response="default",
     *       description="successful operation",
     *       @OA\JsonContent(ref="#/components/schemas/ActualModel")
     *   )
     * )
     */
    public function get($id)
    {
    }
}
