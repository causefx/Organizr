<?php

namespace Spatie\Async\Output;

use Exception;

class ParallelError extends Exception
{
    public static function fromException($exception): self
    {
        return new self($exception);
    }

    public static function outputTooLarge(int $bytes): self
    {
        return new self("The output returned by this child process is too large. The serialized output may only be $bytes bytes long.");
    }
}
