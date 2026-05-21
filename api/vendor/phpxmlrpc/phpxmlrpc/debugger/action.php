<?php
/**
 * @author Gaetano Giunta
 * @copyright (C) 2005-2025 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 *
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 * @todo use ob_start to catch debug info and echo it AFTER method call results?
 * @todo be smarter in creating client stub for proxy/auth cases: only set appropriate property of client obj
 **/

header('Content-Type: text/html; charset=utf-8');

?><!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">
    <title><?php if (defined('DEFAULT_WSTYPE') && (DEFAULT_WSTYPE == 1 || DEFAULT_WSTYPE == 2)) echo 'JSON-RPC'; else echo 'XML-RPC'; ?> Debugger</title>
    <meta name="robots" content="index,nofollow"/>
    <style type="text/css">
        <!--
        body {
            border-top: 1px solid gray;
            padding: 1em;
            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size: 8pt;
        }
        h3 {
            font-size: 9.5pt;
        }
        h2 {
            font-size: 12pt;
        }
        .dbginfo {
            padding: 1em;
            background-color: #EEEEEE;
            border: 1px dashed silver;
            font-family: monospace;
        }
        #response {
            padding: 1em;
            margin-top: 1em;
            background-color: #DDDDDD;
            border: 1px solid gray;
            white-space: pre;
            font-family: monospace;
        }
        table {
            padding: 2px;
            margin-top: 1em;
        }
        th {
            background-color: navy;
            color: white;
            padding: 0.5em;
        }
        td {
            padding: 0.5em;
            font-family: monospace;
        }
        td form {
            margin: 0;
        }
        .oddrow {
            background-color: #EEEEEE;
        }
        .evidence {
            color: blue;
        }
        #phpcode {
            background-color: #EEEEEE;
            padding: 1em;
            margin-top: 1em;
        }
        -->
    </style>
</head>
<body>
<?php

global $inputcharset, $debug, $protocol, $run, $hasjsonrpcclient, $hasjsonrpc2, $wstype, $id, $host, $port, $path, $action,
       $method, $methodsig, $payload, $alt_payload, $username, $password, $authtype, $verifyhost, $verifypeer, $cainfo, $proxy,
       $proxyuser, $proxypwd, $timeout, $requestcompression, $responsecompression, $clientcookies;

include __DIR__ . '/common.php';

