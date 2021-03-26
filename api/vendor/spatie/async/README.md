# Asynchronous and parallel PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/async.svg?style=flat-square)](https://packagist.org/packages/spatie/async)
[![Build Status](https://img.shields.io/travis/spatie/async/master.svg?style=flat-square)](https://travis-ci.org/spatie/async)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/async.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/async)
[![StyleCI](https://styleci.io/repos/114228700/shield?branch=master)](https://styleci.io/repos/114228700)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/async.svg?style=flat-square)](https://packagist.org/packages/spatie/async)

This library provides a small and easy wrapper around PHP's PCNTL extension.
It allows running of different processes in parallel, with an easy-to-use API.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/async.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/async)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/async
```

## Usage

```php
use Spatie\Async\Pool;

$pool = Pool::create();

foreach ($things as $thing) {
    $pool->add(function () use ($thing) {
        // Do a thing
    })->then(function ($output) {
        // Handle success
    })->catch(function (Throwable $exception) {
        // Handle exception
    });
}

$pool->wait();
```

### Event listeners

When creating asynchronous processes, you'll get an instance of `ParallelProcess` returned.
You can add the following event hooks on a process.

```php
$pool
    ->add(function () {
        // ...
    })
    ->then(function ($output) {
        // On success, `$output` is returned by the process or callable you passed to the queue.
    })
    ->catch(function ($exception) {
        // When an exception is thrown from within a process, it's caught and passed here.
    })
    ->timeout(function () {
        // A process took too long to finish.
    })
;
```

### Functional API

Instead of using methods on the `$pool` object, you may also use the `async` and `await` helper functions.

```php
use Spatie\Async\Pool;

$pool = Pool::create();

foreach (range(1, 5) as $i) {
    $pool[] = async(function () {
        usleep(random_int(10, 1000));

        return 2;
    })->then(function (int $output) {
        $this->counter += $output;
    });
}

await($pool);
```

### Error handling

If an `Exception` or `Error` is thrown from within a child process, it can be caught per process by specifying a callback in the `->catch()` method.

```php
$pool
    ->add(function () {
        // ...
    })
    ->catch(function ($exception) {
        // Handle the thrown exception for this child process.
    })
;
```

If there's no error handler added, the error will be thrown in the parent process when calling `await()` or `$pool->wait()`.

If the child process would unexpectedly stop without throwing an `Throwable`, 
the output written to `stderr` will be wrapped and thrown as `Spatie\Async\ParallelError` in the parent process.

### Catching exceptions by type

By type hinting the `catch` functions, you can provide multiple error handlers, 
each for individual types of errors.

```php
$pool
    ->add(function () {
        throw new MyException('test');
    })
    ->catch(function (MyException $e) {
        // Handle `MyException`
    })
    ->catch(function (OtherException $e) {
        // Handle `OtherException`
    });
```

Note that as soon as an exception is handled, it won't trigger any other handlers

```php
$pool
    ->add(function () {
        throw new MyException('test');
    })
    ->catch(function (MyException $e) {
        // This one is triggerd when `MyException` is thrown
    })
    ->catch(function (Exception $e) {
        // This one is not triggerd, even though `MyException` extends `Exception`
    });
```

### Stopping a pool

If you need to stop a pool early, because the task it was performing has been completed by one
of the child processes, you can use the `$pool->stop()` method. This will prevent the 
pool from starting any additional processes.

```php
use Spatie\Async\Pool;

$pool = Pool::create();

// Generate 10k processes generating random numbers
for($i = 0; $i < 10000; $i++) {
    $pool->add(function() use ($i) {
        return rand(0, 100);
    })->then(function($output) use ($pool) {
        // If one of them randomly picks 100, end the pool early.
        if ($output === 100) {
            $pool->stop();
        }
    });
}

$pool->wait();
```

Note that a pool will be rendered useless after being stopped, and a new pool should be
created if needed.

### Using another PHP binary

By default the pool will use `php` to execute its child processes. You can configure another binary like so:

```php
Pool::create()
    ->withBinary('/path/to/php');
```

### Working with tasks

Besides using closures, you can also work with a `Task`. 
A `Task` is useful in situations where you need more setup work in the child process.
Because a child process is always bootstrapped from nothing, chances are you'll want to initialise eg. the dependency container before executing the task.
The `Task` class makes this easier to do.

```php
use Spatie\Async\Task;

class MyTask extends Task
{
    public function configure()
    {
        // Setup eg. dependency container, load config,...
    }

    public function run()
    {
        // Do the real work here.
    }
}

// Add the task to the pool
$pool->add(new MyTask());
```

#### Simple tasks

If you want to encapsulate the logic of your task, but don't want to create a full blown `Task` object,
you may also pass an invokable object to the `Pool`.

```php
class InvokableClass
{
    // ...

    public function __invoke()
    {
        // ...
    }
}

$pool->add(new InvokableClass(/* ... */));
```

### Pool configuration

You're free to create as many pools as you want, each pool has its own queue of processes it will handle.

A pool is configurable by the developer:

```php
use Spatie\Async\Pool;

$pool = Pool::create()

// The maximum amount of processes which can run simultaneously.
    ->concurrency(20)

// The maximum amount of time a process may take to finish in seconds
// (decimal places are supported for more granular timeouts).
    ->timeout(15)

// Configure which autoloader sub processes should use.
    ->autoload(__DIR__ . '/../../vendor/autoload.php')
    
// Configure how long the loop should sleep before re-checking the process statuses in microseconds.
    ->sleepTime(50000)
;
```

### Synchronous fallback

If the required extensions (`pcntl` and `posix`) are not installed in your current PHP runtime, the `Pool` will automatically fallback to synchronous execution of tasks.

The `Pool` class has a static method `isSupported` you can call to check whether your platform is able to run asynchronous processes. 

If you're using a `Task` to run processes, only the `run` method of those tasks will be called when running in synchronous mode.

## Behind the curtains

When using this package, you're probably wondering what's happening underneath the surface.

We're using the `symfony/process` component to create and manage child processes in PHP.
By creating child processes on the fly, we're able to execute PHP scripts in parallel.
This parallelism can improve performance significantly when dealing with multiple synchronous tasks,
which don't really need to wait for each other.
By giving these tasks a separate process to run on, the underlying operating system can take care of running them in parallel.

There's a caveat when dynamically spawning processes: you need to make sure that there won't be too many processes at once,
or the application might crash.
The `Pool` class provided by this package takes care of handling as many processes as you want
by scheduling and running them when it's possible.

That's the part that `async()` or `$pool->add()` does. Now let's look at what `await()` or `$pool->wait()` does.

When multiple processes are spawned, each can have a separate time to completion.
One process might eg. have to wait for a HTTP call, while the other has to process large amounts of data.
Sometimes you also have points in your code which have to wait until the result of a process is returned.

This is why we have to wait at a certain point in time: for all processes on a pool to finish,
so we can be sure it's safe to continue without accidentally killing the child processes which aren't done yet.

Waiting for all processes is done by using a `while` loop, which will wait until all processes are finished.
Determining when a process is finished is done by using a listener on the `SIGCHLD` signal.
This signal is emitted when a child process is finished by the OS kernel.
As of PHP 7.1, there's much better support for listening and handling signals,
making this approach more performant than eg. using process forks or sockets for communication.
You can read more about it [here](https://wiki.php.net/rfc/async_signals).

When a process is finished, its success event is triggered, which you can hook into with the `->then()` function.
Likewise, when a process fails or times out, the loop will update that process' status and move on.
When all processes are finished, the while loop will see that there's nothing more to wait for, and stop.
This is the moment your parent process can continue to execute.

### Comparison to other libraries

We've written a blog post containing more information about use cases for this package, as well as making comparisons to other asynchronous PHP libraries like ReactPHP and Amp: [http://stitcher.io/blog/asynchronous-php](http://stitcher.io/blog/asynchronous-php).

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Kruikstraat 22, 2018 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Credits

- [Brent Roose](https://github.com/brendt)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
