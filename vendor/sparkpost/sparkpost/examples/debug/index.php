<?php

namespace Examples\Templates;

require dirname(__FILE__).'/../bootstrap.php';

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$httpClient = new GuzzleAdapter(new Client());

/*
 * configure options in example-options.json
 */
$sparky = new SparkPost($httpClient, [
    "key" => "YOUR_API_KEY",
    // This will expose your API KEY - do not use this in production.
    "debug" => true
]);

$promise = $sparky->request('GET', 'templates');

try {
    $response = $promise->wait();

    var_dump($response);

    echo "Request:\n";
    print_r($response->getRequest());

    echo "Response:\n";
    echo $response->getStatusCode()."\n";
    print_r($response->getBody())."\n";
} catch (\Exception $e) {
    echo "Request:\n";
    print_r($e->getRequest());

    echo "Exception:\n";
    echo $e->getCode()."\n";
    echo $e->getMessage()."\n";
}