if ($action) {

    // avoid php hanging when using the builtin webserver and sending requests to itself
    $skip = false;
    if (php_sapi_name() === 'cli-server' && ((int)getenv('PHP_CLI_SERVER_WORKERS') < 2)) {
        $localHost = explode(':', $_SERVER['HTTP_HOST']);
        /// @todo support also case where port is null (on either side), and when there is a Proxy in the parameters,
        ///       and that proxy is us
        if ($localHost[0] == $host && (@$localHost[1] == $port)) {
            $actionname = '[ERROR: can not make call to self when running php-cli webserver without setting PHP_CLI_SERVER_WORKERS]';
            $skip = true;
        }
    }

    if (!$skip) {
        // make sure the script waits long enough for the call to complete...
        if ($timeout) {
            set_time_limit($timeout + 10);
        }

        if ($wstype == 1 || $wstype == 2) {
            $clientClass = '\PhpXmlRpc\JsonRpc\Client';
            $requestClass = '\PhpXmlRpc\JsonRpc\Request';
            if ($hasjsonrpc2) {
                $notificationClass = '\PhpXmlRpc\JsonRpc\Notification';
            } else {
                $notificationClass = '\PhpXmlRpc\JsonRpc\Request';
            }
            $protoName = 'JSON-RPC';
        } else {
            $clientClass = '\PhpXmlRpc\Client';
            $requestClass = '\PhpXmlRpc\Request';
            $notificationClass = '\PhpXmlRpc\Request';
            $protoName = 'XML-RPC';
        }

        if ($port != "") {
            $client = new $clientClass($path, $host, $port);
            $server = "$host:$port$path";
        } else {
            $client = new $clientClass($path, $host);
            $server = "$host$path";
        }
        if ($protocol == 2 || $protocol == 3) {
            $server = 'https://' . $server;
        } else {
            $server = 'http://' . $server;
        }
        if ($proxy != '') {
            $pproxy = explode(':', $proxy);
            if (count($pproxy) > 1) {
                $pport = $pproxy[1];
            } else {
                $pport = 8080;
            }
            $client->setProxy($pproxy[0], $pport, $proxyuser, $proxypwd);
        }

        if ($protocol == 2 || $protocol == 3) {
            $client->setOption(\PhpXmlRpc\Client::OPT_VERIFY_PEER, $verifypeer);
            $client->setOption(\PhpXmlRpc\Client::OPT_VERIFY_HOST, $verifyhost);
            if ($cainfo) {
                $client->setCaCertificate($cainfo);
            }
            if ($protocol == 3) {
                $httpprotocol = 'h2';
            } else {
                $httpprotocol = 'https';
            }
        } elseif ($protocol == 4) {
            $httpprotocol = 'h2c';
        } elseif ($protocol == 1) {
            $httpprotocol = 'http11';
        } else {
            $httpprotocol = 'http';
        }

        if ($username) {
            $client->setCredentials($username, $password, $authtype);
        }

        $client->setDebug($debug);

        switch ($requestcompression) {
            case 0:
                $client->setOption(\PhpXmlRpc\Client::OPT_REQUEST_COMPRESSION, '');
                break;
            case 1:
                $client->setOption(\PhpXmlRpc\Client::OPT_REQUEST_COMPRESSION, 'gzip');
                break;
            case 2:
                $client->setOption(\PhpXmlRpc\Client::OPT_REQUEST_COMPRESSION, 'deflate');
                break;
        }

        switch ($responsecompression) {
            case 0:
                $client->setOption(\PhpXmlRpc\Client::OPT_ACCEPTED_COMPRESSION, '');
                break;
            case 1:
                $client->setOption(\PhpXmlRpc\Client::OPT_ACCEPTED_COMPRESSION, array('gzip'));
                break;
            case 2:
                $client->setOption(\PhpXmlRpc\Client::OPT_ACCEPTED_COMPRESSION, ('deflate'));
                break;
            case 3:
                $client->setOption(\PhpXmlRpc\Client::OPT_ACCEPTED_COMPRESSION, array('gzip', 'deflate'));
                break;
        }

        $cookies = explode(',', $clientcookies);
        foreach ($cookies as $cookie) {
            if (strpos($cookie, '=')) {
                $cookie = explode('=', $cookie);
                $client->setCookie(trim($cookie[0]), trim(@$cookie[1]));
            }
        }

        $msg = array();
        switch ($action) {
            // fall thru intentionally
            case 'describe':
            case 'wrap':
                $msg[0] = new $requestClass('system.methodHelp', array(), (int)$id);
                $msg[0]->addparam(new PhpXmlRpc\Value($method));
                $msg[1] = new $requestClass('system.methodSignature', array(), (int)$id + 1);
                $msg[1]->addparam(new PhpXmlRpc\Value($method));
                if ($wstype == 2) {
                    $msg[0]->setJsonRpcVersion('2.0');
                    $msg[1]->setJsonRpcVersion('2.0');
                } elseif ($wstype == 1 && $hasjsonrpc2) {
                    $msg[0]->setJsonRpcVersion('1.0');
                    $msg[1]->setJsonRpcVersion('1.0');
                }
                $actionname = 'Description of method "' . $method . '"';
                break;
            case 'list':
                $msg[0] = new $requestClass('system.listMethods', array(), (int)$id);
                if ($wstype == 2) {
                    $msg[0]->setJsonRpcVersion('2.0');
                } elseif ($wstype == 1 && $hasjsonrpc2) {
                    $msg[0]->setJsonRpcVersion('1.0');
                }
                $actionname = 'List of available methods';
                break;
            case 'execute':
            case 'notification':
                if (!payload_is_safe($payload)) {
                    die("Tsk tsk tsk, please stop it or I will have to call in the cops!");
                }
                if ($action == 'notification') {
                    $msg[0] = new $notificationClass($method, array());
                } else {
                    $msg[0] = new $requestClass($method, array(), $id);
                }
                // hack! build payload by hand
                if ($wstype == 2) {
                    $payload = rtrim($payload, "\n");
                    $payload = "{\n" .
                        '"jsonrpc": "2.0"' . ",\n" .
                        '"method": "' . $method . "\",\n\"params\": [\n" .
                        $payload .
                        "\n]";
                    if ($action == "notification") {
                        $payload .= "\n";
                    } else {
                        if (is_numeric($id)) {
                            $payload .= ",\n\"id\": $id\n";
                        } else {
                            $payload .= ",\n\"id\": \"$id\"\n";
                        }
                    }
                    $payload .= '}';
                    $msg[0]->setPayload($payload);
                    $msg[0]->setJsonRpcVersion('2.0');
                } elseif ($wstype == 1) {
                    $payload = rtrim($payload, "\n");
                    $payload = "{\n" .
                        '"method": "' . $method . "\",\n\"params\": [\n" .
                        $payload .
                        "\n],\n\"id\": ";
                    if ($action == "notification") {
                        $payload .= "null\n}";
                    } else {
                        if (is_numeric($id) || $id == 'false' || $id == 'true') {
                            $payload .= "$id\n}";
                        } else {
                            // this includes the empty string ;-)
                            $payload .= "\"$id\"\n}";
                        }
                    }
                    $msg[0]->setPayload($payload);
                    if ($hasjsonrpc2) {
                        $msg[0]->setJsonRpcVersion('1.0');
                    }
                } else {
                    $msg[0]->setPayload(
                        $msg[0]->xml_header($inputcharset) .
                        '<methodName>' . $method . "</methodName>\n<params>" .
                        $payload .
                        "</params>\n" . $msg[0]->xml_footer()
                    );
                }
                if ($action == 'notification') {
                    $actionname = 'Execution of notification ' . $method;
                } else {
                    $actionname = 'Execution of method ' . $method;
                }
                break;
            default: // give a warning
                $actionname = '[ERROR: unknown action] "' . $action . '"';
        }
    }

    // Before calling execute, echo out brief description of action taken + date and time ???
    // this gives good user feedback for long-running methods...
    echo '<h2>' . htmlspecialchars($actionname, ENT_COMPAT, $inputcharset) . ' on server ' . htmlspecialchars($server, ENT_COMPAT, $inputcharset) . " ...</h2>\n";
    flush();

    $response = null;
    // execute method(s)
    if ($debug) {
        echo '<div class="dbginfo"><h2>Debug info:</h2>';
    }  /// @todo use ob_start instead
    $resp = array();
    $time = microtime(true);
    foreach ($msg as $message) {
        $response = $client->send($message, $timeout, $httpprotocol);
        $resp[] = $response;

        if (!$response || (is_object($response) && $response->faultCode())) {
            break;
        }
    }
    $time = microtime(true) - $time;
    if ($debug) {
        echo "</div>\n";
    }

    if ($response) {
        if ($response === true) {
            // we assume that only notification calls can return true instead of a response
            printf("<h3>%s notification call OK (%.2f secs.)</h3>\n", $protoName, $time);
            echo(date("d/M/Y:H:i:s\n"));
        } else if ($response->faultCode()) {
            // call failed! echo out error msg!
            //echo '<h2>'.htmlspecialchars($actionname, ENT_COMPAT, $inputcharset).' on server '.htmlspecialchars($server, ENT_COMPAT, $inputcharset).'</h2>';
            echo "<h3>$protoName call FAILED!</h3>\n";
            echo "<p>Fault code: [" . htmlspecialchars($response->faultCode(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) .
                "] Reason: '" . htmlspecialchars($response->faultString(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) . "'</p>\n";
            echo(date("d/M/Y:H:i:s\n"));
        } else {
            // call succeeded: parse results
            //echo '<h2>'.htmlspecialchars($actionname, ENT_COMPAT, $inputcharset).' on server '.htmlspecialchars($server, ENT_COMPAT, $inputcharset).'</h2>';
            printf("<h3>%s call(s) OK (%.2f secs.)</h3>\n", $protoName, $time);
            echo(date("d/M/Y:H:i:s\n"));

            switch ($action) {
                case 'list':

                    $v = $response->value();
                    if ($v->kindOf() == "array") {
                        $max = $v->count();
                        echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
                        echo "<thead>\n<tr><th>Method ($max)</th><th>Description</th></tr>\n</thead>\n<tbody>\n";
                        foreach($v as $i => $rec) {
                            if ($i % 2) {
                                $class = ' class="oddrow"';
                            } else {
                                $class = ' class="evenrow"';
                            }
                            echo("<tr><td$class>" . htmlspecialchars($rec->scalarval(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) . "</td><td$class><form action=\"controller.php\" method=\"get\" target=\"frmcontroller\">" .
                                "<input type=\"hidden\" name=\"host\" value=\"" . htmlspecialchars($host, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"port\" value=\"" . htmlspecialchars($port, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"path\" value=\"" . htmlspecialchars($path, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"id\" value=\"" . htmlspecialchars($id, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"debug\" value=\"$debug\" />" .
                                "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($username, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"password\" value=\"" . htmlspecialchars($password, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"authtype\" value=\"$authtype\" />" .
                                "<input type=\"hidden\" name=\"verifyhost\" value=\"$verifyhost\" />" .
                                "<input type=\"hidden\" name=\"verifypeer\" value=\"$verifypeer\" />" .
                                "<input type=\"hidden\" name=\"cainfo\" value=\"" . htmlspecialchars($cainfo, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxy\" value=\"" . htmlspecialchars($proxy, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxyuser\" value=\"" . htmlspecialchars($proxyuser, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxypwd\" value=\"" . htmlspecialchars($proxypwd, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"responsecompression\" value=\"$responsecompression\" />" .
                                "<input type=\"hidden\" name=\"requestcompression\" value=\"$requestcompression\" />" .
                                "<input type=\"hidden\" name=\"clientcookies\" value=\"" . htmlspecialchars($clientcookies, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"protocol\" value=\"$protocol\" />" .
                                "<input type=\"hidden\" name=\"timeout\" value=\"" . htmlspecialchars($timeout, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"method\" value=\"" . htmlspecialchars($rec->scalarval(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) . "\" />" .
                                "<input type=\"hidden\" name=\"wstype\" value=\"$wstype\" />" .
                                "<input type=\"hidden\" name=\"action\" value=\"describe\" />" .
                                "<input type=\"hidden\" name=\"run\" value=\"now\" />" .
                                "<input type=\"submit\" value=\"Describe\" /></form></td>");
                            //echo("</tr>\n");

                            // generate the skeleton for method payload per possible tests
                            //$methodpayload="<methodCall>\n<methodName>".$rec->scalarval()."</methodName>\n<params>\n<param><value></value></param>\n</params>\n</methodCall>";

                            /*echo ("<form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\"><td>".
                              "<input type=\"hidden\" name=\"host\" value=\"$host\" />".
                              "<input type=\"hidden\" name=\"port\" value=\"$port\" />".
                              "<input type=\"hidden\" name=\"path\" value=\"$path\" />".
                              "<input type=\"hidden\" name=\"method\" value=\"".$rec->scalarval()."\" />".
                              "<input type=\"hidden\" name=\"methodpayload\" value=\"$payload\" />".
                              "<input type=\"hidden\" name=\"action\" value=\"execute\" />".
                              "<input type=\"submit\" value=\"Test\" /></td></form>");*/
                            echo("</tr>\n");
                        }
                        echo "</tbody>\n</table>";
                    }
                    break;

                case 'describe':

                    $r1 = $resp[0]->value();
                    $r2 = $resp[1]->value();

                    echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
                    echo "<thead>\n<tr><th>Method</th><th>" . htmlspecialchars($method, ENT_COMPAT, $inputcharset) . "</th><th>&nbsp;</th><th>&nbsp;</th></tr>\n</thead>\n<tbody>\n";
                    $desc = htmlspecialchars($r1->scalarval(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding);
                    if ($desc == "") {
                        $desc = "-";
                    }
                    echo "<tr><td class=\"evenrow\">Description</td><td colspan=\"3\" class=\"evenrow\">$desc</td></tr>\n";

                    if ($r2->kindOf() != "array") {
                        echo "<tr><td class=\"oddrow\">Signature</td><td class=\"oddrow\">Unknown</td><td class=\"oddrow\">&nbsp;</td></tr>\n";
                    } else {
                        foreach($r2 as $i => $x) {
                            $payload = "";
                            $alt_payload = "";
                            if ($i + 1 % 2) {
                                $class = ' class="oddrow"';
                            } else {
                                $class = ' class="evenrow"';
                            }
                            echo "<tr><td$class>Signature&nbsp;" . ($i + 1) . "</td><td$class>";
                            if ($x->kindOf() == "array") {
                                $ret = $x[0];
                                echo "<code>OUT:&nbsp;" . htmlspecialchars($ret->scalarval(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) . "<br />IN: (";
                                if ($x->count() > 1) {
                                    foreach($x as $k => $y) {
                                        if ($k == 0) continue;
                                        echo htmlspecialchars($y->scalarval(), ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding);
                                        if ($wstype == 1 || $wstype == 2) {
                                            switch($y->scalarval()) {
                                                case 'string':
                                                case 'dateTime.iso8601':
                                                case 'base64':
                                                    $payload .= '""';
                                                    break;
                                                case 'i4':
                                                case 'i8':
                                                case 'int':
                                                    $payload .= '0';
                                                    break;
                                                case 'double':
                                                    $payload .= '0.0';
                                                    break;
                                                case 'bool':
                                                case 'boolean':
                                                    $payload .= 'true';
                                                    break;
                                                case 'null':
                                                    $payload .= 'null';
                                                    break;
                                                case 'array':
                                                    $payload .= '[]';
                                                    break;
                                                case 'struct':
                                                    $payload .= '{}';
                                                    break;
                                                default:
                                                    break;
                                            }
                                        } else {
                                            $type = $y->scalarval();
                                            $payload .= '<param><value>';
                                            switch($type) {
                                                case 'undefined':
                                                    break;
                                                case 'null':
                                                    $type = 'nil';
                                                    // fall thru intentionally
                                                default:
                                                    $payload .= '<' .
                                                        htmlspecialchars($type, ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) .
                                                        '></' . htmlspecialchars($type, ENT_COMPAT, \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding) .
                                                        '>';
                                            }
                                            $payload .= "</value></param>\n";
                                        }
                                        $alt_payload .= $y->scalarval();
                                        if ($k < $x->count() - 1) {
                                            $alt_payload .= ';';
                                            if ($wstype == 1 || $wstype == 2) {
                                                $payload .= ', ';
                                            }
                                            echo ", ";
                                        }
                                    }
                                }
                                echo ")</code>";
                            } else {
                                echo 'Unknown';
                            }
                            echo '</td>';
                            // button to test this method
                            //$payload="<methodCall>\n<methodName>$method</methodName>\n<params>\n$payload</params>\n</methodCall>";
                            echo "<td$class><form action=\"controller.php\" target=\"frmcontroller\" method=\"get\">" .
                                "<input type=\"hidden\" name=\"host\" value=\"" . htmlspecialchars($host, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"port\" value=\"" . htmlspecialchars($port, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"path\" value=\"" . htmlspecialchars($path, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"id\" value=\"" . htmlspecialchars($id, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"debug\" value=\"$debug\" />" .
                                "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($username, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"password\" value=\"" . htmlspecialchars($password, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"authtype\" value=\"$authtype\" />" .
                                "<input type=\"hidden\" name=\"verifyhost\" value=\"$verifyhost\" />" .
                                "<input type=\"hidden\" name=\"verifypeer\" value=\"$verifypeer\" />" .
                                "<input type=\"hidden\" name=\"cainfo\" value=\"" . htmlspecialchars($cainfo, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxy\" value=\"" . htmlspecialchars($proxy, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxyuser\" value=\"" . htmlspecialchars($proxyuser, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxypwd\" value=\"" . htmlspecialchars($proxypwd, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"responsecompression\" value=\"$responsecompression\" />" .
                                "<input type=\"hidden\" name=\"requestcompression\" value=\"$requestcompression\" />" .
                                "<input type=\"hidden\" name=\"clientcookies\" value=\"" . htmlspecialchars($clientcookies, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"protocol\" value=\"$protocol\" />" .
                                "<input type=\"hidden\" name=\"timeout\" value=\"" . htmlspecialchars($timeout, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"method\" value=\"" . htmlspecialchars($method, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"methodpayload\" value=\"" . htmlspecialchars($payload, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"altmethodpayload\" value=\"" . htmlspecialchars($alt_payload, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"wstype\" value=\"$wstype\" />" .
                                "<input type=\"hidden\" name=\"action\" value=\"execute\" />";
                            //if ($wstype != 1) {
                                echo "<input type=\"submit\" value=\"Load method synopsis\" />";
                            //}
                            echo "</form></td>\n";

                            echo "<td$class><form action=\"controller.php\" target=\"frmcontroller\" method=\"get\">" .
                                "<input type=\"hidden\" name=\"host\" value=\"" . htmlspecialchars($host, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"port\" value=\"" . htmlspecialchars($port, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"path\" value=\"" . htmlspecialchars($path, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"id\" value=\"" . htmlspecialchars($id, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"debug\" value=\"$debug\" />" .
                                "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($username, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"password\" value=\"" . htmlspecialchars($password, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"authtype\" value=\"$authtype\" />" .
                                "<input type=\"hidden\" name=\"verifyhost\" value=\"$verifyhost\" />" .
                                "<input type=\"hidden\" name=\"verifypeer\" value=\"$verifypeer\" />" .
                                "<input type=\"hidden\" name=\"cainfo\" value=\"" . htmlspecialchars($cainfo, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxy\" value=\"" . htmlspecialchars($proxy, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxyuser\" value=\"" . htmlspecialchars($proxyuser, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"proxypwd\" value=\"" . htmlspecialchars($proxypwd, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"responsecompression\" value=\"$responsecompression\" />" .
                                "<input type=\"hidden\" name=\"requestcompression\" value=\"$requestcompression\" />" .
                                "<input type=\"hidden\" name=\"clientcookies\" value=\"" . htmlspecialchars($clientcookies, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"protocol\" value=\"$protocol\" />" .
                                "<input type=\"hidden\" name=\"timeout\" value=\"" . htmlspecialchars($timeout, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"method\" value=\"" . htmlspecialchars($method, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"methodsig\" value=\"" . $i . "\" />" .
                                "<input type=\"hidden\" name=\"methodpayload\" value=\"" . htmlspecialchars($payload, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"altmethodpayload\" value=\"" . htmlspecialchars($alt_payload, ENT_COMPAT, $inputcharset) . "\" />" .
                                "<input type=\"hidden\" name=\"wstype\" value=\"$wstype\" />" .
                                "<input type=\"hidden\" name=\"action\" value=\"wrap\" />" .
                                "<input type=\"hidden\" name=\"run\" value=\"now\" />" .
                                "<input type=\"submit\" value=\"Generate method call stub code\" />";
                            echo "</form></td></tr>\n";
                        }
                    }
                    echo "</tbody>\n</table>";

                    break;

                case 'wrap':
                    $r1 = $resp[0]->value();
                    $r2 = $resp[1]->value();
                    if ($r2->kindOf() != "array" || $r2->count() <= $methodsig) {
                        echo "Error: signature unknown\n";
                    } else {
                        $mdesc = $r1->scalarval();
                        $encoder = new PhpXmlRpc\Encoder();
                        $msig = $encoder->decode($r2);
                        $msig = $msig[$methodsig];
                        $proto = ($protocol == 1) ? 'http11' : ( $protocol == 2 ? 'https' : ( $protocol == 3 ? 'h2' : ( $protocol == 4 ? 'h2c' : '' ) ) );
                        if ($proxy == '' && $username == '' && !$requestcompression && !$responsecompression &&
                            $clientcookies == '') {
                            $opts = 1; // simple client copy in stub code
                        } else {
                            $opts = 0; // complete client copy in stub code
                        }
                        if ($wstype == 1 || $wstype == 2) {
                            $prefix = 'jsonrpc';
                        } else {
                            $prefix = 'xmlrpc';
                        }
                        if ($wstype == 1 || $wstype == 2) {
                            $wrapper = new PhpXmlRpc\JsonRpc\Wrapper();
                            if ($hasjsonrpc2) {
                                $wrapper->setJsonRpcVersion($wstype == 1 ? \PhpXmlRpc\JsonRpc\PhpJsonRpc::VERSION_1_0 : \PhpXmlRpc\JsonRpc\PhpJsonRpc::VERSION_2_0);
                            }
                        } else {
                            $wrapper = new PhpXmlRpc\Wrapper();
                        }
                        $code = $wrapper->buildWrapMethodSource(
                            $client,
                            $method,
                            array('timeout' => $timeout, 'protocol' => $proto, 'simple_client_copy' => $opts, 'prefix' => $prefix, 'throw_on_fault' => true),
                            str_replace('.', '_', $prefix . '_' . $method), $msig, $mdesc
                        );
                        //if ($code)
                        //{
                        echo "<div id=\"phpcode\">\n";
                        highlight_string("<?php\n" . $code['docstring'] . $code['source']);
                        echo "\n</div>";
                        //}
                        //else
                        //{
                        //  echo 'Error while building php code stub...';
                    }

                    break;

                case 'execute':
                    echo '<div id="response"><h2>Response:</h2>' . htmlspecialchars($response->serialize()) . '</div>';
                    break;

                case 'notification':
                    echo '<div id="response"><h2>Response:</h2>' . htmlspecialchars($response->serialize()) . '</div>';
                    break;

                default: // give a warning
            }
        } // if !$response->faultCode()
    } // if $response
} else {
    // no action taken yet: give some instructions on debugger usage
    ?>

    <h3>Instructions on usage of the debugger</h3>
    <b>If the server supports introspection methods `<i>system.listMethods</i>`, `<i>system.methodHelp</i>` and co.</b>
    <ol>
        <li>Fill in values for <i>Address</i>, <i>Port</i>, <i>Path</i></li>
        <li>Run a <i>'List available methods'</i> action against desired server</li>
        <li>If list of methods appears, click on <i>'Describe method'</i> for desired method</li>
        <li>To run method: click on <i>'Load method synopsis'</i> for desired method. This will load a skeleton for method call
            parameters in the form above. Complete all xml-rpc values with appropriate data and click <i>'Execute'</i>
        </li>
    </ol>
    <b>If the server does not support introspection methods</b>
    <ol>
        <li>Fill in values for <i>Address</i>, <i>Port</i>, <i>Path</i></li>
        <li>Select <i>'Execute method'</i></li>
        <li>Fill in method name and payload - fe. &quot;&lt;param&gt;&lt;value&gt;&lt;string&gt;hello&lt;/string&gt;&lt;/value&gt;&lt;/param&gt;&quot;
            (you can use a wizard to help creating the payload by clicking on the <i>'Edit'</i> link)</li>
        <li>Click <i>'Execute'</i></li>
    </ol>
    <p class="evidence">Use the <i>'Show debug info'</i> switch for troubleshooting.</p>
    <?php
    if (!extension_loaded('curl')) {
        echo "<p class=\"evidence\">You will need to enable the cURL extension to use the HTTPS, HTTP 1.1 and HTTP/2 transports</p>\n";
    }
    ?>

    <h3>Demo server</h3>
    <p>
        Server Address: tanoconsulting.com<br/>
        Path: /sw/xmlrpc/demo/server/server.php
    </p>

    <h3>Notice</h3>
    <p>all usernames and passwords entered on the above form will be written to the web server logs of this server. Use
        with care.</p>

    <h3>Changelog</h3>
    <ul>
        <li>2025-10-08: added support for json-rpc 2.0. The debugger now require phpjsonrpc 1.0.0 or later for json-rpc support</li>
        <li>2023-02-11: display in the top row the version of the libraries in use; made the generated code throw instead
            of returning a Response object on error; fixes for the json-rpc debugger</li>
        <li>2022-12-18: fix XSS vulnerability in the debugger; load jsxmlrpc from CDN; minor improvements</li>
        <li>2022-11-28: allow to use http/2 protocol; two security issues fixed in the underlying library</li>
        <li>2020-12-11: fix problems with running the debugger on php 8</li>
        <li>2015-05-30: fix problems with generating method payloads for NIL and Undefined parameters</li>
        <li>2015-04-19: fix problems with LATIN-1 characters in payload</li>
        <li>2007-02-20: add visual editor for method payload; allow strings, bools as jsonrpc req id</li>
        <li>2006-06-26: support building php code stub for calling remote methods</li>
        <li>2006-05-25: better support for long running queries; check for no-curl installs</li>
        <li>2006-05-02: added support for JSON-RPC. Note that many interesting json-rpc features are not implemented
            yet, such as notifications or multicall.
        </li>
        <li>2006-04-22: added option for setting custom CA certs to verify peer with in SSLmode</li>
        <li>2006-03-05: added option for setting Basic/Digest/NTLM auth type</li>
        <li>2006-01-18: added option echoing to screen xml-rpc request before sending it ('More' debug)</li>
        <li>2005-10-01: added option for setting cookies to be sent to server</li>
        <li>2005-08-07: added switches for compression of requests and responses and http 1.1</li>
        <li>2005-06-27: fixed possible security breach in parsing malformed xml</li>
        <li>2005-06-24: fixed error with calling methods having parameters...</li>
    </ul>
<?php

}
?>
</body>
</html>
