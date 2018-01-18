<?php

namespace Adldap\Query;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Adldap\Utilities;
use Adldap\Models\Model;
use Adldap\Schemas\SchemaInterface;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Models\ModelNotFoundException;
use Adldap\Connections\ConnectionInterface;

class Builder
{
    /**
     * The selected columns to retrieve on the query.
     *
     * @var array
     */
    public $columns = ['*'];

    /**
     * The query filters.
     *
     * @var array
     */
    public $filters = [
        'and' => [],
        'or' => [],
        'raw' => [],
    ];

    /**
     * The size limit of the query.
     *
     * @var int
     */
    public $limit = 0;

    /**
     * Determines whether the current query is paginated.
     *
     * @var bool
     */
    public $paginated = false;

    /**
     * The field to sort search results by.
     *
     * @var string
     */
    protected $sortByField = '';

    /**
     * The direction to sort the results by.
     *
     * @var string
     */
    protected $sortByDirection = '';

    /**
     * The sort flags for sorting query results.
     *
     * @var int
     */
    protected $sortByFlags;

    /**
     * The distinguished name to perform searches upon.
     *
     * @var string|null
     */
    protected $dn;

    /**
     * The default query type.
     *
     * @var string
     */
    protected $type = 'search';

    /**
     * Determines whether or not to return LDAP results in their raw array format.
     *
     * @var bool
     */
    protected $raw = false;

    /**
     * Determines whether the query is nested.
     *
     * @var bool
     */
    protected $nested = false;

    /**
     * The current connection instance.
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * The current grammar instance.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * The current schema instance.
     *
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Constructor.
     *
     * @param ConnectionInterface  $connection
     * @param Grammar|null         $grammar
     * @param SchemaInterface|null $schema
     */
    public function __construct(ConnectionInterface $connection, Grammar $grammar = null, SchemaInterface $schema = null)
    {
        $this->setConnection($connection)
            ->setGrammar($grammar)
            ->setSchema($schema);
    }

    /**
     * Sets the current connection.
     *
     * @param ConnectionInterface $connection
     *
     * @return Builder
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Sets the current filter grammar.
     *
     * @param Grammar|null $grammar
     *
     * @return Builder
     */
    public function setGrammar(Grammar $grammar = null)
    {
        $this->grammar = $grammar ?: new Grammar();

        return $this;
    }

    /**
     * Sets the current schema.
     *
     * @param SchemaInterface|null $schema
     *
     * @return Builder
     */
    public function setSchema(SchemaInterface $schema = null)
    {
        $this->schema = $schema ?: new ActiveDirectory();

        return $this;
    }

    /**
     * Returns the current schema.
     *
     * @return SchemaInterface
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Returns a new Query Builder instance.
     *
     * @param string $baseDn
     *
     * @return Builder
     */
    public function newInstance($baseDn = null)
    {
        // We'll set the base DN of the new Builder so
        // developers don't need to do this manually.
        $dn = is_null($baseDn) ? $this->getDn() : $baseDn;

        return (new static($this->connection, $this->grammar, $this->schema))
            ->setDn($dn);
    }

    /**
     * Returns a new nested Query Builder instance.
     * 
     * @param Closure|null $closure
     * 
     * @return $this
     */
    public function newNestedInstance(Closure $closure = null)
    {
        $query = $this->newInstance()->nested();
        
        if ($closure) {
            call_user_func($closure, $query);
        }

        return $query;
    }

    /**
     * Returns the current query.
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function get()
    {
        // We'll mute any warnings / errors here. We just need to
        // know if any query results were returned.
        return @$this->query($this->getQuery());
    }

    /**
     * Compiles and returns the current query string.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->grammar->compile($this);
    }

    /**
     * Returns the unescaped query.
     *
     * @return string
     */
    public function getUnescapedQuery()
    {
        return Utilities::unescape($this->getQuery());
    }

    /**
     * Returns the current Grammar instance.
     *
     * @return Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Returns the current Connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns the builders DN to perform searches upon.
     *
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Sets the DN to perform searches upon.
     *
     * @param string|null $dn
     *
     * @return Builder
     */
    public function setDn($dn = null)
    {
        $this->dn = (string) $dn;

        return $this;
    }

