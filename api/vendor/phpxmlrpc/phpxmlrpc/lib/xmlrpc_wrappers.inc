<?php

/******************************************************************************
 *
 * *** DEPRECATED ***
 *
 * This file is only used to insure backwards compatibility
 * with the API of the library <= rev. 3
 *****************************************************************************/

include_once(__DIR__.'/../src/Wrapper.php');

/* Expose as global functions the ones which are now class methods */

/**
 * @see PhpXmlRpc\Wrapper::php_2_xmlrpc_type
 * @param string $phpType
 * @return string
 */
function php_2_xmlrpc_type($phpType)
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->php2XmlrpcType($phpType);
}

/**
 * @see PhpXmlRpc\Wrapper::xmlrpc_2_php_type
 * @param string $xmlrpcType
 * @return string
 */
function xmlrpc_2_php_type($xmlrpcType)
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->xmlrpc2PhpType($xmlrpcType);
}

/**
 * @see PhpXmlRpc\Wrapper::wrap_php_function
 * @param callable $funcName
 * @param string $newFuncName
 * @param array $extraOptions
 * @return array|false
 */
function wrap_php_function($funcName, $newFuncName='', $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    if (!isset($extraOptions['return_source']) || $extraOptions['return_source'] == false) {
        // backwards compat: return string instead of callable
        $extraOptions['return_source'] = true;
        $wrapped = $wrapper->wrapPhpFunction($funcName, $newFuncName, $extraOptions);
        eval($wrapped['source']);
    } else {
        $wrapped = $wrapper->wrapPhpFunction($funcName, $newFuncName, $extraOptions);
    }
    return $wrapped;
}

/**
 * NB: this function returns an array in a format which is unsuitable for direct use in the server dispatch map, unlike
 * PhpXmlRpc\Wrapper::wrapPhpClass. This behaviour might seem like a bug, but has been kept for backwards compatibility.
 *
 * @see PhpXmlRpc\Wrapper::wrap_php_class
 * @param string|object $className
 * @param array $extraOptions
 * @return array|false
 */
function wrap_php_class($className, $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    $fix = false;
    if (!isset($extraOptions['return_source']) || $extraOptions['return_source'] == false) {
        // backwards compat: return string instead of callable
        $extraOptions['return_source'] = true;
        $fix = true;
    }
    $wrapped = $wrapper->wrapPhpClass($className, $extraOptions);
    foreach($wrapped as $name => $value) {
        if ($fix) {
            eval($value['source']);
        }
        $wrapped[$name] = $value['function'];
    }
    return $wrapped;
}

/**
 * @see PhpXmlRpc\Wrapper::wrapXmlrpcMethod
 * @param xmlrpc_client $client
 * @param string $methodName
 * @param int|array $extraOptions the usage of an int as signature number is deprecated, use an option 'signum' in $extraOptions
 * @param int $timeout            deprecated, use an option in $extraOptions
 * @param string $protocol        deprecated, use an option in $extraOptions
 * @param string $newFuncName     deprecated, use an option in $extraOptions
 * @return array|callable|false
 */
function wrap_xmlrpc_method($client, $methodName, $extraOptions=0, $timeout=0, $protocol='', $newFuncName='')
{
    if (!is_array($extraOptions))
    {
        $sigNum = $extraOptions;
        $extraOptions = array(
            'signum' => $sigNum,
            'timeout' => $timeout,
            'protocol' => $protocol,
            'new_function_name' => $newFuncName
        );
    }

    $wrapper = new PhpXmlRpc\Wrapper();

    if (!isset($extraOptions['return_source']) || $extraOptions['return_source'] == false) {
        // backwards compat: return string instead of callable
        $extraOptions['return_source'] = true;
        $wrapped = $wrapper->wrapXmlrpcMethod($client, $methodName, $extraOptions);
        if (is_array($wrapped)) {
            eval($wrapped['source']);
            $wrapped = $wrapped['function'];
        }
    } else {
        $wrapped = $wrapper->wrapXmlrpcMethod($client, $methodName, $extraOptions);
    }
    return $wrapped;
}

/**
 * @see PhpXmlRpc\Wrapper::wrap_xmlrpc_server
 * @param xmlrpc_client $client
 * @param array $extraOptions
 * @return mixed
 */
function wrap_xmlrpc_server($client, $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->wrapXmlrpcServer($client, $extraOptions);
}

/**
 * Given the necessary info, build php code that creates a new function to invoke a remote xmlrpc method.
 * Take care that no full checking of input parameters is done to ensure that valid php code is emitted.
 * Only kept for backwards compatibility
 * Note: real spaghetti code follows...
 *
 * @deprecated
 */
