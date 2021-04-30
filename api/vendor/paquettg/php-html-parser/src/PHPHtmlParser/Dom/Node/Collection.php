<?php

declare(strict_types=1);

namespace PHPHtmlParser\Dom\Node;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPHtmlParser\Exceptions\EmptyCollectionException;

/**
 * Class Collection.
 */
class Collection implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * The collection of Nodes.
     *
     * @var array
     */
    protected $collection = [];

    /**
     * Attempts to call the method on the first node in
     * the collection.
     *
     * @throws EmptyCollectionException
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        $node = \reset($this->collection);
        if ($node instanceof AbstractNode) {
            return \call_user_func_array([$node, $method], $arguments);
        }
        throw new EmptyCollectionException('The collection does not contain any Nodes.');
    }

    /**
     * Attempts to apply the magic get to the first node
     * in the collection.
     *
     * @param mixed $key
     *
     * @throws EmptyCollectionException
     *
     * @return mixed
     */
    public function __get($key)
    {
        $node = \reset($this->collection);
        if ($node instanceof AbstractNode) {
            return $node->$key;
        }
        throw new EmptyCollectionException('The collection does not contain any Nodes.');
    }

    /**
     * Applies the magic string method to the first node in
     * the collection.
     */
    public function __toString(): string
    {
        $node = \reset($this->collection);
        if ($node instanceof AbstractNode) {
            return (string) $node;
        }

        return '';
    }

    /**
     * Returns the count of the collection.
     */
    public function count(): int
    {
        return \count($this->collection);
    }

    /**
     * Returns an iterator for the collection.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection);
    }

    /**
     * Set an attribute by the given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (\is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    /**
     * Checks if an offset exists.
     *
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /**
     * Unset a collection Node.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->collection[$offset]);
    }

    /**
     * Gets a node at the given offset, or null.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->collection[$offset] ?? null;
    }

    /**
     * Returns this collection as an array.
     */
    public function toArray(): array
    {
        return $this->collection;
    }

    /**
     * Similar to jQuery "each" method. Calls the callback with each
     * Node in this collection.
     */
    public function each(callable $callback)
    {
        foreach ($this->collection as $key => $value) {
            $callback($value, $key);
        }
    }
}
