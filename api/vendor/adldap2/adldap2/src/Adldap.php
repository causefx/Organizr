<?php

namespace Adldap;

use InvalidArgumentException;
use Adldap\Connections\Provider;
use Adldap\Schemas\SchemaInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Connections\ConnectionInterface;
use Adldap\Configuration\DomainConfiguration;

class Adldap implements AdldapInterface
{
    /**
     * The default provider name.
     *
     * @var string
     */
    protected $default = 'default';

    /**
     * The connection providers.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $name => $config) {
            $this->addProvider($config, $name);
        }

        if ($default = key($providers)) {
            $this->setDefaultProvider($default);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addProvider($config = [], $name = 'default', ConnectionInterface $connection = null, SchemaInterface $schema = null)
    {
        if ($this->isValidConfig($config)) {
            $config = new Provider($config, $connection, $schema);
        }

        if ($config instanceof ProviderInterface) {
            $this->providers[$name] = $config;

            return $this;
        }

        throw new InvalidArgumentException(
            "You must provide a configuration array or an instance of Adldap\Connections\ProviderInterface."
        );
    }

    /**
     * Determines if the given config is valid.
     *
     * @param mixed $config
     *
     * @return bool
     */
    protected function isValidConfig($config)
    {
        return is_array($config) || $config instanceof DomainConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider($name)
    {
        if (array_key_exists($name, $this->providers)) {
            return $this->providers[$name];
        }

        throw new AdldapException("The connection provider '$name' does not exist.");
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultProvider($name = 'default')
    {
        if ($this->getProvider($name) instanceof ProviderInterface) {
            $this->default = $name;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultProvider()
    {
        return $this->getProvider($this->default);
    }

    /**
     * {@inheritdoc}
     */
    public function removeProvider($name)
    {
        unset($this->providers[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connect($name = null, $username = null, $password = null)
    {
        $provider = $name ? $this->getProvider($name) : $this->getDefaultProvider();
        
        return $provider->connect($username, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $parameters)
    {
        $provider = $this->getDefaultProvider();

        if (!$provider->getConnection()->isBound()) {
            // We'll make sure we have a bound connection before
            // allowing dynamic calls on the default provider.
            $provider->connect();
        }

        return call_user_func_array([$provider, $method], $parameters);
    }
}