function build_remote_method_wrapper_code($client, $methodName, $xmlrpcFuncName,
     $mSig, $mDesc = '', $timeout = 0, $protocol = '', $clientCopyMode = 0, $prefix = 'xmlrpc',
     $decodePhpObjects = false, $encodePhpObjects = false, $decodeFault = false,
     $faultResponse = '', $namespace = '\\PhpXmlRpc\\')
{
    $code = "function $xmlrpcFuncName (";
    if ($clientCopyMode < 2) {
        // client copy mode 0 or 1 == partial / full client copy in emitted code
        $innerCode = build_client_wrapper_code($client, $clientCopyMode, $prefix);
        $innerCode .= "\$client->setDebug(\$debug);\n";
        $this_ = '';
    } else {
        // client copy mode 2 == no client copy in emitted code
        $innerCode = '';
        $this_ = 'this->';
    }
    $innerCode .= "\$req = new {$namespace}Request('$methodName');\n";

    if ($mDesc != '') {
        // take care that PHP comment is not terminated unwillingly by method description
        $mDesc = "/**\n* " . str_replace('*/', '* /', $mDesc) . "\n";
    } else {
        $mDesc = "/**\nFunction $xmlrpcFuncName\n";
    }

    // param parsing
    $innerCode .= "\$encoder = new {$namespace}Encoder();\n";
    $plist = array();
    $pCount = count($mSig);
    for ($i = 1; $i < $pCount; $i++) {
        $plist[] = "\$p$i";
        $pType = $mSig[$i];
        if ($pType == 'i4' || $pType == 'i8' || $pType == 'int' || $pType == 'boolean' || $pType == 'double' ||
            $pType == 'string' || $pType == 'dateTime.iso8601' || $pType == 'base64' || $pType == 'null'
        ) {
            // only build directly xmlrpc values when type is known and scalar
            $innerCode .= "\$p$i = new {$namespace}Value(\$p$i, '$pType');\n";
        } else {
            if ($encodePhpObjects) {
                $innerCode .= "\$p$i = \$encoder->encode(\$p$i, array('encode_php_objs'));\n";
            } else {
                $innerCode .= "\$p$i = \$encoder->encode(\$p$i);\n";
            }
        }
        $innerCode .= "\$req->addparam(\$p$i);\n";
        $mDesc .= '* @param ' . xmlrpc_2_php_type($pType) . " \$p$i\n";
    }
    if ($clientCopyMode < 2) {
        $plist[] = '$debug=0';
        $mDesc .= "* @param int \$debug when 1 (or 2) will enable debugging of the underlying {$prefix} call (defaults to 0)\n";
    }
    $plist = implode(', ', $plist);
    $mDesc .= '* @return ' . xmlrpc_2_php_type($mSig[0]) . " (or an {$namespace}Response obj instance if call fails)\n*/\n";

    $innerCode .= "\$res = \${$this_}client->send(\$req, $timeout, '$protocol');\n";
    if ($decodeFault) {
        if (is_string($faultResponse) && ((strpos($faultResponse, '%faultCode%') !== false) || (strpos($faultResponse, '%faultString%') !== false))) {
            $respCode = "str_replace(array('%faultCode%', '%faultString%'), array(\$res->faultCode(), \$res->faultString()), '" . str_replace("'", "''", $faultResponse) . "')";
        } else {
            $respCode = var_export($faultResponse, true);
        }
    } else {
        $respCode = '$res';
    }
    if ($decodePhpObjects) {
        $innerCode .= "if (\$res->faultCode()) return $respCode; else return \$encoder->decode(\$res->value(), array('decode_php_objs'));";
    } else {
        $innerCode .= "if (\$res->faultCode()) return $respCode; else return \$encoder->decode(\$res->value());";
    }

    $code = $code . $plist . ") {\n" . $innerCode . "\n}\n";

    return array('source' => $code, 'docstring' => $mDesc);
}

/**
 * @deprecated
 */
function build_client_wrapper_code($client, $verbatim_client_copy, $prefix='xmlrpc')
{
    $code = "\$client = new {$prefix}_client('".str_replace("'", "\'", $client->path).
        "', '" . str_replace("'", "\'", $client->server) . "', $client->port);\n";

    // copy all client fields to the client that will be generated runtime
    // (this provides for future expansion or subclassing of client obj)
    if ($verbatim_client_copy)
    {
        foreach ($client as $fld => $val)
        {
            if ($fld != 'debug' && $fld != 'return_type')
            {
                $val = var_export($val, true);
                $code .= "\$client->$fld = $val;\n";
            }
        }
    }
    // only make sure that client always returns the correct data type
    $code .= "\$client->return_type = '{$prefix}vals';\n";
    //$code .= "\$client->setDebug(\$debug);\n";
    return $code;
}
