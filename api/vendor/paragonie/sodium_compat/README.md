# Sodium Compat

[![Linux Build Status](https://travis-ci.org/paragonie/sodium_compat.svg?branch=master)](https://travis-ci.org/paragonie/sodium_compat)
[![Windows Build Status](https://ci.appveyor.com/api/projects/status/itcx1vgmfqiawgbe?svg=true)](https://ci.appveyor.com/project/paragonie-scott/sodium-compat)
[![Latest Stable Version](https://poser.pugx.org/paragonie/sodium_compat/v/stable)](https://packagist.org/packages/paragonie/sodium_compat)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/sodium_compat/v/unstable)](https://packagist.org/packages/paragonie/sodium_compat)
[![License](https://poser.pugx.org/paragonie/sodium_compat/license)](https://packagist.org/packages/paragonie/sodium_compat)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/sodium_compat.svg)](https://packagist.org/packages/paragonie/sodium_compat)

Sodium Compat is a pure PHP polyfill for the Sodium cryptography library 
(libsodium), a core extension in PHP 7.2.0+ and otherwise [available in PECL](https://pecl.php.net/package/libsodium).

This library tentativeley supports PHP 5.2.4 - 7.x (latest), but officially
only supports [non-EOL'd versions of PHP](https://secure.php.net/supported-versions.php).

If you have the PHP extension installed, Sodium Compat will opportunistically
and transparently use the PHP extension instead of our implementation.

## IMPORTANT!

This cryptography library has not been formally audited by an independent third 
party that specializes in cryptography or cryptanalysis.

If you require such an audit before you can use sodium_compat in your projects
and have the funds for such an audit, please open an issue or contact 
`security at paragonie dot com` so we can help get the ball rolling.

If you'd like to learn more about the defensive security measures we've taken,
please read [*Cryptographically Secure PHP Development*](https://paragonie.com/blog/2017/02/cryptographically-secure-php-development).

# Installing Sodium Compat

If you're using Composer:

```bash
composer require paragonie/sodium_compat
```

### Install From Source

If you're not using Composer, download a [release tarball](https://github.com/paragonie/sodium_compat/releases)
(which should be signed with [our GnuPG public key](https://paragonie.com/static/gpg-public-key.txt)), extract
its contents, then include our `autoload.php` script in your project.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";
```

### PHP Archives (Phar) Releases

Since version 1.3.0, [sodium_compat releases](https://github.com/paragonie/sodium_compat/releases) include a
PHP Archive (.phar file) and associated GPG signature. First, download both files and verify them with our
GPG public key, like so:

```bash
# Getting our public key from the keyserver:
gpg --fingerprint 7F52D5C61D1255C731362E826B97A1C2826404DA
if [ $? -ne 0 ]; then
    echo -e "\033[33mDownloading PGP Public Key...\033[0m"
    gpg  --keyserver pgp.mit.edu --recv-keys 7F52D5C61D1255C731362E826B97A1C2826404DA
    # Security <security@paragonie.com>
    gpg --fingerprint 7F52D5C61D1255C731362E826B97A1C2826404DA
    if [ $? -ne 0 ]; then
        echo -e "\033[31mCould not download PGP public key for verification\033[0m"
        exit 1
    fi
fi

# Verifying the PHP Archive
gpg --verify sodium-compat.phar.sig sodium-compat.phar
```

Now, simply include this .phar file in your application.

```php
<?php
require_once "/path/to/sodium-compat.phar";
```

# Support

[Commercial support for libsodium](https://download.libsodium.org/doc/commercial_support/) is available
from multiple vendors. If you need help using sodium_compat in one of your projects, [contact Paragon Initiative Enterprises](https://paragonie.com/contact). 

Non-commercial report will be facilitated through [Github issues](https://github.com/paragonie/sodium_compat/issues).
We offer no guarantees of our availability to resolve questions about integrating sodium_compat into third-party
software for free, but will strive to fix any bugs (security-related or otherwise) in our library.

# Using Sodium Compat

## True Polyfill

If you're using PHP 5.3.0 or newer and do not have the PECL extension installed,
you can just use the [standard ext/sodium API features as-is](https://paragonie.com/book/pecl-libsodium)
and the polyfill will work its magic.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = \Sodium\crypto_sign_keypair();
$alice_sk = \Sodium\crypto_sign_secretkey($alice_kp);
$alice_pk = \Sodium\crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = \Sodium\crypto_sign_detached($message, $alice_sk);
if (\Sodium\crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

The polyfill does not expose this API on PHP < 5.3, or if you have the PHP
extension installed already.

Since this doesn't require a namespace, this API *is* exposed on PHP 5.2.

## General-Use Polyfill

If your users are on PHP < 5.3, or you want to write code that will work
whether or not the PECL extension is available, you'll want to use the
**`ParagonIE_Sodium_Compat`** class for most of your libsodium needs.

The above example, written for general use:

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = ParagonIE_Sodium_Compat::crypto_sign_keypair();
$alice_sk = ParagonIE_Sodium_Compat::crypto_sign_secretkey($alice_kp);
$alice_pk = ParagonIE_Sodium_Compat::crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = ParagonIE_Sodium_Compat::crypto_sign_detached($message, $alice_sk);
if (ParagonIE_Sodium_Compat::crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

Generally: If you replace `\Sodium\ ` with `ParagonIE_Sodium_Compat::`, any
code already written for the libsodium PHP extension should work with our
polyfill without additional code changes.

Since version 0.7.0, we have our own namespaced API (`ParagonIE\Sodium\*`) to allow brevity
in software that uses PHP 5.3+. This is useful if you want to use our file cryptography 
features without writing `ParagonIE_Sodium_File` every time. This is not exposed on PHP < 5.3,
so if your project supports PHP < 5.3, use the underscore method instead.

To learn how to use Libsodium, read [*Using Libsodium in PHP Projects*](https://paragonie.com/book/pecl-libsodium).

## PHP 7.2 Polyfill

As per the [second vote on the libsodium RFC](https://wiki.php.net/rfc/libsodium#proposed_voting_choices),
PHP 7.2 uses `sodium_*` instead of `\Sodium\*`.

```php
<?php
require_once "/path/to/sodium_compat/autoload.php";

$alice_kp = sodium_crypto_sign_keypair();
$alice_sk = sodium_crypto_sign_secretkey($alice_kp);
$alice_pk = sodium_crypto_sign_publickey($alice_kp);

$message = 'This is a test message.';
$signature = sodium_crypto_sign_detached($message, $alice_sk);
if (sodium_crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
```

## Help, Sodium_Compat is Slow! How can I make it fast?

There are three ways to make it fast:

1. Use PHP 7.2.
2. [Install the libsodium PHP extension from PECL](https://paragonie.com/book/pecl-libsodium/read/00-intro.md#installing-libsodium).
3. Only if the previous two options are not available for you:
   1. Verify that [the processor you're using actually implements constant-time multiplication](https://bearssl.org/ctmul.html).
      Sodium_compat does, but it must trade some speed in order to attain cross-platform security.
   2. Only if you are 100% certain that your processor is safe, you can set `ParagonIE_Sodium_Compat::$fastMult = true;`
      without harming the security of your cryptography keys. If your processor *isn't* safe, then decide whether you
      want speed or security because you can't have both.

### Help, my PHP only has 32-Bit Integers! It's super slow!

Some features of sodium_compat are ***incredibly slow* with PHP 5 on Windows**
(in particular: public-key cryptography (encryption and signatures) is
affected), and there is nothing we can do about that, due to platform
restrictions on integers.

For acceptable performance, we highly recommend Windows users to version 1.0.6
of the libsodium extension from PECL or. Alternatively, simply upgrade to PHP 7
and the slowdown will be greatly reduced.

This is also true of non-Windows 32-bit operating systems, or if somehow PHP
was compiled where `PHP_INT_SIZE` equals `4` instead of `8`.

## API Coverage

* Mainline NaCl Features
    * `crypto_auth()`
    * `crypto_auth_verify()`
    * `crypto_box()`
    * `crypto_box_open()`
    * `crypto_scalarmult()`
    * `crypto_secretbox()`
    * `crypto_secretbox_open()`
    * `crypto_sign()`
    * `crypto_sign_open()`
* PECL Libsodium Features
    * `crypto_aead_aes256gcm_encrypt()`
    * `crypto_aead_aes256gcm_decrypt()`
    * `crypto_aead_chacha20poly1305_encrypt()`
    * `crypto_aead_chacha20poly1305_decrypt()`
    * `crypto_aead_chacha20poly1305_ietf_encrypt()`
    * `crypto_aead_chacha20poly1305_ietf_decrypt()`
    * `crypto_aead_xchacha20poly1305_ietf_encrypt()`
    * `crypto_aead_xchacha20poly1305_ietf_decrypt()`
    * `crypto_box_xchacha20poly1305()`
    * `crypto_box_xchacha20poly1305_open()`
    * `crypto_box_seal()`
    * `crypto_box_seal_open()`
    * `crypto_generichash()`
    * `crypto_generichash_init()`
    * `crypto_generichash_update()`
    * `crypto_generichash_final()`
    * `crypto_kx()`
    * `crypto_secretbox_xchacha20poly1305()`
    * `crypto_secretbox_xchacha20poly1305_open()`
    * `crypto_shorthash()`
    * `crypto_sign_detached()`
    * `crypto_sign_ed25519_pk_to_curve25519()`
    * `crypto_sign_ed25519_sk_to_curve25519()`
    * `crypto_sign_verify_detached()`
    * For advanced users only:
        * `crypto_stream()`
        * `crypto_stream_xor()`
    * Other utilities (e.g. `crypto_*_keypair()`)

### Cryptography Primitives Provided

* **X25519** - Elliptic Curve Diffie Hellman over Curve25519
* **Ed25519** - Edwards curve Digital Signature Algorithm over Curve25519
* **Xsalsa20** - Extended-nonce Salsa20 stream cipher
* **ChaCha20** - Stream cipher
* **Xchacha20** - Extended-nonce ChaCha20 stream cipher
* **Poly1305** - Polynomial Evaluation Message Authentication Code modulo 2^130 - 5
* **BLAKE2b** - Cryptographic Hash Function
* **SipHash-2-4** - Fast hash, but not collision-resistant; ideal for hash tables.

### Features Excluded from this Polyfill

* `\Sodium\memzero()` - Although we expose this API endpoint, we can't reliably
  zero buffers from PHP.
  
  If you have the PHP extension installed, sodium_compat
  will use the native implementation to zero out the string provided. Otherwise
  it will throw a `SodiumException`.
* `\Sodium\crypto_pwhash()` - It's not feasible to polyfill scrypt or Argon2
  into PHP and get reasonable performance. Users would feel motivated to select
  parameters that downgrade security to avoid denial of service (DoS) attacks.
  
  The only winning move is not to play.
  
  If ext/sodium or ext/libsodium is installed, these API methods will fallthrough
  to the extension. Otherwise, our polyfill library will throw a `SodiumException`.
  
  To detect support for Argon2i at runtime, use
  `ParagonIE_Sodium_Compat::crypto_pwhash_is_available()`, which returns a
   boolean value (`TRUE` or `FALSE`).

