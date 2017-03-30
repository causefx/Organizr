# Changelog

## 1.3.0 (2015-11-08)

* Feature: Support accessing built-in filters as callbacks
  (#5 by @clue)

  ```php
$fun = Filter\fun('zlib.deflate');

$ret = $fun('hello') . $fun('world') . $fun();
assert('helloworld' === gzinflate($ret));
```

## 1.2.0 (2015-10-23)

* Feature: Invoke close event when closing filter (flush buffer)
  (#9 by @clue)

## 1.1.0 (2015-10-22)

* Feature: Abort filter operation when catching an Exception
  (#10 by @clue)

* Feature: Additional safeguards to prevent filter state corruption
  (#7 by @clue)

## 1.0.0 (2015-10-18)

* First tagged release
