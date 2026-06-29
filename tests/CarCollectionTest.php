<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection\Tests;

use PHPUnit\Framework\TestCase;
use Virgulti\TypedCollection\Examples\Car;
use Virgulti\TypedCollection\Examples\CarCollection;
use Virgulti\TypedCollection\Exceptions\InvalidItemClassException;

class CarCollectionTest extends TestCase
{
    private function makeCar(string $make = 'Toyota', string $model = 'Corolla', int $year = 2020): Car
    {
        return new Car($make, $model, $year);
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    public function testAddValidItem(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar());

        $this->assertCount(1, $col);
    }

    public function testAddInvalidItemThrows(): void
    {
        $this->expectException(InvalidItemClassException::class);
        $col = new CarCollection();
        $col->add('not a car');
    }

    public function testAddInvalidObjectThrows(): void
    {
        $this->expectException(InvalidItemClassException::class);
        $col = new CarCollection();
        $col->add(new \stdClass());
    }

    // -------------------------------------------------------------------------
    // remove
    // -------------------------------------------------------------------------

    public function testRemoveByIndex(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('Toyota'));
        $col->add($this->makeCar('Honda'));
        $col->add($this->makeCar('Ford'));

        $col->remove(1); // remove Honda

        $this->assertCount(2, $col);
        $this->assertSame('Toyota', $col[0]->make);
        $this->assertSame('Ford',   $col[1]->make);
    }

    public function testRemoveReindexes(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('A'));
        $col->add($this->makeCar('B'));
        $col->remove(0);

        // Index 0 should now be the former index 1
        $this->assertSame('B', $col[0]->make);
    }

    // -------------------------------------------------------------------------
    // findBy
    // -------------------------------------------------------------------------

    public function testFindByReturnsIndex(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('Toyota'));
        $col->add($this->makeCar('Honda'));

        $this->assertSame(1, $col->findBy('make', 'Honda'));
    }

    public function testFindByReturnsFalseWhenNotFound(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('Toyota'));

        $this->assertFalse($col->findBy('make', 'Ferrari'));
    }

    public function testFindByReturnsFirstMatch(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('Toyota'));
        $col->add($this->makeCar('Toyota'));
        $col->add($this->makeCar('Honda'));

        $this->assertSame(0, $col->findBy('make', 'Toyota'));
    }

    // -------------------------------------------------------------------------
    // push / pop
    // -------------------------------------------------------------------------

    public function testPushAppendsItem(): void
    {
        $col = new CarCollection();
        $col->push($this->makeCar('Toyota'));
        $col->push($this->makeCar('Honda'));

        $this->assertCount(2, $col);
        $this->assertSame('Honda', $col[1]->make);
    }

    public function testPopRemovesAndReturnsLastItem(): void
    {
        $col = new CarCollection();
        $col->add($this->makeCar('Toyota'));
        $col->add($this->makeCar('Honda'));

        $popped = $col->pop();

        $this->assertCount(1, $col);
        $this->assertSame('Honda', $popped->make);
    }

    public function testPopOnEmptyCollectionReturnsNull(): void
    {
        $col = new CarCollection();
        $this->assertNull($col->pop());
    }

    // -------------------------------------------------------------------------
    // equals
    // -------------------------------------------------------------------------

    public function testEqualsSameContent(): void
    {
        $car = $this->makeCar();
        $a   = CarCollection::fromArray([$car]);
        $b   = CarCollection::fromArray([$car]);

        $this->assertTrue($a->equals($b));
    }

    public function testEqualsDifferentContent(): void
    {
        $a = CarCollection::fromArray([$this->makeCar('Toyota')]);
        $b = CarCollection::fromArray([$this->makeCar('Honda')]);

        $this->assertFalse($a->equals($b));
    }

    public function testEqualsStrictSameOrder(): void
    {
        $car = $this->makeCar();
        $a   = CarCollection::fromArray([$car]);
        $b   = CarCollection::fromArray([$car]);

        $this->assertTrue($a->equals($b, true));
    }

    public function testEqualsStrictDifferentOrderFails(): void
    {
        $car1 = $this->makeCar('Toyota');
        $car2 = $this->makeCar('Honda');

        $a = CarCollection::fromArray([$car1, $car2]);
        $b = CarCollection::fromArray([$car2, $car1]);

        $this->assertFalse($a->equals($b, true));
    }

    // -------------------------------------------------------------------------
    // merge
    // -------------------------------------------------------------------------

    public function testMergeReturnsCombinedCollection(): void
    {
        $a = CarCollection::fromArray([$this->makeCar('Toyota')]);
        $b = CarCollection::fromArray([$this->makeCar('Honda')]);

        $merged = $a->merge($b);

        $this->assertCount(2, $merged);
        $this->assertSame('Toyota', $merged[0]->make);
        $this->assertSame('Honda',  $merged[1]->make);
    }

    public function testMergeDoesNotMutateOriginals(): void
    {
        $a = CarCollection::fromArray([$this->makeCar('Toyota')]);
        $b = CarCollection::fromArray([$this->makeCar('Honda')]);

        $a->merge($b);

        $this->assertCount(1, $a);
        $this->assertCount(1, $b);
    }

    // -------------------------------------------------------------------------
    // toArray / fromArray
    // -------------------------------------------------------------------------

    public function testToArrayReturnsPlainArray(): void
    {
        $car = $this->makeCar();
        $col = CarCollection::fromArray([$car]);

        $arr = $col->toArray();

        $this->assertIsArray($arr);
        $this->assertSame($car, $arr[0]);
    }

    public function testFromArrayValidatesItems(): void
    {
        $this->expectException(InvalidItemClassException::class);
        CarCollection::fromArray(['not a car']);
    }

    public function testFromArrayReturnsCorrectType(): void
    {
        $col = CarCollection::fromArray([$this->makeCar()]);
        $this->assertInstanceOf(CarCollection::class, $col);
    }

    // -------------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------------

    public function testOffsetGetReturnsItem(): void
    {
        $car = $this->makeCar();
        $col = CarCollection::fromArray([$car]);

        $this->assertSame($car, $col[0]);
    }

    public function testOffsetGetNonExistentReturnsNull(): void
    {
        $col = new CarCollection();
        $this->assertNull($col[99]);
    }

    public function testOffsetSetAppend(): void
    {
        $col    = new CarCollection();
        $col[]  = $this->makeCar();

        $this->assertCount(1, $col);
    }

    public function testOffsetSetAtIndex(): void
    {
        $car1 = $this->makeCar('Toyota');
        $car2 = $this->makeCar('Honda');
        $col  = CarCollection::fromArray([$car1]);

        $col[0] = $car2;

        $this->assertSame('Honda', $col[0]->make);
    }

    public function testOffsetSetInvalidItemThrows(): void
    {
        $this->expectException(InvalidItemClassException::class);
        $col    = new CarCollection();
        $col[]  = 'not a car';
    }

    public function testOffsetExists(): void
    {
        $col = CarCollection::fromArray([$this->makeCar()]);

        $this->assertTrue(isset($col[0]));
        $this->assertFalse(isset($col[1]));
    }

    public function testOffsetUnsetRemovesAndReindexes(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('A'),
            $this->makeCar('B'),
        ]);

        unset($col[0]);

        $this->assertCount(1, $col);
        $this->assertSame('B', $col[0]->make);
    }

    // -------------------------------------------------------------------------
    // Countable
    // -------------------------------------------------------------------------

    public function testCountReturnsNumberOfItems(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar(),
            $this->makeCar(),
            $this->makeCar(),
        ]);

        $this->assertSame(3, count($col));
    }

    // -------------------------------------------------------------------------
    // Iterator (foreach)
    // -------------------------------------------------------------------------

    public function testForeachIteratesAllItems(): void
    {
        $cars = [
            $this->makeCar('Toyota'),
            $this->makeCar('Honda'),
            $this->makeCar('Ford'),
        ];
        $col = CarCollection::fromArray($cars);

        $result = [];
        foreach ($col as $key => $car) {
            $result[$key] = $car->make;
        }

        $this->assertSame([0 => 'Toyota', 1 => 'Honda', 2 => 'Ford'], $result);
    }

    public function testForeachIsRewindable(): void
    {
        $col = CarCollection::fromArray([$this->makeCar('Toyota')]);

        $first  = null;
        $second = null;

        foreach ($col as $car) {
            $first = $car->make;
        }
        foreach ($col as $car) {
            $second = $car->make;
        }

        $this->assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // filter
    // -------------------------------------------------------------------------

    public function testFilterReturnsMatchingItems(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota', 'Corolla', 2020),
            $this->makeCar('Honda',  'Civic',   2018),
            $this->makeCar('Toyota', 'Yaris',   2021),
        ]);

        $toyotas = $col->filter(fn(Car $c) => $c->make === 'Toyota');

        $this->assertCount(2, $toyotas);
        $this->assertSame('Corolla', $toyotas[0]->model);
        $this->assertSame('Yaris',   $toyotas[1]->model);
    }

    public function testFilterReturnsEmptyCollectionWhenNoMatch(): void
    {
        $col      = CarCollection::fromArray([$this->makeCar('Toyota')]);
        $filtered = $col->filter(fn(Car $c) => $c->make === 'Ferrari');

        $this->assertCount(0, $filtered);
        $this->assertInstanceOf(CarCollection::class, $filtered);
    }

    public function testFilterDoesNotMutateOriginal(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota'),
            $this->makeCar('Honda'),
        ]);

        $col->filter(fn(Car $c) => $c->make === 'Toyota');

        $this->assertCount(2, $col);
    }

    public function testFilterReturnsCorrectType(): void
    {
        $col      = CarCollection::fromArray([$this->makeCar()]);
        $filtered = $col->filter(fn() => true);

        $this->assertInstanceOf(CarCollection::class, $filtered);
    }

    // -------------------------------------------------------------------------
    // map
    // -------------------------------------------------------------------------

    public function testMapReturnsPlainArray(): void
    {
        $col    = CarCollection::fromArray([
            $this->makeCar('Toyota'),
            $this->makeCar('Honda'),
        ]);
        $makes  = $col->map(fn(Car $c) => $c->make);

        $this->assertIsArray($makes);
        $this->assertSame(['Toyota', 'Honda'], $makes);
    }

    public function testMapOnEmptyCollectionReturnsEmptyArray(): void
    {
        $col = new CarCollection();
        $this->assertSame([], $col->map(fn($c) => $c));
    }

    // -------------------------------------------------------------------------
    // reduce
    // -------------------------------------------------------------------------

    public function testReduceAggregatesValues(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota', 'Corolla', 2000),
            $this->makeCar('Honda',  'Civic',   2005),
            $this->makeCar('Ford',   'Focus',   2010),
        ]);

        $newest = $col->reduce(
            fn(int $carry, Car $c) => max($carry, $c->year),
            0
        );

        $this->assertSame(2010, $newest);
    }

    public function testReduceWithInitialValue(): void
    {
        $col    = CarCollection::fromArray([$this->makeCar('A'), $this->makeCar('B')]);
        $makes  = $col->reduce(
            fn(string $carry, Car $c) => $carry . $c->make,
            'Makes: '
        );

        $this->assertSame('Makes: AB', $makes);
    }

    public function testReduceOnEmptyCollectionReturnsInitial(): void
    {
        $col = new CarCollection();
        $this->assertSame(42, $col->reduce(fn($carry) => $carry, 42));
    }

    // -------------------------------------------------------------------------
    // first / last
    // -------------------------------------------------------------------------

    public function testFirstReturnsFirstItem(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota'),
            $this->makeCar('Honda'),
        ]);

        $this->assertSame('Toyota', $col->first()->make);
    }

    public function testFirstOnEmptyCollectionReturnsNull(): void
    {
        $this->assertNull((new CarCollection())->first());
    }

    public function testLastReturnsLastItem(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota'),
            $this->makeCar('Honda'),
        ]);

        $this->assertSame('Honda', $col->last()->make);
    }

    public function testLastOnEmptyCollectionReturnsNull(): void
    {
        $this->assertNull((new CarCollection())->last());
    }

    public function testFirstAndLastSameWhenOneItem(): void
    {
        $car = $this->makeCar('Toyota');
        $col = CarCollection::fromArray([$car]);

        $this->assertSame($col->first(), $col->last());
    }

    // -------------------------------------------------------------------------
    // contains
    // -------------------------------------------------------------------------

    public function testContainsReturnsTrueForExistingItem(): void
    {
        $car = $this->makeCar();
        $col = CarCollection::fromArray([$car]);

        $this->assertTrue($col->contains($car));
    }

    public function testContainsReturnsFalseForAbsentItem(): void
    {
        $col = CarCollection::fromArray([$this->makeCar('Toyota')]);

        $this->assertFalse($col->contains($this->makeCar('Toyota'))); // different instance
    }

    public function testContainsUsesStrictComparison(): void
    {
        // Two different Car instances with identical data are NOT the same object
        $car1 = $this->makeCar('Toyota', 'Corolla', 2020);
        $car2 = $this->makeCar('Toyota', 'Corolla', 2020);
        $col  = CarCollection::fromArray([$car1]);

        $this->assertFalse($col->contains($car2));
    }

    // -------------------------------------------------------------------------
    // isEmpty
    // -------------------------------------------------------------------------

    public function testIsEmptyReturnsTrueForEmptyCollection(): void
    {
        $this->assertTrue((new CarCollection())->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenItemsPresent(): void
    {
        $col = CarCollection::fromArray([$this->makeCar()]);
        $this->assertFalse($col->isEmpty());
    }

    public function testIsEmptyAfterPop(): void
    {
        $col = CarCollection::fromArray([$this->makeCar()]);
        $col->pop();

        $this->assertTrue($col->isEmpty());
    }

    // -------------------------------------------------------------------------
    // sort
    // -------------------------------------------------------------------------

    public function testSortReturnsSortedCollection(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota', 'Corolla', 2015),
            $this->makeCar('Honda',  'Civic',   2010),
            $this->makeCar('Ford',   'Focus',   2020),
        ]);

        $sorted = $col->sort(fn(Car $a, Car $b) => $a->year <=> $b->year);

        $this->assertSame(2010, $sorted[0]->year);
        $this->assertSame(2015, $sorted[1]->year);
        $this->assertSame(2020, $sorted[2]->year);
    }

    public function testSortDoesNotMutateOriginal(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('Toyota', 'Corolla', 2015),
            $this->makeCar('Honda',  'Civic',   2010),
        ]);

        $col->sort(fn(Car $a, Car $b) => $a->year <=> $b->year);

        $this->assertSame(2015, $col[0]->year); // original unchanged
    }

    public function testSortReturnsCorrectType(): void
    {
        $col    = CarCollection::fromArray([$this->makeCar()]);
        $sorted = $col->sort(fn() => 0);

        $this->assertInstanceOf(CarCollection::class, $sorted);
    }

    public function testSortDescending(): void
    {
        $col = CarCollection::fromArray([
            $this->makeCar('A', 'x', 2000),
            $this->makeCar('B', 'x', 2010),
            $this->makeCar('C', 'x', 2005),
        ]);

        $sorted = $col->sort(fn(Car $a, Car $b) => $b->year <=> $a->year);

        $this->assertSame(2010, $sorted[0]->year);
        $this->assertSame(2005, $sorted[1]->year);
        $this->assertSame(2000, $sorted[2]->year);
    }
}
