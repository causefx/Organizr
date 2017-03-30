# clue/stream-filter [![Build Status](https://travis-ci.org/clue/php-stream-filter.svg?branch=master)](https://travis-ci.org/clue/php-stream-filter)

A simple and modern approach to stream filtering in PHP

**Table of contents**

* [Why?](#why)
* [Usage](#usage)
  * [append()](#append)
  * [prepend()](#prepend)
  * [fun()](#fun)
  * [remove()](#remove)
* [Install](#install)
* [License](#license)

## Why?

PHP's stream filtering system is great!

It offers very powerful stream filtering options and comes with a useful set of built-in filters.
These filters can be used to easily and efficiently perform various transformations on-the-fly, such as:

* read from a gzip'ed input file,
* transcode from ISO-8859-1 (Latin1) to UTF-8,
* write to a bzip output file
* and much more.

But let's face it:
Its API is [*difficult to work with*](http://php.net/manual/en/php-user-filter.filter.php)
and its documentation is [*subpar*](http://stackoverflow.com/questions/27103269/what-is-a-bucket-brigade).
This combined means its powerful features are often neglected.

This project aims to make these features more accessible to a broader audience.
* **Lightweight, SOLID design** -
  Provides a thin abstraction that is [*just good enough*](http://en.wikipedia.org/wiki/Principle_of_good_enough)
  and does not get in your way.
  Custom filters require trivial effort.
* **Good test coverage** -
  Comes with an automated tests suite and is regularly tested in the *real world*

## Usage

This lightweight library consists only of a few simple functions.
All functions reside under the `Clue\StreamFilter` namespace.

The below examples assume you use an import statement similar to this:

```php
use Clue\StreamFilter as Filter;

Filter\append(…);
```

Alternatively, you can also refer to them with their fully-qualified name:

```php
\Clue\StreamFilter\append(…);
```

### append()

The `append($stream, $callback, $read_write = STREAM_FILTER_ALL)` function can be used to
append a filter callback to the given stream.

Each stream can have a list of filters attached.
This function appends a filter to the end of this list.

This function returns a filter resource which can be passed to [`remove()`](#remove).
If the given filter can not be added, it throws an `Exception`.

The `$stream` can be any valid stream resource, such as:

```php
$stream = fopen('demo.txt', 'w+');
```

The `$callback` should be a valid callable function which accepts an individual chunk of data
and should return the updated chunk:

```php
$filter = Filter\append($stream, function ($chunk) {
    // will be called each time you read or write a $chunk to/from the stream
    return $chunk;
});
```

As such, you can also use native PHP functions or any other `callable`:

```php
Filter\append($stream, 'strtoupper');

// will write "HELLO" to the underlying stream
fwrite($stream, 'hello');
```

If the `$callback` accepts invocation without parameters, then this signature
will be invoked once ending (flushing) the filter:

```php
Filter\append($stream, function ($chunk = null) {
    if ($chunk === null) {
        // will be called once ending the filter
        return 'end';
    }
    // will be called each time you read or write a $chunk to/from the stream
    return $chunk;
});

fclose($stream);
```

> Note: Legacy PHP versions (PHP < 5.4) do not support passing additional data
from the end signal handler if the stream is being closed.

If your callback throws an `Exception`, then the filter process will be aborted.
In order to play nice with PHP's stream handling, the `Exception` will be
transformed to a PHP warning instead:

```php
Filter\append($stream, function ($chunk) {
    throw new \RuntimeException('Unexpected chunk');
});

// raises an E_USER_WARNING with "Error invoking filter: Unexpected chunk"
fwrite($stream, 'hello');
```

The optional `$read_write` parameter can be used to only invoke the `$callback` when either writing to the stream or only when reading from the stream:

```php
Filter\append($stream, function ($chunk) {
    // will be called each time you write to the stream
    return $chunk;
}, STREAM_FILTER_WRITE);

Filter\append($stream, function ($chunk) {
    // will be called each time you read from the stream
    return $chunk;
}, STREAM_FILTER_READ);
```

### prepend()

The `prepend($stream, $callback, $read_write = STREAM_FILTER_ALL)` function can be used to
prepend a filter callback to the given stream.

Each stream can have a list of filters attached.
This function prepends a filter to the start of this list.

This function returns a filter resource which can be passed to [`remove()`](#remove).
If the given filter can not be added, it throws an `Exception`.

```php
$filter = Filter\prepend($stream, function ($chunk) {
    // will be called each time you read or write a $chunk to/from the stream
    return $chunk;
});
```

### fun()

The `fun($filter, $parameters = null)` function can be used to
create a filter function which uses the given built-in `$filter`.

PHP comes with a useful set of [built-in filters](http://php.net/manual/en/filters.php).
Using `fun()` makes accessing these as easy as passing an input string to filter
and getting the filtered output string.

```php
$fun = Filter\fun('string.rot13');

assert('grfg' === $fun('test'));
assert('test' === $fun($fun('test'));
```

Please note that not all filter functions may be available depending on installed
PHP extensions and the PHP version in use.
In particular, [HHVM](http://hhvm.com/) may not offer the same filter functions
or parameters as Zend PHP.
Accessing an unknown filter function will result in a `RuntimeException`:

```php
Filter\fun('unknown'); // throws RuntimeException
```

Some filters may accept or require additional filter parameters.
The optional `$parameters` argument will be passed to the filter handler as-is.
Please refer to the individual filter definition for more details.
For example, the `string.strip_tags` filter can be invoked like this:

```php
$fun = Filter\fun('string.strip_tags', '<a><b>');

$ret = $fun('<b>h<br>i</b>');
assert('<b>hi</b>' === $ret);
```

Under the hood, this function allocates a temporary memory stream, so it's
recommended to clean up the filter function after use.
Also, some filter functions (in particular the
[zlib compression filters](http://php.net/manual/en/filters.compression.php))
may use internal buffers and may emit a final data chunk on close.
The filter function can be closed by invoking without any arguments:

```php
$fun = Filter\fun('zlib.deflate');

$ret = $fun('hello') . $fun('world') . $fun();
assert('helloworld' === gzinflate($ret));
```

The filter function must not be used anymore after it has been closed.
Doing so will result in a `RuntimeException`:

```php
$fun = Filter\fun('string.rot13');
$fun();

$fun('test'); // throws RuntimeException
```

> Note: If you're using the zlib compression filters, then you should be wary
about engine inconsistencies between different PHP versions and HHVM.
These inconsistencies exist in the underlying PHP engines and there's little we
can do about this in this library.
[Our test suite](tests/) contains several test cases that exhibit these issues.
If you feel some test case is missing or outdated, we're happy to accept PRs! :)

### remove()

The `remove($filter)` function can be used to
remove a filter previously added via [`append()`](#append) or [`prepend()`](#prepend).

```php
$filter = Filter\append($stream, function () {
    // …
});
Filter\remove($filter);
```

## Install

The recommended way to install this library is [through composer](https://getcomposer.org).
[New to composer?](https://getcomposer.org/doc/00-intro.md)

```bash
$ composer require clue/stream-filter:~1.3
```

## License

MIT
