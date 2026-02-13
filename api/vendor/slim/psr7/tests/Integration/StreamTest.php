<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\StreamIntegrationTest;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Stream;

use function fopen;
use function fwrite;
use function is_resource;
use function is_string;

class StreamTest extends StreamIntegrationTest
{
    use BaseTestFactories;

    /**
     * @param string|resource|StreamInterface $data
     *
     * @return StreamInterface
     */
    public function createStream($data)
    {
        if ($data instanceof StreamInterface) {
            return $data;
        } elseif (is_resource($data)) {
            return new Stream($data);
        } elseif (is_string($data)) {
            $s = fopen('php://temp', 'w+');
            fwrite($s, $data);
            return new Stream($s);
        }

        throw new InvalidArgumentException();
    }
}
