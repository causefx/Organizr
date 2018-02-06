<?php

namespace Buzz\Listener;

use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

class DigestAuthListener implements ListenerInterface
{
    private $username;
    private $password;
    private $realm;

    private $algorithm;
    private $authenticationMethod;
    private $clientNonce;
    private $domain;
    private $entityBody;
    private $method;
    private $nonce;
    private $nonceCount;
    private $opaque;
    private $uri;

    /**
     * QOP options: Only one of the following can be set at any time. setOptions will throw an exception otherwise.
     * OPTION_QOP_AUTH_INT       - Always use auth-int   (if available)
     * OPTION_QOP_AUTH           - Always use auth       (even if auth-int available)
     */
    const OPTION_QOP_AUTH_INT             = 1;
    const OPTION_QOP_AUTH                 = 2;
    /**
     * Ignore server request to downgrade authentication from Digest to Basic.
     * Breaks RFC compatibility, but ensures passwords are never sent using base64 which is trivial for an attacker to decode.
     */
    const OPTION_IGNORE_DOWNGRADE_REQUEST = 4;
    /**
     * Discard Client Nonce on each request.
     */
    const OPTION_DISCARD_CLIENT_NONCE     = 8;

    private $options;

    /**
     * Set OPTION_QOP_BEST_AVAILABLE and OPTION_DISCARD_CLIENT_NONCE by default.
     */
    public function __construct($username = null, $password = null, $realm = null)
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setRealm($realm);
        $this->setOptions(DigestAuthListener::OPTION_QOP_AUTH_INT & DigestAuthListener::OPTION_DISCARD_CLIENT_NONCE);
    }

    /**
     * Passes the returned server headers to parseServerHeaders() to check if any authentication variables need to be set.
     * Inteprets the returned status code and attempts authentication if status is 401 (Authentication Required) by resending
     * the last request with an Authentication header.
     *
     * @param RequestInterface $request  A request object
     * @param MessageInterface $response A response object
     */
    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        $statusCode = $response->getStatusCode();
        $this->parseServerHeaders($response->getHeaders(), $statusCode);
    }

    /**
     * Populates uri, method and entityBody used to generate the Authentication header using the specified request object.
     * Appends the Authentication header if it is present and has been able to be calculated.
     *
     * @param RequestInterface $request  A request object
     */
    public function preSend(RequestInterface $request)
    {
        $this->setUri($request->getResource());
        $this->setMethod($request->getMethod());
        $this->setEntityBody($request->getContent());

        $header = $this->getHeader();
        if($header) {
            $request->addHeader($header);
        }
    }

    /**
     * Sets the password to be used to authenticate the client.
     *
     * @param string $password The password
     *
     * @return void
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Sets the realm to be used to authenticate the client.
     *
     * @param string $realm The realm
     *
     * @return void
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * Sets the username to be used to authenticate the client.
     *
     * @param string $username The username
     *
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Sets the options to be used by this class.
     *
     * @param int $options A bitmask of the constants defined in this class.
     *
     * @return void
     */
    public function setOptions($options)
    {
        if(($options & DigestAuthListener::OPTION_QOP_AUTH_INT) === true) {
            if(($options & DigestAuthListener::OPTION_QOP_AUTH) === true) {
                throw new \InvalidArgumentException('DigestAuthListener: Only one value of OPTION_QOP_AUTH_INT or OPTION_QOP_AUTH may be set.');
            }
            $this->options = $this->options | DigestAuthListener::OPTION_QOP_AUTH_INT;
        } else {
            if(($options & DigestAuthListener::OPTION_QOP_AUTH) === true) {
                $this->options = $this->options | DigestAuthListener::OPTION_QOP_AUTH;
            }
        }

        if(($options & DigestAuthListener::OPTION_IGNORE_DOWNGRADE_REQUEST) === true) {
            $this->options = $this->options | DigestAuthListener::OPTION_IGNORE_DOWNGRADE_REQUEST;
        }

        if(($options & DigestAuthListener::OPTION_DISCARD_CLIENT_NONCE) === true) {
            $this->options = $this->options | DigestAuthListener::OPTION_DISCARD_CLIENT_NONCE;
        }
    }

    /**
     * Discards the Client Nonce forcing the generation of a new Client Nonce on the next request.
     *
     * @return void
     */
    private function discardClientNonce()
    {
        $this->clientNonce = null;
    }

    /**
     * Returns the hashing algorithm to be used to generate the digest value. Currently only returns MD5.
     *
     * @return string The hashing algorithm to be used.
     */
    private function getAlgorithm()
    {
        if($this->algorithm == null) {
            $this->algorithm = 'MD5';
        }
        return $this->algorithm;
    }

    /**
     * Returns the authentication method requested by the server.
     * If OPTION_IGNORE_DOWNGRADE_REQUEST is set this will always return "Digest"
     *
     * @return string Returns either "Digest" or "Basic".
     */
    private function getAuthenticationMethod()
    {
        if(($this->options & DigestAuthListener::OPTION_IGNORE_DOWNGRADE_REQUEST) === true) {
            return "Digest";
        }
        return $this->authenticationMethod;
    }

    /**
     * Returns either the current value of clientNonce or generates a new value if clientNonce is null.
     * Also increments nonceCount.
     *
     * @return string Returns either the current value of clientNonce the newly generated clientNonce;
     */
    private function getClientNonce()
    {
        if($this->clientNonce == null) {
            $this->clientNonce = uniqid();

            if($this->nonceCount == null) {
// If nonceCount is not set then set it to 00000001.
                $this->nonceCount = '00000001';
            } else {
// If it is set then increment it.
                $this->nonceCount++;
// Ensure nonceCount is zero-padded at the start of the string to a length of 8
                while(strlen($this->nonceCount) < 8) {
                    $this->nonceCount = '0' . $this->nonceCount;
                }
            }
        }
        return $this->clientNonce;
    }

    /**
     * Returns a space separated list of uris that the server nonce can be used to generate an authentication response against.
     *
     * @return string Space separated list of uris.
     */
    private function getDomain()
    {
        return $this->domain;
    }

    /**
     * Returns the entity body of the current request.
     * The entity body is the request before it has been encoded with the content-encoding and minus the request headers.
     *
     * @return string The full entity-body.
     */
    private function getEntityBody()
    {
        return (string)$this->entityBody;
    }

    /**
     * Calculates the value of HA1 according to RFC 2617 and RFC 2069.
     *
     * @return string The value of HA1
     */
    private function getHA1()
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        $realm = $this->getRealm();

        if(($username) AND ($password) AND ($realm)) {
            $algorithm = $this->getAlgorithm();

            if(!isset($algorithm) OR ($algorithm == "MD5")) {

                $A1 = "{$username}:{$realm}:{$password}";
            }
            if($this->algorithm == "MD5-sess") {

                $nonce = $this->getNonce();
                $cnonce = $this->getClientNonce();
                if(($nonce) AND ($cnonce)) {
                    $A1 = $this->hash("{$username}:{$realm}:{$password}") . ":{$nonce}:{$cnonce}";              
                }
            }
            if(isset($A1)) {
                $HA1 = $this->hash($A1);
                return $HA1;
            }
        }
        return null;
    }

    /**
     * Calculates the value of HA2 according to RFC 2617 and RFC 2069.
     *
     * @return string The value of HA2
     */
    private function getHA2()
    {
        $method = $this->getMethod();
        $uri = $this->getUri();

        if(($method) AND ($uri)) {
            $qop = $this->getQOP();

            if(!isset($qop) OR ($qop == 'auth')) {
                $A2 = "{$method}:{$uri}";
            }
            if($qop == 'auth-int') {
                $entityBody = $this->getEntityBody();
                $A2 = "{$method}:{$uri}:" . $this->hash($entityBody);
            }

            if(isset($A2)) {
                $HA2 = $this->hash($A2);
                return $HA2;
            }           
        }
        return null;
    }

    /**
     * Returns the full Authentication header for use in authenticating the client with either Digest or Basic authentication.
     *
     * @return string The Authentication header to be sent to the server.
     */
    private function getHeader()
    {
        if($this->getAuthenticationMethod() == 'Digest') {
            $username = $this->getUsername();
            $realm = $this->getRealm();
            $nonce = $this->getNonce();
            $response = $this->getResponse();
            if(($username) AND ($realm) AND ($nonce) AND ($response)) {
                $uri = $this->getUri();
                $opaque = $this->getOpaque();
                $domain = $this->getDomain();
                $qop = $this->getQOP();

                $header = "Authorization: Digest";
                $header .= " username=\"" . $username . "\",";
                $header .= " realm=\"" . $realm . "\",";
                $header .= " nonce=\"" . $nonce . "\",";
                $header .= " response=\"" . $response . "\",";

                if($uri) {
                    $header .= " uri=\"" . $uri . "\",";
                }
                if($opaque) {
                    $header .= " opaque=\"" . $opaque . "\",";
                }

                if($qop) {
                    $header .= " qop=" . $qop . ",";
                
                    $cnonce = $this->getClientNonce();
                    $nc = $this->getNonceCount();

                    if($cnonce) {
                        $header .= " nc=" . $nc . ",";
                    }
                    if($cnonce) {
                        $header .= " cnonce=\"" . $cnonce . "\",";
                    }
                }

// Remove the last comma from the header
                $header = substr($header, 0, strlen($header) - 1);
// Discard the Client Nonce if OPTION_DISCARD_CLIENT_NONCE is set.
                if(($this->options & DigestAuthListener::OPTION_DISCARD_CLIENT_NONCE) === true) {
                    $this->discardClientNonce();
                }
                return $header;
            }
        }
        if($this->getAuthenticationMethod() == 'Basic') {
            $username = $this->getUsername();
            $password = $this->getPassword();
            if(($username) AND ($password)) {
                $header = 'Authorization: Basic ' . base64_encode("{$username}:{$password}");
                return $header;
            }
        }
        return null;
    }

    /**
     * Returns the HTTP method used in the current request.
     *
     * @return string One of GET,POST,PUT,DELETE or HEAD.
     */
    private function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the value of nonce we have received in the server headers.
     *
     * @return string The value of the server nonce.
     */
    private function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Returns the current nonce counter for the client nonce.
     *
     * @return string An eight digit zero-padded string which reflects the number of times the clientNonce has been generated.
     */
    private function getNonceCount()
    {
        return $this->nonceCount;
    }

    /**
     * Returns the opaque value that was sent to us from the server.
     *
     * @return string The value of opaque.
     */
    private function getOpaque()
    {
        return $this->opaque;
    }

    /**
     * Returns the plaintext password for the client.
     *
     * @return string The value of password.
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns either the realm specified by the client, or the realm specified by the server.
     * If the server set the value of realm then anything set by our client is overwritten.
     *
     * @return string The value of realm.
     */
    private function getRealm()
    {
        return $this->realm;
    }

    /**
     * Calculates the value of response according to RFC 2617 and RFC 2069.
     *
     * @return string The value of response
     */
    private function getResponse()
    {
        $HA1 = $this->getHA1();
        $nonce = $this->getNonce();
        $HA2 = $this->getHA2();

        if(($HA1) AND ($nonce) AND ($HA2)) {
            $qop = $this->getQOP();

            if(!isset($qop)) {
                $response = $this->hash("{$HA1}:{$nonce}:{$HA2}");
                return $response;
            } else {
                $cnonce = $this->getClientNonce();
                $nc = $this->getNonceCount();
                if(($cnonce) AND ($nc)) {
                    $response = $this->hash("{$HA1}:{$nonce}:{$nc}:{$cnonce}:{$qop}:{$HA2}");
                    return $response;
                }
            }
        }
        return null;
    }

    /**
     * Returns the Quality of Protection to be used when authenticating with the server.
     *
     * @return string This will either be auth-int or auth.
     */
    private function getQOP()
    {
// Has the server specified any options for Quality of Protection
        if(isset($this->qop) AND count($this->qop)) {
            if(($this->options & DigestAuthListener::OPTION_QOP_AUTH_INT) === true) {
                if(in_array('auth-int', $this->qop)) {
                    return 'auth-int';
                }
                if(in_array('auth', $this->qop)) {
                    return 'auth';
                }
            }
            if(($this->options & DigestAuthListener::OPTION_QOP_AUTH) === true) {
                if(in_array('auth', $this->qop)) {
                    return 'auth';
                }
                if(in_array('auth-int', $this->qop)) {
                    return 'auth-int';
                }
            }            
        }
// Server has not specified any value for Quality of Protection so return null
        return null;
    }

    /**
     * Returns the username set by the client to authenticate with the server.
     *
     * @return string The value of username
     */
    private function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the uri that we are requesting access to.
     *
     * @return string The value of uri
     */
    private function getUri()
    {
        return $this->uri;
    }

    /**
     * Calculates the hash for a given value using the algorithm specified by the server.
     *
     * @param string $value The value to be hashed
     *
     * @return string The hashed value.
     */
    private function hash($value)
    {
        $algorithm = $this->getAlgorithm();
        if(($algorithm == 'MD5') OR ($algorithm == 'MD5-sess')) {
            return hash('md5', $value);
        }
        return null;
    }

    /**
     * Parses the Authentication-Info header received from the server and calls the relevant setter method on each variable received.
     *
     * @param string $authenticationInfo The full Authentication-Info header.
     *
     * @return void
     */
    private function parseAuthenticationInfoHeader($authenticationInfo)
    {
// Remove "Authentication-Info: " from start of header
        $wwwAuthenticate = substr($wwwAuthenticate, 21, strlen($wwwAuthenticate) - 21);

        $nameValuePairs = $this->parseNameValuePairs($wwwAuthenticate);
        foreach($nameValuePairs as $name => $value) {
            switch($name) {
                case 'message-qop':

                break;
                case 'nextnonce':
// This function needs to only set the Nonce once the rspauth has been verified.
                    $this->setNonce($value);
                break;
                case 'rspauth':
// Check server rspauth value
                break;
            }
        }
    }

    /**
     * Parses a string of name=value pairs separated by commas and returns and array with the name as the index.
     *
     * @param string $nameValuePairs The string containing the name=value pairs.
     *
     * @return array An array with the name used as the index and the values stored within.
     */
    private function parseNameValuePairs($nameValuePairs)
    {
        $parsedNameValuePairs = array();
        $nameValuePairs = explode(',', $nameValuePairs);
        foreach($nameValuePairs as $nameValuePair) {
// Trim the Whitespace from the start and end of the name value pair string
            $nameValuePair = trim($nameValuePair);
// Split $nameValuePair (name=value) into $name and $value
            list($name, $value) = explode('=', $nameValuePair, 2);
// Remove quotes if the string is quoted
            $value = $this->unquoteString($value);
// Add pair to array[name] => value
            $parsedNameValuePairs[$name] = $value;
        }
        return $parsedNameValuePairs;
    }

    /**
     * Parses the server headers received and checks for WWW-Authenticate and Authentication-Info headers.
     * Calls parseWwwAuthenticateHeader() and parseAuthenticationInfoHeader() respectively if either of these headers are present.
     *
     * @param array $headers An array of the headers received by the client.
     *
     * @return void
     */
    private function parseServerHeaders(array $headers)
    {
        foreach($headers as $header) {
// Check to see if the WWW-Authenticate header is present and if so set $authHeader
            if(strtolower(substr($header, 0, 18)) == 'www-authenticate: ') {
                $wwwAuthenticate = $header;
                $this->parseWwwAuthenticateHeader($wwwAuthenticate);
            }
// Check to see if the Authentication-Info header is present and if so set $authInfo
            if(strtolower(substr($header, 0, 21)) == 'authentication-info: ') {
                $authenticationInfo = $header;
                $this->parseAuthenticationInfoHeader($wwwAuthenticate);
            }
        }
    }

    /**
     * Parses the WWW-Authenticate header received from the server and calls the relevant setter method on each variable received.
     *
     * @param string $wwwAuthenticate The full WWW-Authenticate header.
     *
     * @return void
     */
    private function parseWwwAuthenticateHeader($wwwAuthenticate)
    {
// Remove "WWW-Authenticate: " from start of header
        $wwwAuthenticate = substr($wwwAuthenticate, 18, strlen($wwwAuthenticate) - 18);
        if(substr($wwwAuthenticate, 0, 7) == 'Digest ') {
            $this->setAuthenticationMethod('Digest');
// Remove "Digest " from start of header
            $wwwAuthenticate = substr($wwwAuthenticate, 7, strlen($wwwAuthenticate) - 7);

            $nameValuePairs = $this->parseNameValuePairs($wwwAuthenticate);

            foreach($nameValuePairs as $name => $value) {
                switch($name) {
                    case 'algorithm':
                        $this->setAlgorithm($value);
                    break;
                    case 'domain':
                        $this->setDomain($value);
                    break;
                    case 'nonce':
                        $this->setNonce($value);
                    break;
                    case 'realm':
                        $this->setRealm($value);
                    break;
                    case 'opaque':
                        $this->setOpaque($value);
                    break;
                    case 'qop':
                        $this->setQOP(explode(',', $value));
                    break;
                }
            }
        }
        if (substr($wwwAuthenticate, 0, 6) == 'Basic ') {
            $this->setAuthenticationMethod('Basic');
// Remove "Basic " from start of header
            $wwwAuthenticate = substr($wwwAuthenticate, 6, strlen($wwwAuthenticate) - 6);

            $nameValuePairs = $this->parseNameValuePairs($wwwAuthenticate);

            foreach($nameValuePairs as $name => $value) {
                switch($name) {
                    case 'realm':
                        $this->setRealm($value);
                    break;
                }
            }
        }
    }

    /**
     * Sets the hashing algorithm to be used. Currently only uses MD5 specified by either MD5 or MD5-sess.
     * RFCs are currently in draft stage for the proposal of SHA-256 and SHA-512-256.
     * Support will be added once the RFC leaves the draft stage.
     *
     * @param string $algorithm The algorithm the server has requested to use.
     *
     * @throws \InvalidArgumentException If $algorithm is set to anything other than MD5 or MD5-sess.
     *
     * @return void
     */
    private function setAlgorithm($algorithm)
    {
        if(($algorithm == 'MD5') OR ($algorithm == 'MD5-sess')) {
            $this->algorithm = $algorithm;
        } else {
            throw new \InvalidArgumentException('DigestAuthListener: Only MD5 and MD5-sess algorithms are currently supported.');
        }
    }

    /**
     * Sets authentication method to be used. Options are "Digest" and "Basic".
     * If the server and the client are unable to authenticate using Digest then the RFCs state that the server should attempt
     * to authenticate the client using Basic authentication. This ensures that we adhere to that behaviour.
     * This does however create the possibilty of a downgrade attack so it may be an idea to add a way of disabling this functionality
     * as Basic authentication is trivial to decrypt and exposes the username/password to a man-in-the-middle attack.
     *
     * @param string $authenticationMethod The authentication method requested by the server.
     *
     * @throws \InvalidArgumentException If $authenticationMethod is set to anything other than Digest or Basic
     *
     * @return void
     */
    private function setAuthenticationMethod($authenticationMethod)
    {
        if(($authenticationMethod == 'Digest') OR ($authenticationMethod == 'Basic')) {
            $this->authenticationMethod = $authenticationMethod;
        } else {
            throw new \InvalidArgumentException('DigestAuthListener: Only Digest and Basic authentication methods are currently supported.');
        }
    }

    /**
     * Sets the domain to be authenticated against. THIS IS NOT TO BE CONFUSED WITH THE HOSTNAME/DOMAIN.
     * This is specified by the RFC to be a list of uris separated by spaces that the client will be allowed to access.
     * An RFC in draft stage is proposing the removal of this functionality, it does not seem to be in widespread use.
     *
     * @param string $domain The list of uris separated by spaces that the client will be able to access upon successful authentication.
     *
     * @return void
     */
    private function setDomain($value)
    {
        $this->domain = $value;
    }

    /**
     * Sets the Entity Body of the Request for use with qop=auth-int
     *
     * @param string $entityBody The body of the entity (The unencoded request minus the headers).
     *
     * @return void
     */
    private function setEntityBody($entityBody = null)
    {
        $this->entityBody = $entityBody;
    }

    /**
     * Sets the HTTP method being used for the request
     *
     * @param string $method The HTTP method
     *
     * @throws \InvalidArgumentException If $method is set to anything other than GET,POST,PUT,DELETE or HEAD.
     *
     * @return void
     */
    private function setMethod($method = null)
    {
        if($method == 'GET') {
            $this->method = 'GET';
            return;
        }
        if($method == 'POST') {
            $this->method = 'POST';
            return;
        }
        if($method == 'PUT') {
            $this->method = 'PUT';
            return;
        }
        if($method == 'DELETE') {
            $this->method = 'DELETE';
            return;
        }
        if($method == 'HEAD') {
            $this->method = 'HEAD';
            return;
        }
        throw new \InvalidArgumentException('DigestAuthListener: Only GET,POST,PUT,DELETE,HEAD HTTP methods are currently supported.');
    }

    /**
     * Sets the value of nonce
     *
     * @param string $opaque The server nonce value
     *
     * @return void
     */
    private function setNonce($nonce = null)
    {
        $this->nonce = $nonce;
    }

    /**
     * Sets the value of opaque
     *
     * @param string $opaque The opaque value
     *
     * @return void
     */
    private function setOpaque($opaque)
    {
        $this->opaque = $opaque;
    }

    /**
     * Sets the acceptable value(s) for the quality of protection used by the server. Supported values are auth and auth-int.
     * TODO: This method should give precedence to using qop=auth-int first as this offers integrity protection.
     *
     * @param array $qop An array with the values of qop that the server has specified it will accept.
     *
     * @throws \InvalidArgumentException If $qop contains any values other than auth-int or auth.
     *
     * @return void
     */
    private function setQOP(array $qop = array())
    {
        $this->qop = array();
        foreach($qop as $protection) {
            $protection = trim($protection);
            if($protection == 'auth-int') {
                $this->qop[] = 'auth-int';
            } elseif($protection == 'auth') {
                $this->qop[] = 'auth';
            } else {
                throw new \InvalidArgumentException('DigestAuthListener: Only auth-int and auth are supported Quality of Protection mechanisms.');
            }
        }
    }

    /**
     * Sets the value of uri
     *
     * @param string $uri The uri
     *
     * @return void
     */
    private function setUri($uri = null)
    {
        $this->uri = $uri;
    }

    /**
     * If a string contains quotation marks at either end this function will strip them. Otherwise it will remain unchanged.
     *
     * @param string $str The string to be stripped of quotation marks.
     *
     * @return string Returns the original string without the quotation marks at either end.
     */
    private function unquoteString($str = null)
    {
        if($str) {
            if(substr($str, 0, 1) == '"') {
                $str = substr($str, 1, strlen($str) - 1);
            }
            if(substr($str, strlen($str) - 1, 1) == '"') {
                $str = substr($str, 0, strlen($str) - 1);
            }
        }
        return $str;
    }
}