<?php

use Spatie\Async\Runtime\ParentRuntime;

try {
    $autoloader = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;
    $outputLength = $argv[3] ? intval($argv[3]) : (1024 * 10);

    if (! $autoloader) {
        throw new InvalidArgumentException('No autoloader provided in child process.');
    }

    if (! file_exists($autoloader)) {
        throw new InvalidArgumentException("Could not find autoloader in child process: {$autoloader}");
    }

    if (! $serializedClosure) {
        throw new InvalidArgumentException('No valid closure was passed to the child process.');
    }

    require_once $autoloader;

    $task = ParentRuntime::decodeTask($serializedClosure);

    $output = call_user_func($task);

    $serializedOutput = base64_encode(serialize($output));

    if (strlen($serializedOutput) > $outputLength) {
        throw \Spatie\Async\Output\ParallelError::outputTooLarge($outputLength);
    }

    fwrite(STDOUT, $serializedOutput);

    exit(0);
} catch (Throwable $exception) {
    require_once __DIR__.'/../Output/SerializableException.php';

    $output = new \Spatie\Async\Output\SerializableException($exception);

    fwrite(STDERR, base64_encode(serialize($output)));

    exit(1);
}
