<?php
// by Edd Dumbill (C) 1999-2002
// <edd@usefulinc.com>

// Copyright (c) 1999,2000,2002 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

/******************************************************************************
 *
 * *** DEPRECATED ***
 *
 * This file is only used to insure backwards compatibility
 * with the API of the library <= rev. 3
 *
 * If it is included, the library will work without any further autoloading.
 *
 * NB: including this file will also alter the library configuration setting the
 * expected charset encoding used by the app to ISO-8859-1. Please see the
 * file api_changes_v4.md for how to change this if required.
 *****************************************************************************/

// the list is sorted more-or-less in dependency order
include_once(__DIR__.'/../src/Exception.php');
include_once(__DIR__.'/../src/Exception/FaultResponseException.php');
include_once(__DIR__.'/../src/Exception/ParsingException.php');
include_once(__DIR__.'/../src/Exception/XmlException.php');
include_once(__DIR__.'/../src/Exception/XmlRpcException.php');
include_once(__DIR__.'/../src/Exception/TransportException.php');
include_once(__DIR__.'/../src/Exception/HttpException.php');
include_once(__DIR__.'/../src/Exception/ServerException.php');
include_once(__DIR__.'/../src/Exception/NoSuchMethodException.php');
include_once(__DIR__.'/../src/Exception/StateErrorException.php');
include_once(__DIR__.'/../src/Exception/TypeErrorException.php');
include_once(__DIR__.'/../src/Exception/ValueErrorException.php');
include_once(__DIR__.'/../src/PhpXmlRpc.php');
include_once(__DIR__.'/../src/Traits/CharsetEncoderAware.php');
include_once(__DIR__.'/../src/Traits/LoggerAware.php');
include_once(__DIR__.'/../src/Traits/DeprecationLogger.php');
include_once(__DIR__.'/../src/Traits/ParserAware.php');
include_once(__DIR__.'/../src/Traits/PayloadBearer.php');
include_once(__DIR__.'/../src/Helper/Charset.php');
include_once(__DIR__.'/../src/Helper/Date.php');
include_once(__DIR__.'/../src/Helper/Http.php');
include_once(__DIR__.'/../src/Helper/Interop.php');
include_once(__DIR__.'/../src/Helper/Logger.php');
include_once(__DIR__.'/../src/Helper/XMLParser.php');
include_once(__DIR__.'/../src/Value.php');
include_once(__DIR__.'/../src/Request.php');
include_once(__DIR__.'/../src/Response.php');
include_once(__DIR__.'/../src/Client.php');
include_once(__DIR__.'/../src/Encoder.php');

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request;
use PhpXmlRpc\Response;
use PhpXmlRpc\Value;
use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Helper\Date;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;

/* Expose the global variables which used to be defined */
PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-1'; // old default
PhpXmlRpc\PhpXmlRpc::exportGlobals();

/* some stuff deprecated enough that we do not want to put it in the new lib version */

/// @deprecated
$GLOBALS['xmlEntities'] = array(
    'amp'  => '&',
    'quot' => '"',
    'lt'   => '<',
    'gt'   => '>',
    'apos' => "'"
);

// formulate backslashes for escaping regexp
// Not in use anymore since 2.0. Shall we remove it?
/// @deprecated
$GLOBALS['xmlrpc_backslash'] = chr(92).chr(92);

/* Expose with the old names the classes which have been namespaced */

/**
 * @todo reinstate access to method serializeData?
 */
class xmlrpcval extends Value
{
    /**
     * @deprecated
     * @param xmlrpcval $o
     * @return string
     */
    public function serializeval($o)
    {
        // add check? slower, but helps to avoid recursion in serializing broken xmlrpcvals...
        //if (is_object($o) && (get_class($o) == 'xmlrpcval' || is_subclass_of($o, 'xmlrpcval')))
        //{
        $ar = $o->me;
        $val = reset($ar);
        $typ = key($ar);

        return '<value>' . $this->serializeData($typ, $val) . "</value>\n";
        //}
    }

    /**
     * @deprecated this code looks like it is very fragile and has not been fixed for a long long time.
     * Shall we remove it for 2.0?
     */
    public function getval()
    {
        // UNSTABLE
        $b = reset($this->me);
        $a = key($this->me);
        // contributed by I Sofer, 2001-03-24
        // add support for nested arrays to scalarval
        // i've created a new method here, so as to
        // preserve back compatibility

        if (is_array($b)) {
            foreach($b as $id => $cont) {
                $b[$id] = $cont->scalarVal();
            }
        }

        // add support for structures directly encoding php objects
        if (is_object($b)) {
            $t = get_object_vars($b);
            foreach($t as $id => $cont) {
                $t[$id] = $cont->scalarVal();
            }
            foreach($t as $id => $cont) {
                @$b->$id = $cont;
            }
        }
        // end contrib
        return $b;
    }

    /// reset functionality added by parent class: same as it would happen if no interface was declared
    public function count()
    {
        return 1;
    }

    /// reset functionality added by parent class: same as it would happen if no interface was declared
    public function getIterator()
    {
        return new ArrayIterator($this);
    }
}

/**
 * @todo reinstate access to method parseResponseHeaders?
 */
class xmlrpcmsg extends Request
{
}

class xmlrpcresp extends Response
{
}

/**
 * @todo reinstate access to methods sendPayloadHTTP10, sendPayloadHTTPS, sendPayloadCURL, _try_multicall?
 */
class xmlrpc_client extends Client
{
}

/* Expose as global functions the ones which are now class methods */

/// Wrong speling, but we are adamant on backwards compatibility!
function xmlrpc_encode_entitites($data, $srcEncoding='', $destEncoding='')
{
    return Charset::instance()->encodeEntities($data, $srcEncoding, $destEncoding);
}

function iso8601_encode($timeT, $utc=0)
{
    return Date::iso8601Encode($timeT, $utc);
}

function iso8601_decode($iDate, $utc=0)
{
    return Date::iso8601Decode($iDate, $utc);
}

function decode_chunked($buffer)
{
    return Http::decodeChunked($buffer);
}

function php_xmlrpc_decode($xmlrpcVal, $options=array())
{
    $encoder = new Encoder();
    return $encoder->decode($xmlrpcVal, $options);
}

function php_xmlrpc_encode($phpVal, $options=array())
{
    $encoder = new Encoder();
    return $encoder->encode($phpVal, $options);
}

function php_xmlrpc_decode_xml($xmlVal, $options=array())
{
    $encoder = new Encoder();
    return $encoder->decodeXml($xmlVal, $options);
}

function guess_encoding($httpHeader='', $xmlChunk='', $encodingPrefs=null)
{
    return XMLParser::guessEncoding($httpHeader, $xmlChunk, $encodingPrefs);
}

function has_encoding($xmlChunk)
{
    return XMLParser::hasEncoding($xmlChunk);
}

function is_valid_charset($encoding, $validList)
{
    return Charset::instance()->isValidCharset($encoding, $validList);
}