    /**
     * Alias for setting the base DN of the query.
     *
     * @param string $dn
     *
     * @return Builder
     */
    public function in($dn)
    {
        return $this->setDn($dn);
    }

    /**
     * Sets the size limit of the current query.
     *
     * @param int $limit
     *
     * @return Builder
     */
    public function limit($limit = 0)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Performs the specified query on the current LDAP connection.
     *
     * @param string $query
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function query($query)
    {
        $results = $this->connection->{$this->type}(
            $this->getDn(),
            $query,
            $this->getSelects(),
            $onlyAttributes = false,
            $this->limit
        );

        return $this->newProcessor()->process($results);
    }

    /**
     * Paginates the current LDAP query.
     *
     * @param int  $perPage
     * @param int  $currentPage
     * @param bool $isCritical
     *
     * @return Paginator
     */
    public function paginate($perPage = 50, $currentPage = 0, $isCritical = true)
    {
        $this->paginated = true;

        $pages = [];

        $cookie = '';

        do {
            $this->connection->controlPagedResult($perPage, $isCritical, $cookie);

            // Run the search.
            $resource = @$this->connection->search($this->getDn(), $this->getQuery(), $this->getSelects());

            if ($resource) {
                $this->connection->controlPagedResultResponse($resource, $cookie);

                // We'll collect each resource result into the pages array.
                $pages[] = $resource;
            }
        } while (!empty($cookie));

        $paginator = $this->newProcessor()->processPaginated($pages, $perPage, $currentPage);

        // Reset paged result on the current connection. We won't pass in the current $perPage
        // parameter since we want to reset the page size to the default '1000'. Sending '0'
        // eliminates any further opportunity for running queries in the same request,
        // even though that is supposed to be the correct usage.
        $this->connection->controlPagedResult();

        return $paginator;
    }

    /**
     * Returns the first entry in a search result.
     *
     * @param array|string $columns
     *
     * @return Model|array|null
     */
    public function first($columns = [])
    {
        $results = $this->select($columns)->limit(1)->get();

        return Arr::get($results, 0);
    }

    /**
     * Returns the first entry in a search result.
     *
     * If no entry is found, an exception is thrown.
     *
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    public function firstOrFail($columns = [])
    {
        $record = $this->first($columns);

        if (!$record) {
            throw (new ModelNotFoundException())
                ->setQuery($this->getUnescapedQuery(), $this->getDn());
        }

        return $record;
    }

    /**
     * Finds a record by the specified attribute and value.
     *
     * @param string       $attribute
     * @param string       $value
     * @param array|string $columns
     *
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = [])
    {
        return $this->whereEquals($attribute, $value)->first($columns);
    }

    /**
     * Finds a record by the specified attribute and value.
     *
     * If no record is found an exception is thrown.
     *
     * @param string       $attribute
     * @param string       $value
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return mixed
     */
    public function findByOrFail($attribute, $value, $columns = [])
    {
        return $this->whereEquals($attribute, $value)->firstOrFail($columns);
    }

    /**
     * Finds a record using ambiguous name resolution.
     *
     * @param string|array $anr
     * @param array|string $columns
     *
     * @return mixed
     */
    public function find($anr, $columns = [])
    {
        if (is_array($anr)) {
            return $this->findMany($anr, $columns);
        }

        return $this->findBy($this->schema->anr(), $anr, $columns);
    }

    /**
     * Finds multiple records using ambiguous name resolution.
     *
     * @param array $anrs
     * @param array $columns
     *
     * @return mixed
     */
    public function findMany(array $anrs = [], $columns = [])
    {
        return $this->findManyBy($this->schema->anr(), $anrs, $columns);
    }

    /**
     * Finds many records by the specified attribute.
     *
     * @param string $attribute
     * @param array  $values
     * @param array  $columns
     *
     * @return mixed
     */
    public function findManyBy($attribute, array $values = [], $columns = [])
    {
        $query = $this->select($columns);

        foreach ($values as $value) {
            $query->orWhere([$attribute => $value]);
        }

        return $query->get();
    }

    /**
     * Finds a record using ambiguous name resolution.
     * 
     * If a record is not found, an exception is thrown.
     *
     * @param string       $anr
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return mixed
     */
    public function findOrFail($anr, $columns = [])
    {
        $entry = $this->find($anr, $columns);

        // Make sure we check if the result is an entry or an array before
        // we throw an exception in case the user wants raw results.
        if (!$entry instanceof Model && !is_array($entry)) {
            throw (new ModelNotFoundException())
                ->setQuery($this->getUnescapedQuery(), $this->getDn());
        }

        return $entry;
    }

