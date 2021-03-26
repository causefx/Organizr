<?php

namespace Spatie\Async;

use Spatie\Async\Output\SerializableException;
use Spatie\Async\Process\ParallelProcess;

class PoolStatus
{
    protected $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function __toString(): string
    {
        return $this->lines(
            $this->summaryToString(),
            $this->failedToString()
        );
    }

    protected function lines(string ...$lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    protected function summaryToString(): string
    {
        $queue = $this->pool->getQueue();
        $finished = $this->pool->getFinished();
        $failed = $this->pool->getFailed();
        $timeouts = $this->pool->getTimeouts();

        return
            'queue: '.count($queue)
            .' - finished: '.count($finished)
            .' - failed: '.count($failed)
            .' - timeout: '.count($timeouts);
    }

    protected function failedToString(): string
    {
        return (string) array_reduce($this->pool->getFailed(), function ($currentStatus, ParallelProcess $process) {
            $output = $process->getErrorOutput();

            if ($output instanceof SerializableException) {
                $output = get_class($output->asThrowable()).': '.$output->asThrowable()->getMessage();
            }

            return $this->lines((string) $currentStatus, "{$process->getPid()} failed with {$output}");
        });
    }
}
