# Changelog

## 7.2.6

* Bump supported versions to include PHP8.2, 8.3

## 7.2.5

* Bump supported versions to include PHP8.2, 8.3.

## 7.2.4

* [Fixed] Timeout option is propagated to guzzle client

## 7.2.3

* [Fixed] Include socket_id in batch trigger if included.

## 7.2.2

* [FIXED] composer.phar file removed from package

## 7.2.1

* [FIXED] authenticateUser returns correct auth value

## 7.2.0

* [CHANGED] explicit support for 8.1
* [CHANGED] Ignore pull_request_template.md on vendor
* [ADDED] has_mixed_channels method to allow triggering a single event on multiple channels
* [FIXED] path option can be used for proxied servers under subdirectory.
* [CHANGED] base_path's leading slash is trimmed on every call to Guzzle.

## 7.1.0-beta

* [ADDED] `authenticateUser`, `authorizeChannel` and `authorizePresenceChannel`
* [ADDED] `sendToUser` and `sendToUserAsync`
* [ADDED] `terminateUserConnections` and `terminateUserConnectionsAsync`
* [FIXED] `get_object_vars()` error on `/src/Pusher.php`
* [DEPRECATED] `socketAuth` and `presenceAuth` in favour of `authorizeChannel` and `authorizePresenceChannel`
* [DEPRECATED] Internal functions `make_request` and `make_batch_request` that were exposed as public

## 7.0.2

* [CHANGED] Add psr/log v2.0 and v3.0 compatibility

## 7.0.1

* [FIXED] Infinite recursion in `presence_auth`.

## 7.0.0

* [DEPRECATED] `get_channel_info`, `get_channels`, `socket_auth`, `presence_auth` in favour of camelCased versions
* [DEPRECATED] `get_users_info` in favour of `getPresenceUsers`
* [DEPRECATED] `ensure_valid_signature` in favour of `verifySignature`
* [CHANGED] Restrict `$app_id` parameter of the `Pusher()` object to `string` (`int` was possible).
* [ADDED] Return types.
* [ADDED] Namespacing, PSR-12 formatting.

## 6.1.0

* [ADDED] triggerAsync and triggerBatchAsync using the Guzzle async interface.

## 6.0.1

* [CHANGED] Use type hints where possible (mixed type not available in PHP7).
* [CHANGED] Document that functions can throw GuzzleException.

## 6.0.0

* [CHANGED] internal HTTP client to Guzzle
* [ADDED] optional client parameter to constructor
* [CHANGED] useTLS is true by default
* [REMOVED] `curl_options` from options
* [REMOVED] customer logger
* [REMOVED] host, port and timeout constructor parameters
* [REMOVED] support for PHP 7.1
* [CHANGED] lower severity level of logging to DEBUG level

## 5.0.3

* [CHANGED] Ensure version in Pusher.php is bumped on release.

## 5.0.2

* [CHANGED] Add release automation actions.

## 5.0.1

* [FIXED] Notice raised due to reference to potentially missing object property in `trigger` method

## 5.0.0

