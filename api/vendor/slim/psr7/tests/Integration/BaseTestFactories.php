<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;

use function fopen;
use function fwrite;
use function is_resource;

trait BaseTestFactories
{
    /**
     * @param string $uri
     * @return UriInterface
     */
    protected function buildUri($uri): UriInterface
    {
        return (new UriFactory())->createUri($uri);
    }

    /**
     * @param $data
     * @return Stream
     */
    protected function buildStream($data): Stream
    {
        if (!is_resource($data)) {
            $h = fopen('php://temp', 'w+');
            fwrite($h, $data);

            $data = $h;
        }

        return new Stream($data);
    }

    /**
     * @param $data
     * @return UploadedFile
     */
    protected function buildUploadableFile($data): UploadedFile
    {
        return new UploadedFile($this->buildStream($data));
    }
}
