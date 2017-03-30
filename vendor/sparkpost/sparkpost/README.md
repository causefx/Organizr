<a href="https://www.sparkpost.com"><img src="https://www.sparkpost.com/sites/default/files/attachments/SparkPost_Logo_2-Color_Gray-Orange_RGB.svg" width="200px"/></a>

[Sign up](https://app.sparkpost.com/sign-up?src=Dev-Website&sfdcid=70160000000pqBb) for a SparkPost account and visit our [Developer Hub](https://developers.sparkpost.com) for even more content.

# SparkPost PHP Library

[![Travis CI](https://travis-ci.org/SparkPost/php-sparkpost.svg?branch=master)](https://travis-ci.org/SparkPost/php-sparkpost)
[![Coverage Status](https://coveralls.io/repos/SparkPost/php-sparkpost/badge.svg?branch=master&service=github)](https://coveralls.io/github/SparkPost/php-sparkpost?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/sparkpost/sparkpost.svg?maxAge=3600)](https://packagist.org/packages/sparkpost/sparkpost)
[![Packagist](https://img.shields.io/packagist/v/sparkpost/sparkpost.svg?maxAge=3600)](https://packagist.org/packages/sparkpost/sparkpost)
[![Slack Status](http://slack.sparkpost.com/badge.svg)](http://slack.sparkpost.com)

The official PHP library for using [the SparkPost REST API](https://developers.sparkpost.com/api/).

Before using this library, you must have a valid API Key. To get an API Key, please log in to your SparkPost account and generate one in the Settings page.

## Installation
**Please note: The composer package `sparkpost/php-sparkpost` has been changed to `sparkpost/sparkpost` starting with version 2.0.**

The recommended way to install the SparkPost PHP Library is through composer.

```
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Sparkpost requires php-http client (see [Setting up a Request Adapter](#setting-up-a-request-adapter)). There are several [providers](https://packagist.org/providers/php-http/client-implementation) available. If you were using guzzle6 your install might look like this.

```
composer require guzzlehttp/guzzle
composer require php-http/guzzle6-adapter
```

Next, run the Composer command to install the SparkPost PHP Library:

```
composer require sparkpost/sparkpost
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
use SparkPost\SparkPost;
```

## Setting up a Request Adapter

Because of dependency collision, we have opted to use a request adapter rather than
requiring a request library.  This means that your application will need to pass in
a request adapter to the constructor of the SparkPost Library.  We use the [HTTPlug](https://github.com/php-http/httplug) in SparkPost. Please visit their repo for a list of supported [clients and adapters](http://docs.php-http.org/en/latest/clients.html).  If you don't currently use a request library, you will
need to require one and create a client from it and pass it along. The example below uses the GuzzleHttp Client Library.

A Client can be setup like so:

```php
<?php
require 'vendor/autoload.php';

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$httpClient = new GuzzleAdapter(new Client());
$sparky = new SparkPost($httpClient, ['key'=>'YOUR_API_KEY']);
?>
```

## Initialization
#### new Sparkpost(httpClient, options)
* `httpClient`
    * Required: Yes
    * HTTP client or adapter supported by HTTPlug
* `options`
    * Required: Yes
    * Type: `String` or `Array`
    * A valid Sparkpost API key or an array of options
* `options.key`
    * Required: Yes
    * Type: `String`
    * A valid Sparkpost API key
* `options.host`
    * Required: No
    * Type: `String`
    * Default: `api.sparkpost.com`
* `options.protocol`
    * Required: No
    * Type: `String`
    * Default: `https`
* `options.port`
    * Required: No
    * Type: `Number`
    * Default: 443
* `options.version`
    * Required: No
    * Type: `String`
    * Default: `v1`
* `options.async`
    * Required: No
    * Type: `Boolean`
    * Default: `true`
    * `async` defines if the `request` function sends an asynchronous or synchronous request. If your client does not support async requests set this to `false`
* `options.debug`
    * Required: No
    * Type: `Boolean`
    * Default: `false`
    * If `debug` is true, then then all `SparkPostResponse` and `SparkPostException` instances will return any array of the request values through the function `getRequest`

## Methods
### request(method, uri [, payload [, headers]])
* `method`
    * Required: Yes
    * Type: `String`
    * HTTP method for request
* `uri`
    * Required: Yes
    * Type: `String`
    * The URI to recieve the request
* `payload`
    * Required: No
    * Type: `Array`
    * If the method is `GET` the values are encoded into the URL. Otherwise, if the method is `POST`, `PUT`, or `DELETE` the payload is used for the request body.
* `headers`
    * Required: No
    * Type: `Array`
    * Custom headers to be sent with the request.

### syncRequest(method, uri [, payload [, headers]])
Sends a synchronous request to the SparkPost API and returns a `SparkPostResponse`

### asyncRequest(method, uri [, payload [, headers]])
Sends an asynchronous request to the SparkPost API and returns a `SparkPostPromise`

### setHttpClient(httpClient)
* `httpClient`
    *  Required: Yes
    * HTTP client or adapter supported by HTTPlug

### setOptions(options)
* `options`
    *  Required: Yes
    *  Type: `Array`
    * See constructor


## Endpoints
### transmissions
* **get([transmissionID] [, payload])**
    * `transmissionID` - see `uri` request options
    * `payload` - see request options
* **post(payload)**
    * `payload` - see request options
    * `payload.cc`
        * Required: No
        * Type: `Array`
        * Recipients to recieve a carbon copy of the transmission
    * `payload.bcc`
        * Required: No
        * Type: `Array`
        * Recipients to descreetly recieve a carbon copy of the transmission
* **delete(transmissionID)**
    * `transmissionID` - see `uri` request options

## Examples

### Send An Email Using The Transmissions Endpoint
```php
<?php
require 'vendor/autoload.php';

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$httpClient = new GuzzleAdapter(new Client());
$sparky = new SparkPost($httpClient, ['key'=>'YOUR_API_KEY']);

$promise = $sparky->transmissions->post([
    'content' => [
        'from' => [
            'name' => 'SparkPost Team',
            'email' => 'from@sparkpostbox.com',
        ],
        'subject' => 'First Mailing From PHP',
        'html' => '<html><body><h1>Congratulations, {{name}}!</h1><p>You just sent your very first mailing!</p></body></html>',
        'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
    ],
    'substitution_data' => ['name' => 'YOUR_FIRST_NAME'],
    'recipients' => [
        [
            'address' => [
                'name' => 'YOUR_NAME',
                'email' => 'YOUR_EMAIL',
            ],
        ],
    ],
    'cc' => [
        [
            'address' => [
                'name' => 'ANOTHER_NAME',
                'email' => 'ANOTHER_EMAIL',
            ],
        ],
    ],
    'bcc' => [
        [
            'address' => [
                'name' => 'AND_ANOTHER_NAME',
                'email' => 'AND_ANOTHER_EMAIL',
            ],
        ],
    ],
]);
?>
```

### Send An API Call Using The Base Request Function
We provide a base request function to access any of our API resources.
```php
<?php
require 'vendor/autoload.php';

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$httpClient = new GuzzleAdapter(new Client());
$sparky = new SparkPost($httpClient, ['key'=>'YOUR_API_KEY']);

$promise = $sparky->request('GET', 'metrics/ip-pools', [
    'from' => '2014-12-01T09:00',
    'to' => '2015-12-01T08:00',
    'timezone' => 'America/New_York',
    'limit' => '5',
]);
?>
```


## Handling Responses
The API calls either return a `SparkPostPromise` or `SparkPostResponse` depending on if `async` is `true` or `false`

### Synchronous
```php
$sparky->setOptions(['async' => false]);
try {
    $response = $sparky->transmissions->get();
    
    echo $response->getStatusCode()."\n";
    print_r($response->getBody())."\n";
}
catch (\Exception $e) {
    echo $e->getCode()."\n";
    echo $e->getMessage()."\n";
}
```

### Asynchronous
Asynchronous an be handled in two ways: by passing callbacks or waiting for the promise to be fulfilled. Waiting acts like synchronous request.
##### Wait (Synchronous)
```php
$promise = $sparky->transmissions->get();

try {
    $response = $promise->wait();
    echo $response->getStatusCode()."\n";
    print_r($response->getBody())."\n";
} catch (\Exception $e) {
    echo $e->getCode()."\n";
    echo $e->getMessage()."\n";
}

echo "I will print out after the promise is fulfilled";
```

##### Then (Asynchronous)
```php
$promise = $sparky->transmissions->get();

$promise->then(
    // Success callback
    function ($response) {
        echo $response->getStatusCode()."\n";
        print_r($response->getBody())."\n";
    },
    // Failure callback
    function (Exception $e) {
        echo $e->getCode()."\n";
        echo $e->getMessage()."\n";
    }
);

echo "I will print out before the promise is fulfilled";
```

## Handling Exceptions
An exception will be thrown in two cases: there is a problem with the request or  the server returns a status code of `400` or higher.

### SparkPostException
* **getCode()**
    * Returns the response status code of `400` or higher
* **getMessage()**
    * Returns the exception message
* **getBody()**
    * If there is a response body it returns it as an `Array`. Otherwise it returns `null`.
* **getRequest()**
    * Returns an array with the request values `method`, `url`, `headers`, `body` when `debug` is `true`


### Contributing
See [contributing](https://github.com/SparkPost/php-sparkpost/blob/master/CONTRIBUTING.md).
