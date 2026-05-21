<?php

require __DIR__ . '/../../../vendor/autoload.php';
// also load our custom processor...
require __DIR__ . '/SchemaQueryParameter.php';

// merge our custom processor
$processors = [];
foreach (\OpenApi\Analysis::processors() as $processor) {
    $processors[] = $processor;
    if ($processor instanceof \OpenApi\Processors\BuildPaths) {
        $processors[] = new \SchemaQueryParameterProcessor\SchemaQueryParameter();
    }
}

$options = [
    'processors' => $processors,
];

$openapi = (new OpenApi\Generator())
            ->setProcessors($processors)
            ->generate([__DIR__ . '/app']);
$spec = json_encode($openapi, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/schema-query-parameter.json', $spec);
//echo $spec;