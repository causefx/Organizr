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
 *****************************************************************************/

include_once(__DIR__.'/../src/Server.php');

use PhpXmlRpc\Server;

class xmlrpc_server extends Server
{
    /**
     * A debugging routine: just echoes back the input packet as a string value
     * @deprecated
     */
    public function echoInput()
    {
        $r = new PhpXmlRpc\Response(new PhpXmlRpc\Value("'Aha said I: '" . file_get_contents('php://input'), 'string'));
        print $r->serialize();
    }

    /**
     * Reinstate access to class members which became protected/private
     * @param string $name
     * @return mixed
     */
    public function &__get($name)
    {
        switch($name) {
            case 'dmap':
                return $this->dmap;
            default:
                return parent::__get($name);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $name === 'dmap' ? true : parent::__isset($name);
    }

    /// @todo what about __set, __unset?
}

/* Expose as global functions the ones which are now class methods */

/**
 * @see Server::xmlrpc_debugmsg
 * @param string $m
 */
function xmlrpc_debugmsg($m)
{
    Server::xmlrpc_debugmsg($m);
}

function _xmlrpcs_getCapabilities($server, $m=null)
{
    return Server::_xmlrpcs_getCapabilities($server, $m);
}

$_xmlrpcs_listMethods_sig=array(array(\PhpXmlRpc\Value::$xmlrpcArray));
$_xmlrpcs_listMethods_doc='This method lists all the methods that the XML-RPC server knows how to dispatch';
$_xmlrpcs_listMethods_sdoc=array(array('list of method names'));
function _xmlrpcs_listMethods($server, $m=null) // if called in plain php values mode, second param is missing
{
    return Server::_xmlrpcs_listMethods($server, $m);
}

$_xmlrpcs_methodSignature_sig=array(array(\PhpXmlRpc\Value::$xmlrpcArray, \PhpXmlRpc\Value::$xmlrpcString));
$_xmlrpcs_methodSignature_doc='Returns an array of known signatures (an array of arrays) for the method name passed. If no signatures are known, returns a none-array (test for type != array to detect missing signature)';
$_xmlrpcs_methodSignature_sdoc=array(array('list of known signatures, each sig being an array of xmlrpc type names', 'name of method to be described'));
function _xmlrpcs_methodSignature($server, $m)
{
    return Server::_xmlrpcs_methodSignature($server, $m);
}

$_xmlrpcs_methodHelp_sig=array(array(\PhpXmlRpc\Value::$xmlrpcString, \PhpXmlRpc\Value::$xmlrpcString));
$_xmlrpcs_methodHelp_doc='Returns help text if defined for the method passed, otherwise returns an empty string';
$_xmlrpcs_methodHelp_sdoc=array(array('method description', 'name of the method to be described'));
function _xmlrpcs_methodHelp($server, $m)
{
    return Server::_xmlrpcs_methodHelp($server, $m);
}

function _xmlrpcs_multicall_error($err)
{
    return Server::_xmlrpcs_multicall_error($err);
}

function _xmlrpcs_multicall_do_call($server, $call)
{
    return Server::_xmlrpcs_multicall_do_call($server, $call);
}

function _xmlrpcs_multicall_do_call_phpvals($server, $call)
{
    return Server::_xmlrpcs_multicall_do_call_phpvals($server, $call);
}

$_xmlrpcs_multicall_sig = array(array(\PhpXmlRpc\Value::$xmlrpcArray, \PhpXmlRpc\Value::$xmlrpcArray));
$_xmlrpcs_multicall_doc = 'Boxcar multiple RPC calls in one request. See http://www.xmlrpc.com/discuss/msgReader$1208 for details';
$_xmlrpcs_multicall_sdoc = array(array('list of response structs, where each struct has the usual members', 'list of calls, with each call being represented as a struct, with members "methodname" and "params"'));
function _xmlrpcs_multicall($server, $m)
{
    return Server::_xmlrpcs_multicall($server, $m);
}
