# TypedCollection

Type-safe collections for PHP — drop-in replacements for generic arrays with runtime type enforcement.

Requires PHP >= 7.4. No runtime dependencies.

---

## Installation

```bash
composer require virgulti/typed-collection
```

---

## Concept

Plain PHP arrays accept any value. With `TypedCollection`, every element is validated on insertion: if the type does not match, an exception is thrown immediately instead of propagating corrupt data deep into your code.

```php
// Plain PHP array — no guarantees
$cars = [];
$cars[] = new Car('Toyota', 'Corolla', 2020);
$cars[] = 'oops'; // no error, silent corruption

// TypedCollection — fails immediately
$cars = new CarCollection();
$cars->add(new Car('Toyota', 'Corolla', 2020));
$cars->add('oops'); // throws InvalidItemClassException
```

---

## Creating a custom collection

Extend `BaseCollection` and implement a single method:

```php
use Virgulti\TypedCollection\BaseCollection;

class CarCollection extends BaseCollection
{
    protected function getType(): string
    {
        return Car::class; // FQCN for objects, or 'int' / 'string' / 'float' / 'bool' for scalars
    }
}
```

### Built-in scalar collections

| Class              | Accepted type |
|--------------------|---------------|
| `IntCollection`    | `int`         |
| `StringCollection` | `string`      |
| `FloatCollection`  | `float`       |

---

## API

All collections share the same interface inherited from `BaseCollection`.

### Adding and removing

```php
$col->add($item);          // appends and validates; throws InvalidItemClassException on type mismatch
$col->push($item);         // alias for add()
$col->pop();               // removes and returns the last item (null if empty)
$col->remove(int $index);  // removes by index and re-indexes
```

### Access and search

```php
$col[0];                         // ArrayAccess — read by index
$col[] = $item;                  // ArrayAccess — append with validation
isset($col[0]);                  // ArrayAccess — check existence
unset($col[0]);                  // ArrayAccess — remove and re-index
$col->first();                   // first item (null if empty)
$col->last();                    // last item (null if empty)
$col->contains($item);           // true/false, strict comparison (===)
$col->findBy('property', $val);  // index of first match, false if not found
```

### Utilities

```php
count($col);          // Countable
$col->isEmpty();      // true if the collection has no items
$col->toArray();      // returns the underlying plain PHP array
foreach ($col as $k => $v) { ... }  // Iterator
```

### Functional operations

```php
// filter — returns a new collection, does not mutate the original
$toyotas = $col->filter(fn(Car $c) => $c->make === 'Toyota');

// map — returns a plain PHP array (item type may change)
$makes = $col->map(fn(Car $c) => $c->make); // array of strings

// reduce — aggregates to a single value
$newest = $col->reduce(fn(int $carry, Car $c) => max($carry, $c->year), 0);

// sort — returns a new sorted collection, does not mutate the original
$sorted = $col->sort(fn(Car $a, Car $b) => $a->year <=> $b->year);
```

### Construction and combination

```php
// Build from an existing array (validates every item)
$col = CarCollection::fromArray([$car1, $car2]);

// Merge — returns a new collection, does not mutate either original
$merged = $colA->merge($colB);

// Equality
$colA->equals($colB);         // non-strict: same key-value pairs, order-insensitive
$colA->equals($colB, true);   // strict: same items in the same order (===)
```

---

## Full example

```php
use Virgulti\TypedCollection\Examples\Car;
use Virgulti\TypedCollection\Examples\CarCollection;

$cars = CarCollection::fromArray([
    new Car('Toyota', 'Corolla', 2020),
    new Car('Honda',  'Civic',   2018),
    new Car('Toyota', 'Yaris',   2021),
]);

// Filter by make
$toyotas = $cars->filter(fn(Car $c) => $c->make === 'Toyota');
echo count($toyotas); // 2

// Extract models as a plain PHP array
$models = $toyotas->map(fn(Car $c) => $c->model); // ['Corolla', 'Yaris']

// Sort by year
$sorted = $cars->sort(fn(Car $a, Car $b) => $a->year <=> $b->year);
echo $sorted->first()->model; // Civic (2018)

// Find the most recent year
$latest = $cars->reduce(fn(int $carry, Car $c) => max($carry, $c->year), 0);
echo $latest; // 2021

// Search by property
$idx = $cars->findBy('make', 'Honda'); // 1
```

### Scalar collections

```php
use Virgulti\TypedCollection\IntCollection;
use Virgulti\TypedCollection\StringCollection;

$ints   = IntCollection::fromArray([3, 1, 4, 1, 5]);
$sorted = $ints->sort(fn(int $a, int $b) => $a <=> $b); // [1, 1, 3, 4, 5]
$sum    = $ints->reduce(fn(int $carry, int $n) => $carry + $n, 0); // 14

$words  = StringCollection::fromArray(['banana', 'apple', 'cherry']);
$sorted = $words->sort('strcmp');
$upper  = $words->map('strtoupper'); // ['BANANA', 'APPLE', 'CHERRY']
```

---

## Project structure

```
src/
  BaseCollection.php          # shared logic (ArrayAccess, Countable, Iterator + full API)
  IntCollection.php
  StringCollection.php
  FloatCollection.php
  Exceptions/
    InvalidItemClassException.php
examples/
  Car.php                     # example entity
  CarCollection.php           # typed collection for Car objects
tests/
  CarCollectionTest.php       # full test suite for object collections
  ScalarCollectionsTest.php   # test suite for Int/String/Float collections
```

---

## Running the tests

```bash
composer install
./vendor/bin/phpunit
```

---

## License

MIT
