<?php

namespace Spatie\Async\Output;

class ParallelException extends \Exception
{
    /** @var string */
    protected $originalClass;

    /** @var string */
    protected $originalTrace;

    public function __construct(string $message, string $originalClass, string $originalTrace)
    {
        parent::__construct($message);
        $this->originalClass = $originalClass;
        $this->originalTrace = $originalTrace;
    }

    /** @return string */
    public function getOriginalClass(): string
    {
        return $this->originalClass;
    }

    /** @return string */
    public function getOriginalTrace(): string
    {
        return $this->originalTrace;
    }
}
