<?php

namespace Adldap\Connections;

/**
 * Class Ldap
 *
 * A class that abstracts PHP's LDAP functions and stores the bound connection.
 *
 * @package Adldap\Connections
 */
class Ldap implements ConnectionInterface
{
    /**
     * The connection name.
     * 
     * @var string|null
     */
    protected $name;

    /**
     * The LDAP host that is currently connected.
     *
     * @var string|null
     */
    protected $host;

    /**
     * The active LDAP connection.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Stores the bool whether or not
     * the current connection is bound.
     *
     * @var bool
     */
    protected $bound = false;

    /**
     * Stores the bool to tell the connection
     * whether or not to use SSL.
     *
     * To use SSL, your server must support LDAP over SSL.
     * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
     *
     * @var bool
     */
    protected $useSSL = false;

    /**
     * Stores the bool to tell the connection
     * whether or not to use TLS.
     *
     * If you wish to use TLS you should ensure that $useSSL is set to false and vice-versa
     *
     * @var bool
     */
    protected $useTLS = false;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingSSL()
    {
        return $this->useSSL;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsingTLS()
    {
        return $this->useTLS;
    }

    /**
     * {@inheritdoc}
     */
    public function isBound()
    {
        return $this->bound;
    }

    /**
     * {@inheritdoc}
     */
    public function canChangePasswords()
    {
        return $this->isUsingSSL() || $this->isUsingTLS();
    }

    /**
     * {@inheritdoc}
     */
    public function ssl($enabled = true)
    {
        $this->useSSL = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function tls($enabled = true)
    {
        $this->useTLS = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntries($searchResults)
    {
        return ldap_get_entries($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstEntry($searchResults)
    {
        return ldap_first_entry($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextEntry($entry)
    {
        return ldap_next_entry($this->getConnection(), $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($entry)
    {
        return ldap_get_attributes($this->getConnection(), $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function countEntries($searchResults)
    {
        return ldap_count_entries($this->getConnection(), $searchResults);
    }

    /**
     * {@inheritdoc}
     */
    public function compare($dn, $attribute, $value)
    {
        return ldap_compare($this->getConnection(), $dn, $attribute, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError()
    {
        return ldap_error($this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailedError()
    {
        // If the returned error number is zero, the last LDAP operation
        // succeeded. We won't return a detailed error.
        if ($number = $this->errNo()) {
            ldap_get_option($this->getConnection(), LDAP_OPT_DIAGNOSTIC_MESSAGE, $message);

            return new DetailedError($number, $this->err2Str($number), $message);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getValuesLen($entry, $attribute)
    {
        return ldap_get_values_len($this->getConnection(), $entry, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($option, $value)
    {
        return ldap_set_option($this->getConnection(), $option, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options = [])
    {
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRebindCallback(callable $callback)
    {
        return ldap_set_rebind_proc($this->getConnection(), $callback);
    }

    /**
     * {@inheritdoc}
     */
    public function startTLS()
    {
        return ldap_start_tls($this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function connect($hosts = [], $port = '389')
    {
        $this->host = $this->getConnectionString($hosts, $this->getProtocol(), $port);

        return $this->connection = ldap_connect($this->host);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $connection = $this->getConnection();

        return is_resource($connection) ? ldap_close($connection) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function search($dn, $filter, array $fields, $onlyAttributes = false, $size = 0, $time = 0)
    {
        return ldap_search($this->getConnection(), $dn, $filter, $fields, $onlyAttributes, $size, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function listing($dn, $filter, array $fields, $onlyAttributes = false, $size = 0, $time = 0)
    {
        return ldap_list($this->getConnection(), $dn, $filter, $fields, $onlyAttributes, $size, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function read($dn, $filter, array $fields, $onlyAttributes = false, $size = 0, $time = 0)
    {
        return ldap_read($this->getConnection(), $dn, $filter, $fields, $onlyAttributes, $size, $time);
    }

    /**
     * {@inheritdoc}
     */
    public function bind($username, $password, $sasl = false)
    {
        if ($this->isUsingTLS() && $this->startTLS() === false) {
            throw new ConnectionException("Unable to connect to LDAP server over TLS.");
        }

        if ($sasl) {
            return $this->bound = ldap_sasl_bind($this->getConnection(), null, null, 'GSSAPI');
        }

        return $this->bound = ldap_bind($this->getConnection(), $username, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function add($dn, array $entry)
    {
        return ldap_add($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($dn)
    {
        return ldap_delete($this->getConnection(), $dn);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($dn, $newRdn, $newParent, $deleteOldRdn = false)
    {
        return ldap_rename($this->getConnection(), $dn, $newRdn, $newParent, $deleteOldRdn);
    }

    /**
     * {@inheritdoc}
     */
    public function modify($dn, array $entry)
    {
        return ldap_modify($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyBatch($dn, array $values)
    {
        return ldap_modify_batch($this->getConnection(), $dn, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function modAdd($dn, array $entry)
    {
        return ldap_mod_add($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modReplace($dn, array $entry)
    {
        return ldap_mod_replace($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function modDelete($dn, array $entry)
    {
        return ldap_mod_del($this->getConnection(), $dn, $entry);
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResult($pageSize = 1000, $isCritical = false, $cookie = '')
    {
        return ldap_control_paged_result($this->getConnection(), $pageSize, $isCritical, $cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function controlPagedResultResponse($result, &$cookie)
    {
        return ldap_control_paged_result_response($this->getConnection(), $result, $cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function errNo()
    {
        return ldap_errno($this->getConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedError()
    {
        return $this->getDiagnosticMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedErrorHex()
    {
        if (preg_match("/(?<=data\s).*?(?=\,)/", $this->getExtendedError(), $code)) {
            return $code[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedErrorCode()
    {
        return $this->extractDiagnosticCode($this->getExtendedError());
    }

    /**
     * {@inheritdoc}
     */
    public function err2Str($number)
    {
        return ldap_err2str($number);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiagnosticMessage()
    {
        ldap_get_option($this->getConnection(), LDAP_OPT_ERROR_STRING, $message);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function extractDiagnosticCode($message)
    {
        preg_match('/^([\da-fA-F]+):/', $message, $matches);

        return isset($matches[1]) ? $matches[1] : false;
    }

    /**
     * Returns the LDAP protocol to utilize for the current connection.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->isUsingSSL() ? $this::PROTOCOL_SSL : $this::PROTOCOL;
    }

    /**
     * Generates an LDAP connection string for each host given.
     *
     * @param string|array  $hosts
     * @param string        $protocol
     * @param string        $port
     *
     * @return string
     */
    protected function getConnectionString($hosts, $protocol, $port)
    {
        // Normalize hosts into an array.
        $hosts = is_array($hosts) ? $hosts : [$hosts];

        $hosts = array_map(function ($host) use ($protocol, $port) {
            return "{$protocol}{$host}:{$port}";
        }, $hosts);

        return implode(' ', $hosts);
    }
}
