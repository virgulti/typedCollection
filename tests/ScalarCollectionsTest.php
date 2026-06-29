<?php

declare(strict_types=1);

namespace Virgulti\TypedCollection\Tests;

use PHPUnit\Framework\TestCase;
use Virgulti\TypedCollection\FloatCollection;
use Virgulti\TypedCollection\IntCollection;
use Virgulti\TypedCollection\StringCollection;
use Virgulti\TypedCollection\Exceptions\InvalidItemClassException;

class ScalarCollectionsTest extends TestCase
{
    // =========================================================================
    // IntCollection
    // =========================================================================

    public function testIntCollectionAcceptsIntegers(): void
    {
        $col = new IntCollection();
        $col->add(1);
        $col->add(42);

        $this->assertCount(2, $col);
    }

    public function testIntCollectionRejectsString(): void
    {
        $this->expectException(InvalidItemClassException::class);
        (new IntCollection())->add('not an int');
    }

    public function testIntCollectionRejectsFloat(): void
    {
        $this->expectException(InvalidItemClassException::class);
        (new IntCollection())->add(3.14);
    }

    public function testIntCollectionFromArray(): void
    {
        $col = IntCollection::fromArray([1, 2, 3]);
        $this->assertSame([1, 2, 3], $col->toArray());
    }

    public function testIntCollectionFromArrayRejectsInvalidItem(): void
    {
        $this->expectException(InvalidItemClassException::class);
        IntCollection::fromArray([1, 'two', 3]);
    }

    public function testIntCollectionFilter(): void
    {
        $col  = IntCollection::fromArray([1, 2, 3, 4, 5]);
        $even = $col->filter(fn(int $n) => $n % 2 === 0);

        $this->assertSame([2, 4], $even->toArray());
    }

    public function testIntCollectionMap(): void
    {
        $col    = IntCollection::fromArray([1, 2, 3]);
        $doubled = $col->map(fn(int $n) => $n * 2);

        $this->assertSame([2, 4, 6], $doubled);
    }

    public function testIntCollectionReduce(): void
    {
        $col = IntCollection::fromArray([1, 2, 3, 4]);
        $sum = $col->reduce(fn(int $carry, int $n) => $carry + $n, 0);

        $this->assertSame(10, $sum);
    }

    public function testIntCollectionSort(): void
    {
        $col    = IntCollection::fromArray([3, 1, 4, 1, 5, 9]);
        $sorted = $col->sort(fn(int $a, int $b) => $a <=> $b);

        $this->assertSame([1, 1, 3, 4, 5, 9], $sorted->toArray());
    }

    public function testIntCollectionFirstAndLast(): void
    {
        $col = IntCollection::fromArray([10, 20, 30]);

        $this->assertSame(10, $col->first());
        $this->assertSame(30, $col->last());
    }

    public function testIntCollectionContains(): void
    {
        $col = IntCollection::fromArray([1, 2, 3]);

        $this->assertTrue($col->contains(2));
        $this->assertFalse($col->contains(99));
    }

    public function testIntCollectionIsEmpty(): void
    {
        $empty = new IntCollection();
        $full  = IntCollection::fromArray([1]);

        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($full->isEmpty());
    }

