<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection;

use ArrayAccess;
use Countable;
use Iterator;
use Virgulti\TypedCollection\Exceptions\InvalidItemClassException;

abstract class BaseCollection implements ArrayAccess, Countable, Iterator
{
    /** @var array<int, mixed> */
    protected array $items = [];

    private int $position = 0;

    // Maps accepted type aliases to the value returned by gettype()
    private const SCALAR_TYPES = [
        'int'     => 'integer',
        'integer' => 'integer',
        'bool'    => 'boolean',
        'boolean' => 'boolean',
        'float'   => 'double',
        'double'  => 'double',
        'string'  => 'string',
    ];

    /**
     * Returns the fully-qualified class name or scalar type string
     * ('int', 'string', 'float', 'bool') that every element must match.
     */
    abstract protected function getType(): string;

    // -------------------------------------------------------------------------
    // Type validation
    // -------------------------------------------------------------------------

    /**
     * @param mixed $item
     * @throws InvalidItemClassException
     */
    protected function validate($item): void
    {
        $type = $this->getType();

        if (isset(self::SCALAR_TYPES[$type])) {
            if (gettype($item) !== self::SCALAR_TYPES[$type]) {
                throw InvalidItemClassException::forItem($item, $type);
            }
        } else {
            if (!($item instanceof $type)) {
                throw InvalidItemClassException::forItem($item, $type);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Collection operations
    // -------------------------------------------------------------------------

    /**
     * Appends a validated item to the end of the collection.
     *
     * @param mixed $item
     */
    public function add($item): void
    {
        $this->validate($item);
        $this->items[] = $item;
    }

    /**
     * Removes the item at position $index and re-indexes the collection.
     */
    public function remove(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    /**
     * Searches for the first item matching $property === $value.
     * For object collections, compares the named property.
     * For scalar collections, $property is ignored and the value is compared directly.
     *
     * @param  mixed      $value
     * @return int|false  Index of the first match, or false if not found.
     */
    public function findBy(string $property, $value)
    {
        foreach ($this->items as $index => $item) {
            if (is_object($item)) {
                if ($item->$property === $value) {
                    return $index;
                }
            } elseif ($item === $value) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Alias for add() — appends a validated item to the end.
     *
     * @param mixed $item
     */
    public function push($item): void
    {
        $this->add($item);
    }

    /**
     * Removes and returns the last item, or null if the collection is empty.
     *
     * @return mixed|null
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Compares this collection with another.
     * Non-strict (default): same key-value pairs, order-insensitive (like == on arrays).
     * Strict: same key-value pairs in the same order with identical types (like ===).
     */
    public function equals(self $other, bool $strict = false): bool
    {
        return $strict
            ? $this->items === $other->items
            : $this->items == $other->items;
    }

    /**
     * Returns a new collection containing all items from this collection
     * followed by all items from $other. Equivalent to array_merge().
     */
    public function merge(self $other): self
    {
        $new = clone $this;
        foreach ($other->items as $item) {
            $new->add($item);
        }
        return $new;
    }

    /**
     * Returns the underlying plain PHP array.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Creates a new collection from a plain PHP array, validating every item.
     *
     * @param  array<int, mixed> $items
     * @return static
     */
    public static function fromArray(array $items): self
    {
        $collection = new static();
        foreach ($items as $item) {
            $collection->add($item);
        }
        return $collection;
    }

    // -------------------------------------------------------------------------
    // Functional operations
    // -------------------------------------------------------------------------

    /**
     * Returns a new collection containing only items for which $predicate returns true.
     */
    public function filter(callable $predicate): self
    {
        $new         = clone $this;
        $new->items  = array_values(array_filter($this->items, $predicate));
        $new->position = 0;
        return $new;
    }

    /**
     * Applies $transform to every item and returns a plain PHP array.
     * Returns array (not a collection) because the transform may change the item type.
     *
     * @return array<int, mixed>
     */
    public function map(callable $transform): array
    {
        return array_map($transform, $this->items);
    }

    /**
     * Reduces the collection to a single value using $callback($carry, $item).
     * Equivalent to array_reduce().
     *
     * @param  mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Returns the first item, or null if the collection is empty.
     *
     * @return mixed|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * Returns the last item, or null if the collection is empty.
     *
     * @return mixed|null
     */
    public function last()
    {
        if (empty($this->items)) {
            return null;
        }
        return $this->items[count($this->items) - 1];
    }

    /**
     * Returns true if $item is present in the collection (strict comparison).
     *
     * @param mixed $item
     */
    public function contains($item): bool
    {
        return in_array($item, $this->items, true);
    }

    /**
     * Returns true if the collection has no items.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Returns a new collection sorted by $comparator (same signature as usort).
     * Does not mutate the original.
     */
    public function sort(callable $comparator): self
    {
        $new           = clone $this;
        usort($new->items, $comparator);
        $new->position = 0;
        return $new;
    }

    // -------------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------------

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->validate($value);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
        $this->items = array_values($this->items);
    }

    // -------------------------------------------------------------------------
    // Countable
    // -------------------------------------------------------------------------

    public function count(): int
    {
        return count($this->items);
    }

    // -------------------------------------------------------------------------
    // Iterator
    // -------------------------------------------------------------------------

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->items[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }
}