    /**
     * Finds a record by its distinguished name.
     *
     * @param string|array $dn
     * @param array|string $columns
     *
     * @return bool|Model
     */
    public function findByDn($dn, $columns = [])
    {
        return $this
            ->setDn($dn)
            ->read(true)
            ->whereHas($this->schema->objectClass())
            ->first($columns);
    }

    /**
     * Finds a record by its distinguished name.
     *
     * Fails upon no records returned.
     *
     * @param string       $dn
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return Model
     */
    public function findByDnOrFail($dn, $columns = [])
    {
        return $this
            ->setDn($dn)
            ->read(true)
            ->whereHas($this->schema->objectClass())
            ->firstOrFail($columns);
    }

    /**
     * Finds a record by its string GUID.
     *
     * @param string       $guid
     * @param array|string $columns
     *
     * @return Model
     */
    public function findByGuid($guid, $columns = [])
    {
        if ($this->schema->objectGuidRequiresConversion()) {
            $guid = Utilities::stringGuidToHex($guid);
        }

        return $this->select($columns)->whereRaw([
            $this->schema->objectGuid() => $guid
        ])->first();
    }

    /**
     * Finds a record by its string GUID.
     *
     * Fails upon no records returned.
     *
     * @param string       $guid
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return mixed
     */
    public function findByGuidOrFail($guid, $columns = [])
    {
        if ($this->schema->objectGuidRequiresConversion()) {
            $guid = Utilities::stringGuidToHex($guid);
        }

        return $this->select($columns)->whereRaw([
            $this->schema->objectGuid() => $guid
        ])->firstOrFail();
    }

    /**
     * Finds a record by its Object SID.
     *
     * @param string       $sid
     * @param array|string $columns
     *
     * @return mixed
     */
    public function findBySid($sid, $columns = [])
    {
        return $this->findBy($this->schema->objectSid(), $sid, $columns);
    }

    /**
     * Finds a record by its Object SID.
     *
     * Fails upon no records returned.
     *
     * @param string       $sid
     * @param array|string $columns
     *
     * @throws ModelNotFoundException
     *
     * @return mixed
     */
    public function findBySidOrFail($sid, $columns = [])
    {
        return $this->findByOrFail($this->schema->objectSid(), $sid, $columns);
    }

    /**
     * Finds the Base DN of your domain controller.
     *
     * @return string|bool
     */
    public function findBaseDn()
    {
        $result = $this->setDn(null)
            ->read()
            ->raw()
            ->whereHas($this->schema->objectClass())
            ->first();

        $key = $this->schema->defaultNamingContext();

        if (is_array($result) && array_key_exists($key, $result)) {
            if (array_key_exists(0, $result[$key])) {
                return $result[$key][0];
            }
        }

        return false;
    }

    /**
     * Adds the inserted fields to query on the current LDAP connection.
     *
     * @param array|string $columns
     *
     * @return Builder
     */
    public function select($columns = [])
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        if (!empty($columns)) {
            $this->columns = $columns;
        }

