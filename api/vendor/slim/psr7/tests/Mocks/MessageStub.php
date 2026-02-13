<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Mocks;

use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Interfaces\HeadersInterface;
use Slim\Psr7\Message;

class MessageStub extends Message
{
    /**
     * Protocol version
     */
    public string $protocolVersion;

    /**
     * Headers
     *
     * @var HeadersInterface
     */
    public $headers;

    /**
     * Body object
     *
     * @var StreamInterface
     */
    public $body;
}
