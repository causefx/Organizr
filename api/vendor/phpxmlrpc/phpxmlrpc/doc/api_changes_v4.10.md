API Changes between library versions 4.9 and 4.10
=================================================

While 4.10 keeps the usual BC promise with previous 4.x releases, it also paves the way for all the API changes foreseen
to happen in future version 5.

In particular, API cleanups mean that most public access to object properties has been replaced by dedicated methods.

New classes, traits, exceptions, interfaces
-------------------------------------------

For the first time, usage of custom exceptions is in place. Traits are also in use for sharing common functionality.

| Type      | Name                          | Notes                                                               |
|-----------|-------------------------------|---------------------------------------------------------------------|
| exception | PhpXmlRpc\Exception           | Parent class for all exceptions thrown by the library.              |
|           |                               | Its alias PhpXmlRpc\Exception\PhpXmlRpcException is still in place. |
| exception | PhpXmlRpc\Exception\...       | See the list in the appendix of the user manual                     |
| trait     | PhpXmlRpc\CharsetEncoderAware |                                                                     |
| trait     | PhpXmlRpc\Server              |                                                                     |
| trait     | PhpXmlRpc\DeprecationLogger   |                                                                     |
| trait     | PhpXmlRpc\ParserAware         |                                                                     |
| trait     | PhpXmlRpc\PayloadBearer       |                                                                     |

New class methods
-----------------

In case you had extended the classes of the library and added methods to the subclasses, you might find that your
implementation clashes with the new one if you implemented:

| Class     | Method              | Notes     |
|-----------|---------------------|-----------|
| *         | __get               |           |
| *         | __isset             |           |
| *         | __set               |           |
| *         | __unset             |           |
| Charset   | getLogger           |           |
| Charset   | knownCharsets       |           |
| Charset   | setLogger           | static    |
| Client    | getOption           |           |
| Client    | getOptions          |           |
| Client    | getUrl              |           |
| Client    | setOption           |           |
| Client    | setOptions          |           |
| Http      | getLogger           |           |
| Http      | parseAcceptHeader   |           |
| Http      | setLogger           | static    |
| Logger    | debug               |           |
| Logger    | error               |           |
| Logger    | warning             |           |
| PhpXmlRpc | setLogger           | static    |
| PhpXmlRpc | useInteropFaults    | static    |
| Request   | getContentType      |           |
| Request   | getPayload          |           |
| Response  | getContentType      |           |
| Response  | getPayload          |           |
| Response  | valueType           |           |
| Response  | xml_header          |           |
| Server    | addToMap            |           |
| Server    | getOption           |           |
| Server    | getOptions          |           |
| Server    | setDispatchMap      |           |
| Server    | setOption           |           |
| Server    | setOptions          |           |
| Wrapper   | getHeldObject       |           |
| Wrapper   | holdObject          |           |
| XMLParser | getLogger           |           |
| XMLParser | setLogger           | static    |
| XMLParser | truncateValueForLog | protected |

New class properties
--------------------

| Class     | Property | Default value | Notes     |
|-----------|----------|---------------|-----------|
| Client    | $timeout | 0             | protected |

New static properties
---------------------

| Class     | Property                      | Default value       | Notes                      |
|-----------|-------------------------------|---------------------|----------------------------|
| Client    | $options                      | see code            | protected                  |
| Client    | $requestClass                 | \PhpXmlRpc\Request  | protected                  |
| Client    | $responseClass                | \PhpXmlRpc\Response | protected                  |
| PhpXmlRpc | $xmlrpc_datetime_format       | see code            |                            |
| PhpXmlRpc | $xmlrpc_double_format         | see code            |                            |
| PhpXmlRpc | $xmlrpc_int_format            | see code            |                            |
| PhpXmlRpc | $xmlrpc_methodname_format     | see code            |                            |
| PhpXmlRpc | $xmlrpc_reject_invalid_values | false               |                            |
| PhpXmlRpc | $xmlrpc_return_datetimes      | false               |                            |
| PhpXmlRpc | $xmlrpc_silence_deprecations  | true                |                            |
| Server    | $options                      | see code            | protected                  |
| Server    | $responseClass                | \PhpXmlRpc\Response | protected. Added in 4.10.1 |
| Wrapper   | $namespace                    | \PhpXmlRpc\         | protected                  |

New constants
-------------

| Class  | Constant | Notes                 |
|--------|----------|-----------------------|
| Client | OPT_*    | see code for the list |
| Server | OPT_*    | see code for the list |

Changed methods
---------------

The following methods acquired new parameters, or accept a wider range of values for existing parameters

| Class     | Method           | Notes                                                              |
|-----------|------------------|--------------------------------------------------------------------|
| Client    | setDebug         | value -1 can now be used for $level                                |
| Date      | iso8601Encode    | a DateTimeInterface value is accepted for $timet                   |
| Server    | add_to_map       | new parameters: parametersType = false, $exceptionHandling = false |
| Wrapper   | wrapPhpClass     | $extraoptions accepts 'encode_nulls'                               |
| Wrapper   | wrapPhpFunction  | $extraoptions accepts 'encode_nulls'                               |
| Wrapper   | wrapXmlrpcMethod | $extraoptions accepts 'throw_on_fault'                             |
| Wrapper   | wrapXmlrpcMethod | $extraoptions accepts 'encode_nulls'                               |
| Wrapper   | wrapXmlrpcServer | $extraoptions accepts 'throw_on_fault'                             |
| Wrapper   | wrapXmlrpcServer | $extraoptions accepts 'encode_null'                                |
| XMLParser | __construct      | extra values accepted in $options (see code)                       |
| XMLParser | parse            | extra values accepted in $options (see code)                       |