    public function testIntCollectionForeach(): void
    {
        $col    = IntCollection::fromArray([10, 20, 30]);
        $result = [];

        foreach ($col as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([10, 20, 30], $result);
    }

    public function testIntCollectionArrayAccess(): void
    {
        $col    = new IntCollection();
        $col[]  = 7;
        $col[]  = 14;

        $this->assertSame(7,  $col[0]);
        $this->assertSame(14, $col[1]);
        $this->assertTrue(isset($col[0]));
        $this->assertFalse(isset($col[99]));
    }

    public function testIntCollectionPushPop(): void
    {
        $col = IntCollection::fromArray([1, 2]);
        $col->push(3);

        $this->assertSame(3, $col->pop());
        $this->assertCount(2, $col);
    }

    public function testIntCollectionFindBy(): void
    {
        $col = IntCollection::fromArray([10, 20, 30]);

        $this->assertSame(1,     $col->findBy('ignored', 20));
        $this->assertFalse($col->findBy('ignored', 99));
    }

    // =========================================================================
    // StringCollection
    // =========================================================================

    public function testStringCollectionAcceptsStrings(): void
    {
        $col = new StringCollection();
        $col->add('hello');
        $col->add('world');

        $this->assertCount(2, $col);
    }

    public function testStringCollectionRejectsInteger(): void
    {
        $this->expectException(InvalidItemClassException::class);
        (new StringCollection())->add(42);
    }

    public function testStringCollectionRejectsNull(): void
    {
        $this->expectException(InvalidItemClassException::class);
        (new StringCollection())->add(null);
    }

    public function testStringCollectionFromArray(): void
    {
        $col = StringCollection::fromArray(['foo', 'bar', 'baz']);
        $this->assertSame(['foo', 'bar', 'baz'], $col->toArray());
    }

    public function testStringCollectionFilter(): void
    {
        $col      = StringCollection::fromArray(['apple', 'banana', 'apricot', 'cherry']);
        $filtered = $col->filter(fn(string $s) => strpos($s, 'a') === 0);

        $this->assertSame(['apple', 'apricot'], $filtered->toArray());
    }

    public function testStringCollectionMap(): void
    {
        $col    = StringCollection::fromArray(['hello', 'world']);
        $upper  = $col->map('strtoupper');

        $this->assertSame(['HELLO', 'WORLD'], $upper);
    }

    public function testStringCollectionReduce(): void
    {
        $col        = StringCollection::fromArray(['a', 'b', 'c']);
        $concatenated = $col->reduce(fn(string $carry, string $s) => $carry . $s, '');

        $this->assertSame('abc', $concatenated);
    }

    public function testStringCollectionSort(): void
    {
        $col    = StringCollection::fromArray(['banana', 'apple', 'cherry']);
        $sorted = $col->sort('strcmp');

        $this->assertSame(['apple', 'banana', 'cherry'], $sorted->toArray());
    }

    public function testStringCollectionFirstAndLast(): void
    {
        $col = StringCollection::fromArray(['first', 'middle', 'last']);

        $this->assertSame('first', $col->first());
        $this->assertSame('last',  $col->last());
    }

    public function testStringCollectionContains(): void
    {
        $col = StringCollection::fromArray(['foo', 'bar']);

        $this->assertTrue($col->contains('foo'));
        $this->assertFalse($col->contains('baz'));
    }

    public function testStringCollectionMerge(): void
    {
        $a      = StringCollection::fromArray(['x', 'y']);
        $b      = StringCollection::fromArray(['z']);
        $merged = $a->merge($b);

        $this->assertSame(['x', 'y', 'z'], $merged->toArray());
        $this->assertCount(2, $a); // original unchanged
    }

    // =========================================================================
    // FloatCollection
    // =========================================================================

    public function testFloatCollectionAcceptsFloats(): void
    {
        $col = new FloatCollection();
        $col->add(1.5);
        $col->add(3.14);

        $this->assertCount(2, $col);
    }

    public function testFloatCollectionRejectsInteger(): void
    {
        // In PHP, 1 is an int, not a float — must be 1.0
        $this->expectException(InvalidItemClassException::class);
        (new FloatCollection())->add(1);
    }

    public function testFloatCollectionRejectsString(): void
    {
        $this->expectException(InvalidItemClassException::class);
        (new FloatCollection())->add('3.14');
    }

    public function testFloatCollectionFromArray(): void
    {
        $col = FloatCollection::fromArray([1.1, 2.2, 3.3]);
        $this->assertSame([1.1, 2.2, 3.3], $col->toArray());
    }

    public function testFloatCollectionReduce(): void
    {
        $col = FloatCollection::fromArray([1.5, 2.5, 3.0]);
        $sum = $col->reduce(fn(float $carry, float $n) => $carry + $n, 0.0);

        $this->assertEqualsWithDelta(7.0, $sum, 0.0001);
    }

    public function testFloatCollectionSort(): void
    {
        $col    = FloatCollection::fromArray([3.3, 1.1, 2.2]);
        $sorted = $col->sort(fn(float $a, float $b) => $a <=> $b);

        $this->assertSame([1.1, 2.2, 3.3], $sorted->toArray());
    }

    public function testFloatCollectionFirstAndLast(): void
    {
        $col = FloatCollection::fromArray([0.1, 0.5, 0.9]);

        $this->assertSame(0.1, $col->first());
        $this->assertSame(0.9, $col->last());
    }

    public function testFloatCollectionContains(): void
    {
        $col = FloatCollection::fromArray([1.0, 2.0, 3.0]);

        $this->assertTrue($col->contains(2.0));
        $this->assertFalse($col->contains(4.0));
    }

    public function testFloatCollectionIsEmpty(): void
    {
        $this->assertTrue((new FloatCollection())->isEmpty());
        $this->assertFalse(FloatCollection::fromArray([1.0])->isEmpty());
    }
}
