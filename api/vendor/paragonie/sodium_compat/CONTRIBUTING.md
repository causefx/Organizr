# Contributing Guidelines

Thank you for your interest in contributing to sodium_compat. This document should help you get up and running.

## Branches

This project maintains two main branches:

* `master`: This is the active development branch for the latest major version of `sodium_compat`. 
  It only supports PHP 8.1 and newer..
* `v1.x`: This branch supports a wider range of PHP versions, from 5.2.x up to the latest. Many projects that need to
  support older version of PHP, such as WordPress, depend on the releases cut from the v1.x branch.

## Code Quality and Security

This is a cryptography library, so security is of the utmost importance.

We follow the principles of [Cryptographically Secure PHP Development](https://paragonie.com/blog/2017/02/cryptographically-secure-php-development).
Before contributing, please read the linked blog post.

## Scope of Contributions

This library is a polyfill for [libsodium](https://github.com/jedisct1/libsodium). Therefore, we will only consider
contributions that implement features already present in libsodium proper.

### Reporting Security Vulnerabilities

Please email `security at paragonie dot com` to disclose a security issue with sodium_compat. If you are reporting a
cryptographic weakness that also applies to libsodium, please disclose it upstream to libsodium first.

## Testing

### Unit Testing

Before submitting a pull request, please ensure that all unit tests pass.

You can run the tests using Composer:

```terminal
composer test
```

If you want to go the extra mile (at the cost of a longer test runtime), run the pedantic tests too:

```terminal
vendor/bin/phpunit --bootstrap=autoload-pedantic.php
```

Paragon Initiative Enterprises runs the pedantic tests before every release is cut.

### Mutation Testing

We use [Infection](https://infection.github.io) for mutation testing.

```bash
composer mutation-test
```

Please be aware that this command can take a long time to complete, and will generate some false positives.

### Fuzz Testing

We use [Nikita Popov's PHP-Fuzzer](https://github.com/nikic/PHP-Fuzzer) for fuzz testing. To run the fuzz tests, use the
following command:

```bash
composer fuzz-test
```

This command runs for a long time. To limit the fuzzer to 1000 runs:

```bash
vendor/bin/php-fuzzer --max-runs 1000 fuzz fuzzing/FuzzSodiumCompat.php
```
