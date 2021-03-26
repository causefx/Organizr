<?php

use Spatie\Async\Pool;
use Spatie\Async\Process\Runnable;
use Spatie\Async\Runtime\ParentRuntime;

if (! function_exists('async')) {
    /**
     * @param \Spatie\Async\Task|callable $task
     *
     * @return \Spatie\Async\Process\ParallelProcess
     */
    function async($task): Runnable
    {
        return ParentRuntime::createProcess($task);
    }
}

if (! function_exists('await')) {
    function await(Pool $pool): array
    {
        return $pool->wait();
    }
}
