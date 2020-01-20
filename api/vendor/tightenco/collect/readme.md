[![Travis Status for tightenco/collect](https://travis-ci.org/tightenco/collect.svg?branch=master)](https://travis-ci.org/tightenco/collect)

![](https://raw.githubusercontent.com/tightenco/collect/master/collect-logo.png)

# Collect - Illuminate Collections

Import [Laravel's Collections](https://laravel.com/docs/collections) into non-Laravel packages easily, without needing to require the entire `Illuminate\Support` package. ([Why not pull `Illuminate\Support` in framework-agnostic packages](https://mattallan.org/posts/dont-use-illuminate-support/))

Written by Taylor Otwell as a part of Laravel's [Illuminate/Support](https://github.com/illuminate/support) package, Collect is just the code from Support needed in order to use Collections on their own.

Lovingly split by Matt Stauffer for [Tighten Co.](https://tighten.co/), with a kick in the butt to finally do it from [@assertchris](https://github.com/assertchris).

## Installation

With [Composer](https://getcomposer.org):

```bash
composer require tightenco/collect
```

## Development
If you are a developer working on Collect and you're tasked with upgrading it to mirror a new version of Laravel,  run `./upgrade.sh` from the root directory. You can pass a parameter to target a specific Laravel version (e.g. `./upgrade.sh 5.7.10`) or, if you don't pass a parameter, the script will find the latest tagged release and run against that.

The upgrader will pull down the appropriate source and test files for the specified version of Laravel and then run the tests.

```bash
./upgrade.sh
# or
./upgrade.sh 5.7.10
```

> The upgrade script requires the use of `wget`. It's recommended to install [homebrew](https://brew.sh), and run `brew install wget`

## Testing
**Due to a [dependency on Carbon](https://github.com/tightenco/collect/commit/4afe1fcb40f1c10e399730562c2c7ca36c6fba01), tests won't pass until you've run `./upgrade.sh` at least once locally.**

```bash
vendor/bin/phpunit
```

## FAQ
 - **Will this develop independently from Illuminate's Collections?**  
    No. Right now it's split manually, but the goal is for it shortly to be split automatically to keep it in sync with Laravel's Collections, even mirroring the release numbers.
 - **Why is the package `tightenco/collect` instead of `illuminate/collect`?**  
    It's not an official Laravel package so we don't want to use the Packagist namespace reserved by Laravel packages. One day `Collection` may be extracted from `illuminate/support` to a new package. If so, we'll deprecate this package and point to the core version.
 - **Why not just use an array?**  
    What a great question. [Tighten alum Adam Wathan has a book about that.](https://adamwathan.me/refactoring-to-collections/)

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT). Collect consists almost entirely of Laravel source code, so maintains the same license.