* [CHANGED] The methods that make HTTP requests now throw an `ApiErrorException` instead of returning `false` for non-2xx responses
* [CHANGED] `trigger` now accepts a `$params` associative array instead of a `$socket_id` as the third parameter
* [ADDED] Support for requesting channel attributes as part of a `trigger` and `triggerBatch` request via an `info` parameter
* [REMOVED] `debug` parameter from methods that make HTTP requests and from the constructor options
* [REMOVED] Support for legacy push notifications (this has been superseded by https://github.com/pusher/push-notifications-php)

## 4.1.5

* [ADDED] Support for PHP 8.

## 4.1.4

* [FIXED] Errors in the failure path of `get_...` methods revealed by stricter type checking in PHP7.4

## 4.1.3

* No functional change, previous release was only partially successful

## 4.1.2

* [ADDED] option `encryption_master_key_base64`
* [DEPRECATED] option `encryption_master_key`

## 4.1.1

* [ADDED] Support for PHP 7.4.

## 4.1.0

* [ADDED] `path` configuration option.

## 4.0.0

* [REMOVED] Support for PHP 5.x, PHP 7.0 and HHVM.

## 3.4.1

* [ADDED] Support for PHP 7.3.

## 3.4.0

* [ADDED] `get_users_info` method.

## 3.3.1

* [FIXED] PHP Notice for Undefined `socket_id` in triggerBatch

## 3.3.0

* [ADDED] Support for End-to-end encrypted channels for triggerbatch
* [FIXED] trigger behavior with mixtures of encrypted and non-encrypted channels

## 3.2.0

* [ADDED] This release adds support for end to end encrypted channels, a new feature for Channels. Read more [in our docs](https://pusher.com/docs/client_api_guide/client_encrypted_channels).
* [DEPRECATED] Renamed `encrypted` option to `useTLS` - `encrypted` will still work!

## 3.1.0

* [ADDED] This release adds Webhook validation as well as a data structure to store Webhook payloads.

## 3.0.4

* [FIXED] Non zero indexed arrays of channels no longer get serialized as an object.

## 3.0.3

* [ADDED] PSR-3 logger compatibility.
* [CHANGED] Improved PHP docs.

## 3.0.2

* [FIXED] Insufficient check for un-initialized curl resource.
* [FIXED] Acceptance tests.

## 3.0.1

* [CHANGED] Info messages are now prefixed with INFO and errors are now prefixed with ERROR.

## 3.0.0

* [NEW] Added namespaces (thanks [@vinkla](https://github.com/vinkla)).

## 2.6.4

* [FIXED] Log the curl error in more circumstances

## 2.6.1

* [FIXED] Check for correct status code when POSTing to native push notifications API.

## 2.6.0

* [ADDED] support for publishing push notifications on up to 10 interests.

## 2.5.0

* [REMOVED] Native push notifications payload validation in the client.

## 2.5.0-rc2

* [FIXED] DDN and Native Push endpoints were not assembled correctly.

## 2.5.0-rc1

* [NEW] Native push notifications

## 2.4.2

* [CHANGED] One curl instance per Pusher instance

## 2.4.1

* [FIXED] Presence data could not be submitted after the style changes

## 2.4.0

* [ADDED] Support for batch events
* [ADDED] Curl options
* [FIXED] Applied fixes from StyleCI

## 2.3.0

* [ADDED] A new `cluster` option for the Pusher constructor.

## 2.2.2

* [FIXED] Fixed a PHP 5.2 incompatibility caused by referencing a private method in array_walk.

## 2.2.1

* [FIXED] Channel name and socket_id values are now validated.
* [BROKE] Inadvertently broke PHP 5.2 compatibility by referencing a private method in array_walk.

## 2.2.0

* [CHANGED] `new Pusher($app_key, $app_secret, $app_id, $options)` - The `$options` parameter
  has been added as the forth parameter to the constructor and other additional
  parameters are now deprecated.

## 2.1.3

* [NEW] `$pusher->trigger` can now take an `array` of channel names as a first parameter to allow the same event to be published on multiple channels.
* [NEW] `$pusher->get` generic function can be used to make `GET` calls to the REST API
* [NEW] `$pusher->set_logger` to allow internal logging to be exposed and logged in your own logs.

## 2.1.2

* [CHANGED] Debug response from `$pusher->trigger` call is now an associative array in the form `array( 'body' => '{String} body text of response', 'status' => '{Number} http status of the response' )`

## 2.1.1

* [CHANGED] Added optional $options parameter to get_channel_info. get_channel_info($channel, $options = array() )

## 2.1.0

* [CHANGED] Renamed get_channel_stats to get_channel_info
* [CHANGED] get_channels now takes and $options parameter. get_channels( $options = array() )
* [REMOVED] get_presence_channels

## 2.0.1

* [FIXED] Overwritten socket_id parameter in trigger: https://github.com/pusher/pusher-php-server/pull/3

## 2.0.0

* [NEW] Versioning introduced at 2.0.0
* [NEW] Added composer.json for submission to https://packagist.org/
* [CHANGED] `get_channels()` now returns an object which has a `channels` property. This must be accessed to get the Array of channels in an application.
* [CHANGED] `get_presence_channels()` now returns an object which has a `channels` property. This must be accessed to get the Array of channels in an application.
