<?php

namespace Adldap\Configuration;

use Adldap\Connections\ConnectionInterface;
use Adldap\Configuration\Validators\ArrayValidator;
use Adldap\Configuration\Validators\StringOrNullValidator;
use Adldap\Configuration\Validators\BooleanValidator;
use Adldap\Configuration\Validators\IntegerValidator;

/**
 * Class DomainConfiguration
 *
 * Contains an array of configuration options for a single LDAP connection.
 *
 * @package Adldap\Configuration
 */
class DomainConfiguration
{
    /**
     * The configuration options array.
     *
     * The default values for each key indicate the type of value it requires.
     *
     * @var array
     */
    protected $options = [
        // An array of LDAP hosts.
        'domain_controllers' => [],

        // The global LDAP operation timeout limit in seconds.
        'timeout' => 5,

        // The LDAP version to utilize.
        'version' => 3,

        // The port to use for connecting to your hosts.
        'port' => ConnectionInterface::PORT,

        // The base distinguished name of your domain.
        'base_dn' => '',

        // Whether or not to use SSL when connecting to your hosts.
        'use_ssl' => false,

        // Whether or not to use TLS when connecting to your hosts.
        'use_tls' => false,

        // Whether or not follow referrals is enabled when performing LDAP operations.
        'follow_referrals' => false,

        // The account prefix to use when authenticating users.
        'account_prefix' => null,

        // The account suffix to use when authenticating users.
        'account_suffix' => null,

        // The username to connect to your hosts with.
        'admin_username' => '',

        // The password that is utilized with the above user.
        'admin_password' => '',

        // The account prefix to use when authenticating your admin account above.
        'admin_account_prefix' => null,

        // The account prefix to use when authenticating your admin account above.
        'admin_account_suffix' => null,

        // Custom LDAP options that you'd like to utilize.
        'custom_options' => [],
    ];

    /**
     * Constructor.
     *
     * @param array $options
     *
     * @throws ConfigurationException When an option value given is an invalid type.
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Sets a configuration option.
     *
     * Throws an exception if the specified option does
     * not exist, or if it's an invalid type.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws ConfigurationException When an option value given is an invalid type.
     */
    public function set($key, $value)
    {
        if($this->validate($key, $value)) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Returns the value for the specified configuration options.
     *
     * Throws an exception if the specified option does not exist.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws ConfigurationException When the option specified does not exist.
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->options[$key];
        }

        throw new ConfigurationException("Option {$key} does not exist.");
    }

    /**
     * Checks if a configuration option exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Validates the new configuration option against its
     * default value to ensure it's the correct type.
     *
     * If an invalid type is given, an exception is thrown.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     *
     * @throws ConfigurationException When an option value given is an invalid type.
     */
    protected function validate($key, $value)
    {
        $default = $this->get($key);

        if (is_array($default)) {
            $validator = new ArrayValidator($key, $value);
        } elseif (is_int($default)) {
            $validator = new IntegerValidator($key, $value);
        } elseif (is_bool($default)) {
            $validator = new BooleanValidator($key, $value);
        } else {
            $validator = new StringOrNullValidator($key, $value);
        }

        return $validator->validate();
    }
}
