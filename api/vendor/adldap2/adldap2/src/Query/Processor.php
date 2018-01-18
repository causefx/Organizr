<?php

namespace Adldap\Query;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Adldap\Models\Entry;
use Adldap\Models\Model;
use Adldap\Schemas\SchemaInterface;
use Adldap\Connections\ConnectionInterface;

class Processor
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Constructor.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->connection = $builder->getConnection();
        $this->schema = $builder->getSchema();
    }

    /**
     * Processes LDAP search results and constructs their model instances.
     *
     * @param resource $results
     *
     * @return array
     */
    public function process($results)
    {
        // Normalize entries. Get entries returns false on failure.
        // We'll always want an array in this situation.
        $entries = $this->connection->getEntries($results) ?: [];

        if ($this->builder->isRaw()) {
            // If the builder is asking for a raw
            // LDAP result, we can return here.
            return $entries;
        }

        $models = [];

        if (Arr::has($entries, 'count')) {
            for ($i = 0; $i < $entries['count']; $i++) {
                // We'll go through each entry and construct a new
                // model instance with the raw LDAP attributes.
                $models[] = $this->newLdapEntry($entries[$i]);
            }
        }

        if (!$this->builder->isPaginated()) {
            // If the current query isn't paginated,
            // we'll sort the models array here.
            $models = $this->processSort($models);
        }

        return $models;
    }

    /**
     * Processes paginated LDAP results.
     *
     * @param array $pages
     * @param int   $perPage
     * @param int   $currentPage
     *
     * @return Paginator
     */
    public function processPaginated(array $pages = [], $perPage = 50, $currentPage = 0)
    {
        $models = [];

        foreach ($pages as $results) {
            // Go through each page and process the results into an objects array.
            $models = array_merge($models, $this->process($results));
        }

        $models = $this->processSort($models)->toArray();

        return $this->newPaginator($models, $perPage, $currentPage, count($pages));
    }

    /**
     * Returns a new LDAP Entry instance.
     *
     * @param array $attributes
     *
     * @return Entry
     */
    public function newLdapEntry(array $attributes = [])
    {
        $objectClass = $this->schema->objectClass();

        if (array_key_exists($objectClass, $attributes) && array_key_exists(0, $attributes[$objectClass])) {
            // Retrieve all of the object classes from the LDAP
            // entry and lowercase them for comparisons.
            $classes = array_map('strtolower', $attributes[$objectClass]);

            // Retrieve the model mapping.
            $models = $this->map();

            // Retrieve the object class mappings (with strtolower keys).
            $mappings = array_map('strtolower', array_keys($models));

            // Retrieve the model from the map using the entry's object class.
            $map = array_intersect($mappings, $classes);

            if (count($map) > 0) {
                // Retrieve the model using the object class.
                $model = $models[current($map)];

                // Construct and return a new model.
                return $this->newModel([], $model)
                    ->setRawAttributes($attributes);
            }
        }

        // A default entry model if the object class isn't found.
        return $this->newModel()->setRawAttributes($attributes);
    }

    /**
     * Creates a new model instance.
     *
     * @param array       $attributes
     * @param string|null $model
     *
     * @return mixed|Entry
     */
    public function newModel($attributes = [], $model = null)
    {
        $model = (class_exists($model) ? $model : Entry::class);

        return new $model($attributes, $this->builder);
    }

    /**
     * Returns a new Paginator object instance.
     *
     * @param array $models
     * @param int   $perPage
     * @param int   $currentPage
     * @param int   $pages
     *
     * @return Paginator
     */
    public function newPaginator(array $models = [], $perPage = 25, $currentPage = 0, $pages = 1)
    {
        return new Paginator($models, $perPage, $currentPage, $pages);
    }

    /**
     * Returns a new doctrine array collection instance.
     *
     * @param array $items
     *
     * @return Collection
     */
    public function newCollection(array $items = [])
    {
        return new Collection($items);
    }

    /**
     * Returns the object class model class mapping.
     *
     * @return array
     */
    public function map()
    {
        return [
            $this->schema->objectClassComputer()    => $this->schema->computerModel(),
            $this->schema->objectClassContact()     => $this->schema->contactModel(),
            $this->schema->objectClassPerson()      => $this->schema->userModel(),
            $this->schema->objectClassGroup()       => $this->schema->groupModel(),
            $this->schema->objectClassContainer()   => $this->schema->containerModel(),
            $this->schema->objectClassPrinter()     => $this->schema->printerModel(),
            $this->schema->objectClassOu()          => $this->schema->organizationalUnitModel(),
        ];
    }

    /**
     * Sorts LDAP search results.
     *
     * @param array $models
     *
     * @return Collection
     */
    protected function processSort(array $models = [])
    {
        $field = $this->builder->getSortByField();

        $flags = $this->builder->getSortByFlags();

        $direction = $this->builder->getSortByDirection();

        $desc = ($direction === 'desc' ? true : false);

        return $this->newCollection($models)->sortBy(function (Model $model) use ($field) {
            return $model->getFirstAttribute($field);
        }, $flags, $desc);
    }
}
