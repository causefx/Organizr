# Contributing to php-sparkpost
Transparency is one of our core values, and we encourage developers to contribute and become part of the SparkPost developer community.

The following is a set of guidelines for contributing to php-sparkpost,
which is hosted in the [SparkPost Organization](https://github.com/sparkpost) on GitHub.
These are just guidelines, not rules, use your best judgment and feel free to
propose changes to this document in a pull request.

## Submitting Issues
* You can create an issue [here](https://github.com/sparkpost/php-sparkpost/issues/new), but
  before doing that please read the notes below on debugging and submitting issues,
  and include as many details as possible with your report.
* Include the version of php-sparkpost you are using.
* Perform a [cursory search](https://github.com/SparkPost/php-sparkpost/issues?q=is%3Aissue+is%3Aopen)
  to see if a similar issue has already been submitted.

## Development

### Setup (Getting the Tools)
#### Install Composer
```
curl -sS https://getcomposer.org/installer | php
```

Add composer install directory to $PATH `~/.composer/vendor/bin/`

#### Install PHPUnit for Testing
```
composer global require "phpunit/phpunit=4.8.*"
```

We recommend increasing PHP’s memory limit, by default it uses 128MB.  We ran into some issues during local development without doing so.  You can do this by editing your php.ini file and modifying `memory_limit`.  We set ours to `memory_limit = 1024M`.

#### Install XDebug for code coverage generation
Follow the instructions at [xdebug.org](http://xdebug.org/wizard.php)

#### Development Tool Resources
* https://getcomposer.org/doc/00-intro.md#globally-on-osx-via-homebrew-
* https://phpunit.de/manual/current/en/installation.html

### Local Development
* Fork [this repository](http://github.com/SparkPost/php-sparkpost)
* Clone your fork
* Run `composer install`
* Write code!

### Contribution Steps

#### Guidelines

- Provide documentation for any newly added code.
- Provide tests for any newly added code.
- Follow [PSR-2](http://www.php-fig.org/psr/psr-2/) (_will be auto-enforced by php-cs-fixer in a later step_)

1. Create a new branch named after the issue you’ll be fixing (include the issue number as the branch name, example: Issue in GH is #8 then the branch name should be ISSUE-8)
1. Write corresponding tests and code (only what is needed to satisfy the issue and tests please)
    * Include your tests in the 'test' directory in an appropriate test file
    * Write code to satisfy the tests
1. Ensure automated tests pass
1. Run `composer run-script fix-style` to enforce PSR-2 style
1. Send a pull request and bug the maintainer until it gets merged and published. :) Make sure to add yourself to [AUTHORS](https://github.com/SparkPost/php-sparkpost/blob/master/AUTHORS.md).


### Testing
Once you are setup for local development:
* You can execute the unit tests using: `composer test`
* You can view coverage information by viewing: `open test/output/report/index.html`

## Releasing

* Update version information in composer.json during development.
* Once its been merged down, create a release tag in git.
* Composer will automatically pickup the new tag and present it as a release.
