<?php declare(strict_types=1);

namespace OpenApi\Tests\Fixtures;

use OpenApi\Tests\Annotations as OAF;

/**
 * @OA\Info(title="Custom annotation attributes", version="1.0")
 * @OA\PathItem(path="/")
 *
 * @OA\Schema(
 *   schema="UsingCustomAttachables",
 *   required={"name"},
 *   @OAF\CustomAttachable(value=1),
 *   @OAF\CustomAttachable(value={"foo"=false, "ping"="pong"})
 * )
 */
class UsingCustomAttachables
{
}
