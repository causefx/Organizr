String Encode
==========================

Version 1.0.1

String Encode is a simple PHP wrapper package to facilitate the encoding of strings in different charsets.

Install
-------

This package can be found on [packagist](https://packagist.org/packages/paquettg/stringencode) and is best loaded using [composer](http://getcomposer.org/). It does require php 7.1 or higher, so keep that in consideration. 

Usage
-----

This is a really simple package so there is not much to say about it. The following is just about the only usage for this package at the moment.

```php
use stringEncode\Encode;

$str    = "Calendrier de l'avent façon Necta!"
$encode = new Encode;
$encode->detect($str);
$newstr = $encode->convert($str);
echo $newstr; // "Calendrier de l'avent façon Necta!" in UTF-8 encoding (default)
```

As you can see, it is a very simple encoding converter.