The following methods have had some parameters deprecated

| Class     | Method    | Notes                                          |
|-----------|-----------|------------------------------------------------|
| Client    | send      | parameters $timeout and $method are deprecated |
| Client    | multicall | parameters $timeout and $method are deprecated |

The following methods have modified their return value

| Class     | Method         | Notes                               |
|-----------|----------------|-------------------------------------|
| Client    | _try_multicall | private                             |
| XMLParser | parse          | was: return void, now returns array |

Deprecated methods
------------------

| Class   | Method                | Replacement          |
|---------|-----------------------|----------------------|
| Charset | isValidCharset        | -                    |
| Client  | prepareCurlHandle     | createCURLHandle     |
| Client  | sendPayloadCurl       | sendViaCURL          |
| Client  | sendPayloadSocket     | sendViaSocket        |
| Client  | setCurlOptions        | setOption            |
| Client  | setRequestCompression | setOption            |
| Client  | setSSLVerifyHost      | setOption            |
| Client  | setSSLVerifyPeer      | setOption            |
| Client  | setSSLVersion         | setOption            |
| Client  | setUseCurl            | setOption            |
| Client  | setUserAgent          | setOption            |
| Server  | add_to_map            | addToMap             |
| Server  | xml_header            | Response::xml_header |
| Value   | serializeData         | -                    |

Deprecated properties
---------------------

The following properties have now protected access. Replacement accessor for public use are listed.

| Class     | Property                   | Read via               | Write via                        |
|-----------|----------------------------|------------------------|----------------------------------|
| Client    | accepted_charset_encodings | getOption              | setOption                        |
| Client    | accepted_compression       | getOption              | setOption/setAcceptedCompression |
| Client    | authtype                   | getOption              | setOption/setCredentials         |
| Client    | cacert                     | getOption              | setOption/setCaCertificate       |
| Client    | cacertdir                  | getOption              | setOption/setCaCertificate       |
| Client    | cert                       | getOption              | setOption/setCertificate         |
| Client    | certpass                   | getOption              | setOption/setCertificate         |
| Client    | cookies                    | getOption              | setOption                        |
| Client    | debug                      | getOption              | setOption/setDebug               |
| Client    | errno                      | -                      | -                                |
| Client    | errstr                     | -                      | -                                |
| Client    | extracurlopts              | getOption              | setOption                        |
| Client    | keepalive                  | getOption              | setOption                        |
| Client    | key                        | getOption              | setOption/setKey                 |
| Client    | keypass                    | getOption              | setOption/setKey                 |
| Client    | method                     | getUrl                 | __construct                      |
| Client    | no_multicall               | getOption              | setOption                        |
| Client    | password                   | getOption              | setOption/setCredentials         |
| Client    | path                       | getUrl                 | __construct                      |
| Client    | port                       | getUrl                 | __construct                      |
| Client    | proxy                      | getOption              | setOption/setProxy               |
| Client    | proxy_authtype             | getOption              | setOption/setProxy               |
| Client    | proxy_pass                 | getOption              | setOption/setProxy               |
| Client    | proxy_user                 | getOption              | setOption/setProxy               |
| Client    | proxyport                  | getOption              | setOption/setProxy               |
| Client    | request_charset_encoding   | getOption              | setOption                        |
| Client    | request_compression        | getOption              | setOption                        |
| Client    | return_type                | getOption              | setOption                        |
| Client    | server                     | getUrl                 | __construct                      |
| Client    | sslversion                 | getOption              | setOption                        |
| Client    | use_curl                   | getOption              | setOption                        |
| Client    | user_agent                 | getOption              | setOption                        |
| Client    | username                   | getOption              | setOption/setCredentials         |
| Client    | verifyhost                 | getOption              | setOption                        |
| Client    | verifypeer                 | getOption              | setOption                        |
| Request   | content_type               | getContentType         | setPayload                       |
| Request   | debug                      | setDebug               | -                                |
| Request   | methodname                 | method                 | __construct/method               |
| Request   | params                     | getParam               | __construct/addParam             |
| Request   | payload                    | getPayload             | setPayload                       |
| Response  | val                        | value                  | __construct                      |
| Response  | valtyp                     | valueType              | __construct                      |
| Response  | errno                      | faultCode              | __construct                      |
| Response  | errstr                     | faultString            | __construct                      |
| Response  | content_type               | getContentType         | setPayload                       |
| Response  | payload                    | getPayload             | setPayload                       |
| Server    | accepted_compression       | getOption              | setOption                        |
| Server    | allow_system_funcs         | getOption              | setOption                        |
| Server    | compress_response          | getOption              | setOption                        |
| Server    | debug                      | getOption              | setOption/setDebug               |
| Server    | exception_handling         | getOption              | setOption                        |
| Server    | functions_parameters_type  | getOption              | setOption                        |
| Server    | phpvals_encoding_options   | getOption              | setOption                        |
| Server    | response_charset_encoding  | getOption              | setOption                        |
| Value     | _php_class                 | -                      | -                                |
| Value     | me                         | scalarVal/array access | __construct                      |
| Value     | mytype                     | kindOf                 | __construct                      |
| Wrapper   | $objectholder              | getHeldObject          | holdObject                       |
| XMLParser | $_xh                       | results of parse()     | -                                |

The following previously protected properties are now deprecated for access by subclasses

| Class     | Property                   | Read via               | Write via                        |
|-----------|----------------------------|------------------------|----------------------------------|
| Request   | httpResponse               | -                      | -                                |
| Server    | accepted_charset_encodings | -                      | -                                |
