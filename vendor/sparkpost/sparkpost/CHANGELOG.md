# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

## [2.1.0] - 2017-01-09
### Added
- [#161](https://github.com/SparkPost/php-sparkpost/pull/161) added example for sending email with attachment and simplified the examples setup
- [#159](https://github.com/SparkPost/php-sparkpost/pull/159) added `debug` option for seeing the full request sent to SparkPost
- [#151](https://github.com/SparkPost/php-sparkpost/pull/151) added packagist badges
- [#139](https://github.com/SparkPost/php-sparkpost/pull/139) added examples for message events and templates

### Changed
- [#150](https://github.com/SparkPost/php-sparkpost/issues/150) renamed the `Resource` class to `ResourceBase` since resource soft reserved in php 7
- [#137](https://github.com/SparkPost/php-sparkpost/pull/137) cleaned up tests and post install scripts
- [#138](https://github.com/SparkPost/php-sparkpost/pull/138) added PHP 7.1 as a test environment

### Fixed
- [#156](https://github.com/SparkPost/php-sparkpost/pull/156) fixed typo in README.md
- [#152](https://github.com/SparkPost/php-sparkpost/issues/152) fixed propagation of coverage tests to coveralls.io
- [#147](https://github.com/SparkPost/php-sparkpost/pull/147) fixed examples in README.md
- [#139](https://github.com/SparkPost/php-sparkpost/pull/139) fixed the ability to send using recipient lists
- Issue[#141](https://github.com/SparkPost/php-sparkpost/issues/141) removed form feeds from the JSON body sent to the API

## [2.0.3] - 2016-07-28
### Fixed
- Issue [#135](https://github.com/SparkPost/php-sparkpost/issues/135) reported `Http\Discovery\NotFoundException` caused by 2.0.2 update.

## [2.0.2] - 2016-07-28
### Fixed
- [#131](https://github.com/SparkPost/php-sparkpost/pull/131) removed any dependency on Guzzle by replacing it with `MessageFactoryDiscovery`


## [2.0.1] - 2016-06-29
### Fixed
- [#129](https://github.com/SparkPost/php-sparkpost/pull/129) issue with `content.from` being expected even when using a stored template

## [2.0.0] - 2016-06-24

This major release included a complete refactor of the library to be a thin HTTP client layer with some sugar methods on the Transmission class. There is now a base resource that can be used to call any SparkPost API with a one to one mapping of payload parameters to what is listed in our API documentation.

### Changed
- [#123](https://github.com/SparkPost/php-sparkpost/pull/123) Rewrote docs and updated composer name
- [#122](https://github.com/SparkPost/php-sparkpost/pull/122) Add transmission class and examples
- [#121](https://github.com/SparkPost/php-sparkpost/pull/121) Update base resource and tests

## [1.2.1] - 2016-05-27
### Fixed
- [#111](https://github.com/SparkPost/php-sparkpost/pull/111) allow pass through of timeout setting in http config

## [1.2.0] - 2016-05-04
### Added
- [EditorConfig](http://editorconfig.org/) file to maintain consistent coding style
- `composer run-script fix-style` can now be run to enforce PSR-2 style

### Changed
- Responses from the SparkPost API with HTTP status code 403 now properly raise with message, code, and description

### Fixed
- Removed reliance on composer for version of library

## [1.1.0] - 2016-05-02
### Added
- Message Events API added.

### Changed
- Transmission API now accepts a DateTime object for startDate

## [1.0.3] - 2016-03-25
### Added
- Support for attachments, inline attachments, inline css, sandbox, start time, and transactional options in `Transmission` class
- API response exceptions now include message, code, and description from API

## [1.0.2] - 2016-02-28
### Fixed
- Miscellaneous code cleanups related to docs and namespacing

## [1.0.1] - 2016-02-24
### Added
- Example for using `setupUnwrapped()` to get a list of webhooks.
- CHANGELOG.md for logging release updates and backfilled it with previous release.

### Fixed
- Library will now throw a `SparkPost\APIReponseException` properly when a 4XX http status is encountered.

## 1.0.0 - 2015-10-15
### Added
- Request adapter interface for passing in request adapters via `Ivory\HttpAdapter`
- Ability to create 'unwrapped' modules for API endpoints that haven't had functionality included yet.
- Instructions for setting up request adapters in README 

### Changed
- Library now requires PHP 5.5 or greater
- Updated interface to be instance based with referenceable objects rather than static functions.

### Fixed
- README now has proper code blocks denoting PHP language

[unreleased]: https://github.com/sparkpost/php-sparkpost/compare/2.1.0...HEAD
[2.1.0]: https://github.com/sparkpost/php-sparkpost/compare/2.0.3...2.1.0
[2.0.3]: https://github.com/sparkpost/php-sparkpost/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/sparkpost/php-sparkpost/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/sparkpost/php-sparkpost/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/sparkpost/php-sparkpost/compare/1.2.1...2.0.0
[1.2.1]: https://github.com/sparkpost/php-sparkpost/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/sparkpost/php-sparkpost/compare/v1.1.0...1.2.0
[1.1.0]: https://github.com/sparkpost/php-sparkpost/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/sparkpost/php-sparkpost/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/sparkpost/php-sparkpost/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/sparkpost/php-sparkpost/compare/v1.0.0...v1.0.1

