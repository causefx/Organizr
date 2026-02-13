<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Psr7\Integration;

use Http\Psr7Test\UploadedFileIntegrationTest;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\UploadedFile;

use function sys_get_temp_dir;
use function tempnam;

class UploadedFileTest extends UploadedFileIntegrationTest
{
    use BaseTestFactories;

    protected string $tempFilename;

    /**
     * @return UploadedFileInterface
     */
    public function createSubject()
    {
        $this->tempFilename = tempnam(sys_get_temp_dir(), 'Slim_Http_UploadedFileTest_');
        if (!$this->tempFilename) {
            throw new \RuntimeException("Unable to create temporary file");
        }
        file_put_contents($this->tempFilename, '12345');

        return new UploadedFile(
            $this->tempFilename,
            basename($this->tempFilename),
            'text/plain',
            (int)filesize($this->tempFilename)
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->tempFilename)) {
            unlink($this->tempFilename);
        }
    }
}
