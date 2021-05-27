Changelog
=========

1.8.0
-----

### IMPORTANT NOTES

#### Last release supporting PHP 5.2 - 5.5

  Release 1.8.0 will be the last release with compatibility for PHP 5.2 - 5.5. With the next release (v2.0.0), the minimum PHP version will be bumped to 5.6.

#### Last release supporting PEAR distribution

  Release 1.8.0 will be the last release to be distributed via PEAR. From release 2.0.0 onwards, consumers of this library will have to switch to Composer to receive updates.

### Overview of changes

- **[SECURITY FIX] Disable deserialization in `FilteredIterator`**

  A `Deserialization of Untrusted Data` weakness was found in the `FilteredIterator` class.
  
  This security vulnerability was first reported to the WordPress project. The security fix applied to WordPress has been ported back into the library.
  
  GitHub security advisory: [CVE-2021-29476 - Deserialization of Untrusted Data](https://cve.mitre.org/cgi-bin/cvename.cgi?name=2021-29476)

  Related WordPress CVE: [https://cve.mitre.org/cgi-bin/cvename.cgi?name=2020-28032](https://cve.mitre.org/cgi-bin/cvename.cgi?name=2020-28032)

  (props [@dd32][gh-dd32], [@desrosj][gh-desrosj], [@jrfnl][gh-jrfnl], [@peterwilsoncc][gh-peterwilsoncc], [@SergeyBiryukov][gh-SergeyBiryukov], [@whyisjake][gh-whyisjake], [@xknown][gh-xknown], [#421][gh-421], [#422][gh-422])


- **Repository moved to `WordPress\Requests`**

  The `Requests` library has been moved to the WordPress GitHub organization and can now be found under `https://github.com/WordPress/Requests`.
  
  All links in code and documentation were updated accordingly.

  Note: the Composer package name remains unchanged ([`rmccue/requests`](https://packagist.org/packages/rmccue/requests)), as well as the documentation site ([requests.ryanmccue.info](https://requests.ryanmccue.info/)).

  (props [@dd32][gh-dd32], [@JustinyAhin][gh-JustinyAhin], [@jrfnl][gh-jrfnl], [@rmccue][gh-rmccue], [#440][gh-440], [#441][gh-441], [#448][gh-448])


- **Manage `"Expect"` header with `cURL` transport**

  By default, `cURL` adds a `Expect: 100-Continue` header to certain requests. This can add as much as a second delay to requests done using `cURL`. This is [discussed on the cURL mailing list](https://curl.se/mail/lib-2017-07/0013.html).

  To prevent this, `Requests` now adds an empty `"Expect"` header to requests that are smaller than 1 MB and use HTTP/1.1.

  (props [@carlalexander][gh-carlalexander], [@schlessera][gh-schlessera], [@TimothyBJacobs][gh-TimothyBJacobs], [#453][gh-453], [#454][gh-454], [#469][gh-469])


- **Update bundled certificates as of 2021-02-12**

  The bundled certificates were updated. A small subset of expired certificates are still included for legacy reasons (and support).

  (props [@ozh][gh-ozh], [@patmead][gh-patmead], [@schlessera][gh-schlessera], [@todeveni][gh-todeveni], [#385][gh-385], [#398][gh-398], [#451][gh-451])


- **Add required `Content-*` headers for empty `POST` requests**

  Sends the `Content-Length` and `Content-Type` headers even for empty `POST` requests, as the length is expected as per [RFC2616 Section 14.13](https://tools.ietf.org/html/rfc2616#section-14.13):
  ```
  Content-Length header "SHOULD" be included. In practice, it is not
  used for GET nor HEAD requests, but is expected for POST requests.
  ```

  (props [@dd32][gh-dd32], [@gstrauss][gh-gstrauss], [@jrfnl][gh-jrfnl], [@soulseekah][gh-soulseekah], [#248][gh-248], [#249][gh-249], [#318][gh-318], [#368][gh-368])


- **Ignore locale when creating the HTTP version string from a float**

  The previous behavior allowed for the locale to mess up the float to string conversion resulting in a `GET / HTTP/1,1` instead of `GET / HTTP/1.1` request.

  (props [@tonebender][gh-tonebender], [@Zegnat][gh-Zegnat], [#335][gh-335], [#339][gh-339])


- **Make `verify => false` work with `fsockopen`**

  This allows the `fsockopen` transport now to ignore SSL failures when requested.
  
  (props [@soulseekah][gh-soulseekah], [#310][gh-310], [#311][gh-311])


- **Only include port number in the `Host` header if it differs from the default**

  The code was not violating the RFC per se, but also not following standard practice of leaving the port off when it is the default port for the scheme, which could lead to connectivity issues.

  (props [@amandato][gh-amandato], [@dd32][gh-dd32], [#238][gh-238])


- **Fix PHP cross-version compatibility**

  Important fixes have been made to improve cross-version compatibility of the code across all supported PHP versions.

  - Use documented order for `implode()` arguments.
  - Harden type handling when no domain was passed.
  - Explicitly cast `$url` property to `string` in `Requests::parse_response()`.
  - Initialize `$body` property to an empty string in `Requests::parse_response()`.
  - Ensure the stream handle is valid before trying to close it.
  - Ensure the `$callback` in the `FilteredIterator` is callable before calling it.

  (props [@aaronjorbin][gh-aaronjorbin], [@jrfnl][gh-jrfnl], [#346][gh-346], [#370][gh-370], [#425][gh-425], [#426][gh-426], [#456][gh-456], [#457][gh-457])


- **Improve testing**
  
  Lots of improvements were made to render the tests more reliable and increase the coverage.

  And to top it all off, all tests are now run against all supported PHP versions, including PHP 8.0.

  (props [@datagutten][gh-datagutten], [@jrfnl][gh-jrfnl], [@schlessera][gh-schlessera], [#345][gh-345], [#351][gh-351], [#355][gh-355], [#366][gh-366], [#412][gh-412], [#414][gh-414], [#445][gh-445], [#458][gh-458], [#464][gh-464])


- **Improve code quality and style**
  
  A whole swoop of changes has been made to harden the code and make it more consistent.

  The code style has been made consistent across both code and tests and is now enforced via a custom PHPCS rule set.

  The WordPress Coding Standards were chosen as the basis for the code style checks as most contributors to this library originate from the WordPress community and will be familiar with this code style.

  Main differences from the WordPress Coding Standards based on discussions and an analysis of the code styles already in use:

  - No whitespace on the inside of parentheses.
  - No Yoda conditions.

  A more detailed overview of the decisions that went into the final code style rules can be found at [#434][gh-434].

  (props [@jrfnl][gh-jrfnl], [@KasperFranz][gh-KasperFranz], [@ozh][gh-ozh], [@schlessera][gh-schlessera], [@TysonAndre][gh-TysonAndre], [#263][gh-263], [#296][gh-296], [#328][gh-328], [#358][gh-358], [#359][gh-359], [#360][gh-360], [#361][gh-361], [#362][gh-362], [#363][gh-363], [#364][gh-364], [#386][gh-386], [#396][gh-396], [#399][gh-399], [#400][gh-400], [#401][gh-401], [#402][gh-402], [#403][gh-403], [#404][gh-404], [#405][gh-405], [#406][gh-406], [#408][gh-408], [#409][gh-409], [#410][gh-410], [#411][gh-411], [#413][gh-413], [#415][gh-415], [#416][gh-416], [#417][gh-417], [#423][gh-423], [#424][gh-424], [#434][gh-434])


- **Replace Travis CI with GitHub Actions (partial)**
  
  The entire CI setup is gradually being moved from Travis CI to GitHub Actions. 
  
  At this point, GitHub Actions takes over the CI from PHP 5.5 onwards, leaving Travis CI as a fallback for lower PHP versions.

  This move will be completed after the planned minimum version bump to PHP 5.6+ with the next release, at which point we will get rid of all the remaining Travis CI integrations.

  (props [@dd32][gh-dd32], [@desrosj][gh-desrosj], [@jrfnl][gh-jrfnl], [@ntwb][gh-ntwb], [@ozh][gh-ozh], [@schlessera][gh-schlessera], [@TimothyBJacobs][gh-TimothyBJacobs], [@TysonAndre][gh-TysonAndre], [#280][gh-280], [#298][gh-298], [#302][gh-302], [#303][gh-303], [#352][gh-352], [#353][gh-353], [#354][gh-354], [#356][gh-356], [#388][gh-388], [#397][gh-397], [#428][gh-428], [#436][gh-436], [#439][gh-439], [#461][gh-461], [#467][gh-467])


- **Update and improve documentation**
  - Use clearer and more inclusive language.
  - Update the GitHub Pages site.
  - Update content and various tweaks to the markdown.
  - Fix code blocks in `README.md` file.
  - Add pagination to documentation pages.

  (props [@desrosj][gh-desrosj], [@jrfnl][gh-jrfnl], [@JustinyAhin][gh-JustinyAhin], [@tnorthcutt][gh-tnorthcutt], [#334][gh-334], [#367][gh-367], [#387][gh-387], [#443][gh-443], [#462][gh-462], [#465][gh-465], [#468][gh-468], [#471][gh-471] )

[gh-194]: https://github.com/WordPress/Requests/issues/194
[gh-238]: https://github.com/WordPress/Requests/issues/238
[gh-248]: https://github.com/WordPress/Requests/issues/248
[gh-249]: https://github.com/WordPress/Requests/issues/249
[gh-263]: https://github.com/WordPress/Requests/issues/263
[gh-280]: https://github.com/WordPress/Requests/issues/280
[gh-296]: https://github.com/WordPress/Requests/issues/296
[gh-298]: https://github.com/WordPress/Requests/issues/298
[gh-302]: https://github.com/WordPress/Requests/issues/302
[gh-303]: https://github.com/WordPress/Requests/issues/303
[gh-310]: https://github.com/WordPress/Requests/issues/310
[gh-311]: https://github.com/WordPress/Requests/issues/311
[gh-318]: https://github.com/WordPress/Requests/issues/318
[gh-328]: https://github.com/WordPress/Requests/issues/328
[gh-334]: https://github.com/WordPress/Requests/issues/334
[gh-335]: https://github.com/WordPress/Requests/issues/335
[gh-339]: https://github.com/WordPress/Requests/issues/339
[gh-345]: https://github.com/WordPress/Requests/issues/345
[gh-346]: https://github.com/WordPress/Requests/issues/346
[gh-351]: https://github.com/WordPress/Requests/issues/351
[gh-352]: https://github.com/WordPress/Requests/issues/352
[gh-353]: https://github.com/WordPress/Requests/issues/353
[gh-354]: https://github.com/WordPress/Requests/issues/354
[gh-355]: https://github.com/WordPress/Requests/issues/355
[gh-356]: https://github.com/WordPress/Requests/issues/356
[gh-358]: https://github.com/WordPress/Requests/issues/358
[gh-359]: https://github.com/WordPress/Requests/issues/359
[gh-360]: https://github.com/WordPress/Requests/issues/360
[gh-361]: https://github.com/WordPress/Requests/issues/361
[gh-362]: https://github.com/WordPress/Requests/issues/362
[gh-363]: https://github.com/WordPress/Requests/issues/363
[gh-364]: https://github.com/WordPress/Requests/issues/364
[gh-366]: https://github.com/WordPress/Requests/issues/366
[gh-367]: https://github.com/WordPress/Requests/issues/367
[gh-367]: https://github.com/WordPress/Requests/issues/367
[gh-368]: https://github.com/WordPress/Requests/issues/368
[gh-370]: https://github.com/WordPress/Requests/issues/370
[gh-385]: https://github.com/WordPress/Requests/issues/385
[gh-386]: https://github.com/WordPress/Requests/issues/386
[gh-387]: https://github.com/WordPress/Requests/issues/387
[gh-388]: https://github.com/WordPress/Requests/issues/388
[gh-396]: https://github.com/WordPress/Requests/issues/396
[gh-397]: https://github.com/WordPress/Requests/issues/397
[gh-398]: https://github.com/WordPress/Requests/issues/398
[gh-399]: https://github.com/WordPress/Requests/issues/399
[gh-400]: https://github.com/WordPress/Requests/issues/400
[gh-401]: https://github.com/WordPress/Requests/issues/401
[gh-402]: https://github.com/WordPress/Requests/issues/402
[gh-403]: https://github.com/WordPress/Requests/issues/403
[gh-404]: https://github.com/WordPress/Requests/issues/404
[gh-405]: https://github.com/WordPress/Requests/issues/405
[gh-406]: https://github.com/WordPress/Requests/issues/406
[gh-408]: https://github.com/WordPress/Requests/issues/408
[gh-409]: https://github.com/WordPress/Requests/issues/409
[gh-410]: https://github.com/WordPress/Requests/issues/410
[gh-411]: https://github.com/WordPress/Requests/issues/411
[gh-412]: https://github.com/WordPress/Requests/issues/412
[gh-413]: https://github.com/WordPress/Requests/issues/413
[gh-414]: https://github.com/WordPress/Requests/issues/414
[gh-415]: https://github.com/WordPress/Requests/issues/415
[gh-416]: https://github.com/WordPress/Requests/issues/416
[gh-417]: https://github.com/WordPress/Requests/issues/417
[gh-421]: https://github.com/WordPress/Requests/issues/421
[gh-422]: https://github.com/WordPress/Requests/issues/422
[gh-423]: https://github.com/WordPress/Requests/issues/423
[gh-424]: https://github.com/WordPress/Requests/issues/424
[gh-425]: https://github.com/WordPress/Requests/issues/425
[gh-426]: https://github.com/WordPress/Requests/issues/426
[gh-428]: https://github.com/WordPress/Requests/issues/428
[gh-434]: https://github.com/WordPress/Requests/issues/434
[gh-436]: https://github.com/WordPress/Requests/issues/436
[gh-439]: https://github.com/WordPress/Requests/issues/439
[gh-440]: https://github.com/WordPress/Requests/issues/440
[gh-441]: https://github.com/WordPress/Requests/issues/441
[gh-443]: https://github.com/WordPress/Requests/issues/443
[gh-445]: https://github.com/WordPress/Requests/issues/445
[gh-448]: https://github.com/WordPress/Requests/issues/448
[gh-451]: https://github.com/WordPress/Requests/issues/451
[gh-453]: https://github.com/WordPress/Requests/issues/453
[gh-454]: https://github.com/WordPress/Requests/issues/454
[gh-456]: https://github.com/WordPress/Requests/issues/456
[gh-457]: https://github.com/WordPress/Requests/issues/457
[gh-458]: https://github.com/WordPress/Requests/issues/458
[gh-461]: https://github.com/WordPress/Requests/issues/461
[gh-462]: https://github.com/WordPress/Requests/issues/462
[gh-464]: https://github.com/WordPress/Requests/issues/464
[gh-465]: https://github.com/WordPress/Requests/issues/465
[gh-467]: https://github.com/WordPress/Requests/issues/467
[gh-468]: https://github.com/WordPress/Requests/issues/468
[gh-469]: https://github.com/WordPress/Requests/issues/469
[gh-471]: https://github.com/WordPress/Requests/issues/471

1.7.0
-----

- Add support for HHVM and PHP 7

  Requests is now tested against both HHVM and PHP 7, and they are supported as
  first-party platforms.

  (props [@rmccue][gh-rmccue], [#106][gh-106], [#176][gh-176])

- Transfer & connect timeouts, in seconds & milliseconds

  cURL is unable to handle timeouts under a second in DNS lookups, so we round
  those up to ensure 1-999ms isn't counted as an instant failure.

  (props [@ozh][gh-ozh], [@rmccue][gh-rmccue], [#97][gh-97], [#216][gh-216])

- Rework cookie handling to be more thorough.

  Cookies are now restricted to the same-origin by default, expiration is checked.

  (props [@catharsisjelly][gh-catharsisjelly], [@rmccue][gh-rmccue], [#120][gh-120], [#124][gh-124], [#130][gh-130], [#132][gh-132], [#156][gh-156])

- Improve testing

  Tests are now run locally to speed them up, as well as further general
  improvements to the quality of the testing suite. There are now also
  comprehensive proxy tests to ensure coverage there.

  (props [@rmccue][gh-rmccue], [#75][gh-75], [#107][gh-107], [#170][gh-170], [#177][gh-177], [#181][gh-181], [#183][gh-183], [#185][gh-185], [#196][gh-196], [#202][gh-202], [#203][gh-203])

- Support custom HTTP methods

  Previously, custom HTTP methods were only supported on sockets; they are now
  supported across all transports.

  (props [@ocean90][gh-ocean90], [#227][gh-227])

- Add byte limit option

  (props [@rmccue][gh-rmccue], [#172][gh-172])

- Support a Requests_Proxy_HTTP() instance for the proxy setting.

  (props [@ocean90][gh-ocean90], [#223][gh-223])

- Add progress hook

  (props [@rmccue][gh-rmccue], [#180][gh-180])

- Add a before_redirect hook to alter redirects

  (props [@rmccue][gh-rmccue], [#205][gh-205])

- Pass cURL info to after_request

  (props [@rmccue][gh-rmccue], [#206][gh-206])

- Remove explicit autoload in Composer installation instructions

  (props [@SlikNL][gh-SlikNL], [#86][gh-86])

- Restrict CURLOPT_PROTOCOLS on `defined()` instead of `version_compare()`

  (props [@ozh][gh-ozh], [#92][gh-92])

- Fix doc - typo in "Authentication"

  (props [@remik][gh-remik], [#99][gh-99])

- Contextually check for a valid transport

  (props [@ozh][gh-ozh], [#101][gh-101])

- Follow relative redirects correctly

  (props [@ozh][gh-ozh], [#103][gh-103])

- Use cURL's version_number

  (props [@mishan][gh-mishan], [#104][gh-104])

- Removed duplicated option docs

  (props [@staabm][gh-staabm], [#112][gh-112])

- code styling fixed

  (props [@imsaintx][gh-imsaintx], [#113][gh-113])

- Fix IRI "normalization"

  (props [@ozh][gh-ozh], [#128][gh-128])

- Mention two PHP extension dependencies in the README.

  (props [@orlitzky][gh-orlitzky], [#136][gh-136])

- Ignore coverage report files

  (props [@ozh][gh-ozh], [#148][gh-148])

- drop obsolete "return" after throw

  (props [@staabm][gh-staabm], [#150][gh-150])

- Updated exception message to specify both http + https

  (props [@beutnagel][gh-beutnagel], [#162][gh-162])

- Sets `stream_headers` method to public to allow calling it from other
places.

  (props [@adri][gh-adri], [#158][gh-158])

- Remove duplicated stream_get_meta_data call

  (props [@rmccue][gh-rmccue], [#179][gh-179])

- Transmits $errno from stream_socket_client in exception

  (props [@laurentmartelli][gh-laurentmartelli], [#174][gh-174])

- Correct methods to use snake_case

  (props [@rmccue][gh-rmccue], [#184][gh-184])

- Improve code quality

  (props [@rmccue][gh-rmccue], [#186][gh-186])

- Update Build Status image

  (props [@rmccue][gh-rmccue], [#187][gh-187])

- Fix/Rationalize transports (v2)

  (props [@rmccue][gh-rmccue], [#188][gh-188])

- Surface cURL errors

  (props [@ifwe][gh-ifwe], [#194][gh-194])

- Fix for memleak and curl_close() never being called

  (props [@kwuerl][gh-kwuerl], [#200][gh-200])

- addex how to install with composer

  (props [@royopa][gh-royopa], [#164][gh-164])

- Uppercase the method to ensure compatibility

  (props [@rmccue][gh-rmccue], [#207][gh-207])

- Store default certificate path

  (props [@rmccue][gh-rmccue], [#210][gh-210])

- Force closing keep-alive connections on old cURL

  (props [@rmccue][gh-rmccue], [#211][gh-211])

- Docs: Updated HTTP links with HTTPS links where applicable

  (props [@ntwb][gh-ntwb], [#215][gh-215])

- Remove the executable bit

  (props [@ocean90][gh-ocean90], [#224][gh-224])

- Change more links to HTTPS

  (props [@rmccue][gh-rmccue], [#217][gh-217])

- Bail from cURL when either `curl_init()` OR `curl_exec()` are unavailable

  (props [@dd32][gh-dd32], [#230][gh-230])

- Disable OpenSSL's internal peer_name checking when `verifyname` is disabled.

  (props [@dd32][gh-dd32], [#239][gh-239])

- Only include the port number in the `Host` header when it differs from
default

  (props [@dd32][gh-dd32], [#238][gh-238])

- Respect port if specified for HTTPS connections

  (props [@dd32][gh-dd32], [#237][gh-237])

- Allow paths starting with a double-slash

  (props [@rmccue][gh-rmccue], [#240][gh-240])

- Fixes bug in rfc2616 #3.6.1 implementation.

  (props [@stephenharris][gh-stephenharris], [#236][gh-236], [#3][gh-3])

- CURLOPT_HTTPHEADER在php7接受空数组导致php-fpm奔溃

  (props [@qibinghua][gh-qibinghua], [#219][gh-219])

[gh-3]: https://github.com/WordPress/Requests/issues/3
[gh-75]: https://github.com/WordPress/Requests/issues/75
[gh-86]: https://github.com/WordPress/Requests/issues/86
[gh-92]: https://github.com/WordPress/Requests/issues/92
[gh-97]: https://github.com/WordPress/Requests/issues/97
[gh-99]: https://github.com/WordPress/Requests/issues/99
[gh-101]: https://github.com/WordPress/Requests/issues/101
[gh-103]: https://github.com/WordPress/Requests/issues/103
[gh-104]: https://github.com/WordPress/Requests/issues/104
[gh-106]: https://github.com/WordPress/Requests/issues/106
[gh-107]: https://github.com/WordPress/Requests/issues/107
[gh-112]: https://github.com/WordPress/Requests/issues/112
[gh-113]: https://github.com/WordPress/Requests/issues/113
[gh-120]: https://github.com/WordPress/Requests/issues/120
[gh-124]: https://github.com/WordPress/Requests/issues/124
[gh-128]: https://github.com/WordPress/Requests/issues/128
[gh-130]: https://github.com/WordPress/Requests/issues/130
[gh-132]: https://github.com/WordPress/Requests/issues/132
[gh-136]: https://github.com/WordPress/Requests/issues/136
[gh-148]: https://github.com/WordPress/Requests/issues/148
[gh-150]: https://github.com/WordPress/Requests/issues/150
[gh-156]: https://github.com/WordPress/Requests/issues/156
[gh-158]: https://github.com/WordPress/Requests/issues/158
[gh-162]: https://github.com/WordPress/Requests/issues/162
[gh-164]: https://github.com/WordPress/Requests/issues/164
[gh-170]: https://github.com/WordPress/Requests/issues/170
[gh-172]: https://github.com/WordPress/Requests/issues/172
[gh-174]: https://github.com/WordPress/Requests/issues/174
[gh-176]: https://github.com/WordPress/Requests/issues/176
[gh-177]: https://github.com/WordPress/Requests/issues/177
[gh-179]: https://github.com/WordPress/Requests/issues/179
[gh-180]: https://github.com/WordPress/Requests/issues/180
[gh-181]: https://github.com/WordPress/Requests/issues/181
[gh-183]: https://github.com/WordPress/Requests/issues/183
[gh-184]: https://github.com/WordPress/Requests/issues/184
[gh-185]: https://github.com/WordPress/Requests/issues/185
[gh-186]: https://github.com/WordPress/Requests/issues/186
[gh-187]: https://github.com/WordPress/Requests/issues/187
[gh-188]: https://github.com/WordPress/Requests/issues/188
[gh-194]: https://github.com/WordPress/Requests/issues/194
[gh-196]: https://github.com/WordPress/Requests/issues/196
[gh-200]: https://github.com/WordPress/Requests/issues/200
[gh-202]: https://github.com/WordPress/Requests/issues/202
[gh-203]: https://github.com/WordPress/Requests/issues/203
[gh-205]: https://github.com/WordPress/Requests/issues/205
[gh-206]: https://github.com/WordPress/Requests/issues/206
[gh-207]: https://github.com/WordPress/Requests/issues/207
[gh-210]: https://github.com/WordPress/Requests/issues/210
[gh-211]: https://github.com/WordPress/Requests/issues/211
[gh-215]: https://github.com/WordPress/Requests/issues/215
[gh-216]: https://github.com/WordPress/Requests/issues/216
[gh-217]: https://github.com/WordPress/Requests/issues/217
[gh-219]: https://github.com/WordPress/Requests/issues/219
[gh-223]: https://github.com/WordPress/Requests/issues/223
[gh-224]: https://github.com/WordPress/Requests/issues/224
[gh-227]: https://github.com/WordPress/Requests/issues/227
[gh-230]: https://github.com/WordPress/Requests/issues/230
[gh-236]: https://github.com/WordPress/Requests/issues/236
[gh-237]: https://github.com/WordPress/Requests/issues/237
[gh-238]: https://github.com/WordPress/Requests/issues/238
[gh-239]: https://github.com/WordPress/Requests/issues/239
[gh-240]: https://github.com/WordPress/Requests/issues/240

1.6.0
-----
- [Add multiple request support][#23] - Send multiple HTTP requests with both
  fsockopen and cURL, transparently falling back to synchronous when
  not supported.

- [Add proxy support][#70] - HTTP proxies are now natively supported via a
  [high-level API][docs/proxy]. Major props to Ozh for his fantastic work
  on this.

- [Verify host name for SSL requests][#63] - Requests is now the first and only
  standalone HTTP library to fully verify SSL hostnames even with socket
  connections. Thanks to Michael Adams, Dion Hulse, Jon Cave, and Pádraic Brady
  for reviewing the crucial code behind this.

- [Add cookie support][#64] - Adds built-in support for cookies (built entirely
  as a high-level API)

- [Add sessions][#62] - To compliment cookies, [sessions][docs/usage-advanced]
  can be created with a base URL and default options, plus a shared cookie jar.

- Add [PUT][#1], [DELETE][#3], and [PATCH][#2] request support

- [Add Composer support][#6] - You can now install Requests via the
  `rmccue/requests` package on Composer

[docs/proxy]: http://requests.ryanmccue.info/docs/proxy.html
[docs/usage-advanced]: http://requests.ryanmccue.info/docs/usage-advanced.html

[#1]: https://github.com/WordPress/Requests/issues/1
[#2]: https://github.com/WordPress/Requests/issues/2
[#3]: https://github.com/WordPress/Requests/issues/3
[#6]: https://github.com/WordPress/Requests/issues/6
[#9]: https://github.com/WordPress/Requests/issues/9
[#23]: https://github.com/WordPress/Requests/issues/23
[#62]: https://github.com/WordPress/Requests/issues/62
[#63]: https://github.com/WordPress/Requests/issues/63
[#64]: https://github.com/WordPress/Requests/issues/64
[#70]: https://github.com/WordPress/Requests/issues/70

[View all changes][https://github.com/WordPress/Requests/compare/v1.5.0...v1.6.0]

1.5.0
-----
Initial release!

[gh-aaronjorbin]: https://github.com/aaronjorbin
[gh-adri]: https://github.com/adri
[gh-amandato]: https://github.com/amandato
[gh-beutnagel]: https://github.com/beutnagel
[gh-carlalexander]: https://github.com/carlalexander
[gh-catharsisjelly]: https://github.com/catharsisjelly
[gh-datagutten]: https://github.com/datagutten
[gh-dd32]: https://github.com/dd32
[gh-desrosj]: https://github.com/desrosj
[gh-gstrauss]: https://github.com/gstrauss
[gh-ifwe]: https://github.com/ifwe
[gh-imsaintx]: https://github.com/imsaintx
[gh-JustinyAhin]: https://github.com/JustinyAhin
[gh-jrfnl]: https://github.com/jrfnl
[gh-KasperFranz]: https://github.com/KasperFranz
[gh-kwuerl]: https://github.com/kwuerl
[gh-laurentmartelli]: https://github.com/laurentmartelli
[gh-mishan]: https://github.com/mishan
[gh-ntwb]: https://github.com/ntwb
[gh-ocean90]: https://github.com/ocean90
[gh-orlitzky]: https://github.com/orlitzky
[gh-ozh]: https://github.com/ozh
[gh-patmead]: https://github.com/patmead
[gh-peterwilsoncc]: https://github.com/peterwilsoncc
[gh-qibinghua]: https://github.com/qibinghua
[gh-remik]: https://github.com/remik
[gh-rmccue]: https://github.com/rmccue
[gh-royopa]: https://github.com/royopa
[gh-schlessera]: https://github.com/schlessera
[gh-SergeyBiryukov]: https://github.com/SergeyBiryukov
[gh-SlikNL]: https://github.com/SlikNL
[gh-soulseekah]: https://github.com/soulseekah
[gh-staabm]: https://github.com/staabm
[gh-stephenharris]: https://github.com/stephenharris
[gh-TimothyBJacobs]: https://github.com/TimothyBJacobs
[gh-tnorthcutt]: https://github.com/tnorthcutt
[gh-todeveni]: https://github.com/todeveni
[gh-tonebender]: https://github.com/tonebender
[gh-TysonAndre]: https://github.com/TysonAndre
[gh-whyisjake]: https://github.com/whyisjake
[gh-xknown]: https://github.com/xknown
[gh-Zegnat]: https://github.com/Zegnat
