# LineReader

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-ghactions]][link-ghactions]

LineReader is a library to read large files line by line in a memory efficient (constant) way.

## Install

Via Composer

```bash
$ composer require bcremer/line-reader
```

## Usage

Given we have a textfile (`some/file.txt`) with lines like:

```
Line 1
Line 2
Line 3
Line 4
Line 5
Line 6
Line 7
Line 8
Line 9
Line 10
```

Also let's assume the namespace is imported to keep the examples dense:

```
use Bcremer\LineReader\LineReader;
```

### Read forwards

```php
foreach (LineReader::readLines('some/file.txt') as $line) {
    echo $line . "\n"
}
```

The output will be:

```
Line 1
Line 2
Line 3
Line 4
Line 5
...
```

To set an offset or a limit use the `\LimitIterator`:

```php
$lineGenerator = LineReader::readLines('some/file.txt');
$lineGenerator = new \LimitIterator($lineGenerator, 2, 5);
foreach ($lineGenerator as $line) {
    echo $line . "\n"
}
```

Will output line 3 to 7

```
Line 3
Line 4
Line 5
Line 6
Line 7
```

### Read backwards

```php
foreach (LineReader::readLinesBackwards('some/file.txt') as $line) {
    echo $line;
}
```

```
Line 10
Line 9
Line 8
Line 7
Line 6
...
```

Example: Read the last 5 lines in forward order:

```php
$lineGenerator = LineReader::readLinesBackwards('some/file.txt');
$lineGenerator = new \LimitIterator($lineGenerator, 0, 5);

$lines = array_reverse(iterator_to_array($lineGenerator));
foreach ($line as $line) {
    echo $line;
}
```

```
Line 6
Line 7
Line 8
Line 9
Line 10
```

## Testing

```bash
$ composer test
```

```bash
$ TEST_MAX_LINES=200000 composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/bcremer/line-reader.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/bcremer/line-reader
[ico-ghactions]: https://github.com/bcremer/LineReader/workflows/Build/badge.svg
[link-ghactions]: https://github.com/bcremer/LineReader/actions?query=workflow%3ABuild
