<?php

namespace Adldap\Query;

use Adldap\Models\RootDse;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Schemas\SchemaInterface;
use Adldap\Connections\ConnectionInterface;

/**
 * Adldap2 Search Factory.
 *
 * @package Adldap\Search
 *
 * @mixin Builder
 */
class Factory
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * Stores the current query builder instance.
     *
     * @var Builder
     */
    protected $query;

    /**
     * Stores the current schema instance.
     *
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Constructor.
     *
     * @param ConnectionInterface  $connection
     * @param SchemaInterface|null $schema
     * @param string               $baseDn
     */
    public function __construct(ConnectionInterface $connection, SchemaInterface $schema = null, $baseDn = '')
    {
        $this->setConnection($connection)
            ->setSchema($schema)
            ->setQuery($this->newQuery($baseDn));
    }

    /**
     * Sets the connection property.
     *
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Sets the query property.
     *
     * @param Builder $query
     *
     * @return $this
     */
    public function setQuery(Builder $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Sets the schema property.
     *
     * @param SchemaInterface|null $schema
     *
     * @return $this
     */
    public function setSchema(SchemaInterface $schema = null)
    {
        $this->schema = $schema ?: new ActiveDirectory();

        return $this;
    }

    /**
     * Returns a new query builder instance.
     *
     * @param string $baseDn
     *
     * @return Builder
     */
    public function newQuery($baseDn = '')
    {
        return (new Builder($this->connection, $this->newGrammar(), $this->schema))
            ->in($baseDn);
    }

    /**
     * Returns the current query Builder instance.
     *
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Returns a new query grammar instance.
     *
     * @return Grammar
     */
    public function newGrammar()
    {
        return new Grammar();
    }

    /**
     * Performs a global 'all' search query on the current
     * connection by performing a search for all entries
     * that contain a common name attribute.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function all()
    {
        return $this->query->whereHas($this->schema->commonName())->get();
    }

    /**
     * Alias for the `all()` method.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function get()
    {
        return $this->all();
    }

    /**
     * Returns a query builder limited to users.
     *
     * @return Builder
     */
    public function users()
    {
        return $this->where([
            $this->schema->objectClass()    => $this->schema->objectClassPerson(),
            $this->schema->objectCategory() => $this->schema->objectCategoryPerson(),
        ]);
    }

    /**
     * Returns a query builder limited to printers.
     *
     * @return Builder
     */
    public function printers()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassPrinter(),
        ]);
    }

    /**
     * Returns a query builder limited to organizational units.
     *
     * @return Builder
     */
    public function ous()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassOu(),
        ]);
    }

    /**
     * Returns a query builder limited to groups.
     *
     * @return Builder
     */
    public function groups()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassGroup(),
        ]);
    }

    /**
     * Returns a query builder limited to exchange servers.
     *
     * @return Builder
     */
    public function containers()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassContainer(),
        ]);
    }

    /**
     * Returns a query builder limited to exchange servers.
     *
     * @return Builder
     */
    public function contacts()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassContact(),
        ]);
    }

    /**
     * Returns a query builder limited to exchange servers.
     *
     * @return Builder
     */
    public function computers()
    {
        return $this->where([
            $this->schema->objectClass() => $this->schema->objectClassComputer(),
        ]);
    }

    /**
     * Returns the root DSE record.
     *
     * @return RootDse|null
     */
    public function getRootDse()
    {
        $root = $this->query->newInstance()
            ->in('')
            ->read(true)
            ->whereHas($this->schema->objectClass())
            ->first();

        if ($root) {
            return (new RootDse([], $this->query))
                ->setRawAttributes($root->getAttributes());
        }
    }

    /**
     * Handle dynamic method calls on the query builder object.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->query->newInstance();

        return call_user_func_array([$query, $method], $parameters);
    }
}
