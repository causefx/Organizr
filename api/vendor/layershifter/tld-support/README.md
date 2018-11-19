# TLDSupport

Support package for [TLDDatabase](https://github.com/layershifter/TLDDatabase) and [TLDExtract](https://github.com/layershifter/TLDExtract). This package provides helpers for work with arrays, IP addresses, strings and more.

[![Build Status](https://travis-ci.org/layershifter/TLDSupport.svg)](https://travis-ci.org/layershifter/TLDSupport) [![Code Climate](https://codeclimate.com/github/layershifter/TLDSupport/badges/gpa.svg)](https://codeclimate.com/github/layershifter/TLDSupport) [![Test Coverage](https://codeclimate.com/github/layershifter/TLDSupport/badges/coverage.svg)](https://codeclimate.com/github/layershifter/TLDSupport/coverage) [![PHP 7 ready](http://php7ready.timesplinter.ch/layershifter/TLDSupport/master/badge.svg)](https://travis-ci.org/layershifter/TLDSupport})

---

This package is compliant with [PSR-1][], [PSR-2][], [PSR-4][]. If you notice compliance oversights, please send a patch via pull request.

## Requirements

The following versions of PHP are supported.

* PHP 5.5
* PHP 5.6
* PHP 7.0
* HHVM

## Usage

### Arrays:
```php
mixed Arr::first(array $haystack, null|callable $callback, mixed $default);
mixed Arr::last(array $haystack, null|callable $callback, mixed $default);
```
### IP addresses:
```php
bool IP::isValid(string $hostname);
```
### Strings:
```php
bool     Str::endsWith(string $haystack, string|array $needles);
int      Str::length(string $value);
string   Str::lower(string $value);
string   Str::substr(string $string, int $start, int|null $length = null);
bool     Str::startsWith(string $haystack, string|array $needles);
bool|int Str::strpos(string $haystack, string $needles, int $offset = 0);
bool|int Str::strrpos(string $haystack, string $needles, int $offset = 0);
```
### Mixed:
```php
mixed Mixed::value(mixed $value);
```
## Install

Via Composer

``` bash
$ composer require layershifter/tld-support
```

## License

This library is released under the Apache 2.0 license. Please see [License File](LICENSE) for more information.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md