        return $this;
    }

    /**
     * Adds a raw filter to the current query.
     *
     * @param array|string $filters
     *
     * @return Builder
     */
    public function rawFilter($filters = [])
    {
        $filters = is_array($filters) ? $filters : func_get_args();

        foreach ($filters as $filter) {
            $this->filters['raw'][] = $filter;
        }

        return $this;
    }

    /**
     * Adds a nested 'and' filter to the current query.
     *
     * @param Closure $closure
     *
     * @return Builder
     */
    public function andFilter(Closure $closure)
    {
        $query = $this->newNestedInstance($closure);

        $filter = $this->grammar->compileAnd($query->getQuery());

        return $this->rawFilter($filter);
    }

    /**
     * Adds a nested 'or' filter to the current query.
     *
     * @param Closure $closure
     *
     * @return Builder
     */
    public function orFilter(Closure $closure)
    {
        $query = $this->newNestedInstance($closure);

        $filter = $this->grammar->compileOr($query->getQuery());

        return $this->rawFilter($filter);
    }

    /**
     * Adds a nested 'not' filter to the current query.
     *
     * @param Closure $closure
     *
     * @return Builder
     */
    public function notFilter(Closure $closure)
    {
        $query = $this->newNestedInstance($closure);

        $filter = $this->grammar->compileNot($query->getQuery());

        return $this->rawFilter($filter);
    }

    /**
     * Adds a where clause to the current query.
     *
     * @param string|array $field
     * @param string       $operator
     * @param string       $value
     * @param string       $boolean
     * @param bool         $raw
     *
     * @throws InvalidArgumentException
     *
     * @return Builder
     */
    public function where($field, $operator = null, $value = null, $boolean = 'and', $raw = false)
    {
        if (is_array($field)) {
            // If the column is an array, we will assume it is an array of
            // key-value pairs and can add them each as a where clause.
            return $this->addArrayOfWheres($field, $boolean, $raw);
        }

        // We'll bypass the 'has' and 'notHas' operator since they
        // only require two arguments inside the where method.
        $bypass = [Operator::$has, Operator::$notHas];

        // Here we will make some assumptions about the operator. If only
        // 2 values are passed to the method, we will assume that
        // the operator is an equals sign and keep going.
        if (func_num_args() === 2 && in_array($operator, $bypass) === false) {
            list($value, $operator) = [$operator, '='];
        }

        if (!in_array($operator, Operator::all())) {
            throw new InvalidArgumentException("Invalid where operator: {$operator}");
        }

        // We'll escape the value if raw isn't requested.
        $value = $raw ? $value : $this->escape($value);

        $field = $this->escape($field, $ignore = null, 3);

        $this->filters[$boolean][] = compact('field', 'operator', 'value');

        return $this;
    }

    /**
     * Adds a raw where clause to the current query.
     *
     * Values given to this method are not escaped.
     *
     * @param string|array $field
     * @param string       $operator
     * @param string       $value
     *
     * @return Builder
     */
    public function whereRaw($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'and', true);
    }

    /**
     * Adds a 'where equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereEquals($field, $value)
    {
        return $this->where($field, Operator::$equals, $value);
    }

    /**
     * Adds a 'where not equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereNotEquals($field, $value)
    {
        return $this->where($field, Operator::$doesNotEqual, $value);
    }

    /**
     * Adds a 'where approximately equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereApproximatelyEquals($field, $value)
    {
        return $this->where($field, Operator::$approximatelyEquals, $value);
    }

    /**
     * Adds a 'where has' clause to the current query.
     *
     * @param string $field
     *
     * @return Builder
     */
    public function whereHas($field)
    {
        return $this->where($field, Operator::$has);
    }

    /**
     * Adds a 'where not has' clause to the current query.
     *
     * @param string $field
     *
     * @return Builder
     */
    public function whereNotHas($field)
    {
        return $this->where($field, Operator::$notHas);
    }

    /**
     * Adds a 'where contains' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereContains($field, $value)
    {
        return $this->where($field, Operator::$contains, $value);
    }

    /**
     * Adds a 'where contains' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereNotContains($field, $value)
    {
        return $this->where($field, Operator::$notContains, $value);
    }

    /**
     * Adds a 'between' clause to the current query.
     *
     * @param string $field
     * @param array  $values
     *
     * @return Builder
     */
    public function whereBetween($field, array $values)
    {
        return $this->where([
            [$field, '>=', $values[0]],
            [$field, '<=', $values[1]],
        ]);
    }

    /**
     * Adds a 'where starts with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereStartsWith($field, $value)
    {
        return $this->where($field, Operator::$startsWith, $value);
    }

    /**
     * Adds a 'where *not* starts with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereNotStartsWith($field, $value)
    {
        return $this->where($field, Operator::$notStartsWith, $value);
    }

    /**
     * Adds a 'where ends with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereEndsWith($field, $value)
    {
        return $this->where($field, Operator::$endsWith, $value);
    }

    /**
     * Adds a 'where *not* ends with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function whereNotEndsWith($field, $value)
    {
        return $this->where($field, Operator::$notEndsWith, $value);
    }

    /**
     * Adds a enabled filter to the current query.
     *
     * @return Builder
     */
    public function whereEnabled()
    {
        return $this->rawFilter($this->schema->filterEnabled());
    }

    /**
     * Adds a disabled filter to the current query.
     *
     * @return Builder
     */
    public function whereDisabled()
    {
        return $this->rawFilter($this->schema->filterDisabled());
    }

    /**
     * Adds a 'member of' filter to the current query.
     *
     * @param string $dn
     *
     * @return Builder
     */
    public function whereMemberOf($dn)
    {
        return $this->whereEquals($this->schema->memberOfRecursive(), $dn);
    }

    /**
     * Adds an 'or where' clause to the current query.
     *
     * @param string      $field
     * @param string|null $operator
     * @param string|null $value
     *
     * @return Builder
     */
    public function orWhere($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'or');
    }

    /**
     * Adds a raw or where clause to the current query.
     *
     * Values given to this method are not escaped.
     *
     * @param string $field
     * @param string $operator
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereRaw($field, $operator = null, $value = null)
    {
        return $this->where($field, $operator, $value, 'or', true);
    }

    /**
     * Adds an 'or where has' clause to the current query.
     *
     * @param string $field
     *
     * @return Builder
     */
    public function orWhereHas($field)
    {
        return $this->orWhere($field, Operator::$has);
    }

    /**
     * Adds a 'where not has' clause to the current query.
     *
     * @param string $field
     *
     * @return Builder
     */
    public function orWhereNotHas($field)
    {
        return $this->orWhere($field, Operator::$notHas);
    }

    /**
     * Adds an 'or where equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereEquals($field, $value)
    {
        return $this->orWhere($field, Operator::$equals, $value);
    }

    /**
     * Adds an 'or where not equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereNotEquals($field, $value)
    {
        return $this->orWhere($field, Operator::$doesNotEqual, $value);
    }

    /**
     * Adds a 'or where approximately equals' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereApproximatelyEquals($field, $value)
    {
        return $this->orWhere($field, Operator::$approximatelyEquals, $value);
    }

    /**
     * Adds an 'or where contains' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereContains($field, $value)
    {
        return $this->orWhere($field, Operator::$contains, $value);
    }

    /**
     * Adds an 'or where *not* contains' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereNotContains($field, $value)
    {
        return $this->orWhere($field, Operator::$notContains, $value);
    }

    /**
     * Adds an 'or where starts with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereStartsWith($field, $value)
    {
        return $this->orWhere($field, Operator::$startsWith, $value);
    }

    /**
     * Adds an 'or where *not* starts with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereNotStartsWith($field, $value)
    {
        return $this->orWhere($field, Operator::$notStartsWith, $value);
    }

    /**
     * Adds an 'or where ends with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereEndsWith($field, $value)
    {
        return $this->orWhere($field, Operator::$endsWith, $value);
    }

    /**
     * Adds an 'or where *not* ends with' clause to the current query.
     *
     * @param string $field
     * @param string $value
     *
     * @return Builder
     */
    public function orWhereNotEndsWith($field, $value)
    {
        return $this->orWhere($field, Operator::$notEndsWith, $value);
    }

    /**
     * Adds an 'or where member of' filter to the current query.
     *
     * @param string $dn
     *
     * @return Builder
     */
    public function orWhereMemberOf($dn)
    {
        return $this->orWhereEquals($this->schema->memberOfRecursive(), $dn);
    }

    /**
     * Returns true / false depending if the current object
     * contains selects.
     *
     * @return bool
     */
    public function hasSelects()
    {
        return count($this->getSelects()) > 0;
    }

    /**
     * Returns the current selected fields to retrieve.
     *
     * @return array
     */
    public function getSelects()
    {
        $selects = $this->columns;

        // If the asterisk is not provided in the selected columns, we need to
        // ensure we always select the object class and category, as these
        // are used for constructing models. The asterisk indicates that
        // we want all attributes returned for LDAP records.
        if (!in_array('*', $selects)) {
            $selects[] = $this->schema->objectCategory();
            $selects[] = $this->schema->objectClass();
        }

        return $selects;
    }

    /**
     * Sorts the LDAP search results by the specified field and direction.
     *
     * @param string   $field
     * @param string   $direction
     * @param int|null $flags
     *
     * @return Builder
     */
    public function sortBy($field, $direction = 'asc', $flags = null)
    {
        $this->sortByField = $field;

        // Normalize direction.
        $direction = strtolower($direction);

        if ($direction === 'asc' || $direction === 'desc') {
            $this->sortByDirection = $direction;
        }

        if (is_null($flags)) {
            $this->sortByFlags = SORT_NATURAL + SORT_FLAG_CASE;
        }

        return $this;
    }

    /**
     * Set the query to search on the base distinguished name.
     *
     * This will result in one record being returned.
     *
     * @return Builder
     */
    public function read()
    {
        $this->type = 'read';

        return $this;
    }

    /**
     * Set the query to search one level on the base distinguished name.
     *
     * @return Builder
     */
    public function listing()
    {
        $this->type = 'listing';

        return $this;
    }

    /**
     * Sets the query to search the entire directory on the base distinguished name.
     *
     * @return Builder
     */
    public function recursive()
    {
        $this->type = 'search';

        return $this;
    }

    /**
     * Sets the recursive property to tell the search whether or
     * not to return the LDAP results in their raw format.
     *
     * @param bool $raw
     *
     * @return Builder
     */
    public function raw($raw = true)
    {
        $this->raw = (bool) $raw;

        return $this;
    }

    /**
     * Sets the nested property to tell the Grammar instance whether
     * or not the current query is already nested.
     *
     * @param bool $nested
     *
     * @return Builder
     */
    public function nested($nested = true)
    {
        $this->nested = (bool) $nested;

        return $this;
    }

    /**
     * Returns true / false if the current query is nested.
     *
     * @return bool
     */
    public function isNested()
    {
        return $this->nested === true;
    }

    /**
     * Returns an escaped string for use in an LDAP filter.
     *
     * @param string $value
     * @param string $ignore
     * @param int    $flags
     *
     * @return string
     */
    public function escape($value, $ignore = '', $flags = 0)
    {
        return Utilities::escape($value, $ignore, $flags);
    }

    /**
     * Returns the query builders sort by field.
     *
     * @return string
     */
    public function getSortByField()
    {
        return $this->sortByField;
    }

    /**
     * Returns the query builders sort by direction.
     *
     * @return string
     */
    public function getSortByDirection()
    {
        return $this->sortByDirection;
    }

    /**
     * Returns the query builders sort by flags.
     *
     * @return int
     */
    public function getSortByFlags()
    {
        return $this->sortByFlags;
    }

    /**
     * Returns bool that determines whether the current
     * query builder will return raw results.
     *
     * @return bool
     */
    public function isRaw()
    {
        return $this->raw;
    }

    /**
     * Returns bool that determines whether the current
     * query builder will return paginated results.
     *
     * @return bool
     */
    public function isPaginated()
    {
        return $this->paginated;
    }

    /**
     * Returns bool that determines whether the current
     * query builder will return sorted results.
     *
     * @return bool
     */
    public function isSorted()
    {
        return $this->sortByField ? true : false;
    }

    /**
     * Handle dynamic method calls on the query builder
     * object to be directed to the query processor.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'where')) {
            return $this->dynamicWhere($method, $parameters);
        }

        return call_user_func_array([$this->newProcessor(), $method], $parameters);
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param string $method
     * @param string $parameters
     *
     * @return Builder
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);

        $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'and';

        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment != 'And' && $segment != 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);

                $index++;
            }

            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Adds an array of wheres to the current query.
     *
     * @param array  $wheres
     * @param string $boolean
     * @param bool   $raw
     *
     * @return Builder
     */
    protected function addArrayOfWheres($wheres, $boolean, $raw)
    {
        foreach ($wheres as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                // If the key is numeric and the value is an array, we'll
                // assume we've been given an array with conditionals.
                $this->where($value[0], $value[1], $value[2]);
            } else {
                // Otherwise, we'll assume the array is an equals clause.
                $this->where($key, Operator::$equals, $value, $boolean, $raw);
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     *
     * @param string $segment
     * @param string $connector
     * @param array  $parameters
     * @param int    $index
     *
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower($connector);

        $this->where(Str::snake($segment), '=', $parameters[$index], $bool);
    }

    /**
     * Returns a new query Processor instance.
     *
     * @return Processor
     */
    protected function newProcessor()
    {
        return new Processor($this);
    }
}
