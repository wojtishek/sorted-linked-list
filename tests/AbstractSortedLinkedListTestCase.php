<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\EmptyListException;

abstract class AbstractSortedLinkedListTestCase extends TestCase
{
    abstract protected function createEmpty(): AbstractSortedLinkedList;

    /**
     * Construct a list containing exactly the given values, in the order provided
     * (the list itself will sort them).
     */
    abstract protected function fromValues(int|string ...$values): AbstractSortedLinkedList;

    /** @return list<int|string> 5 distinct values in ascending order */
    abstract protected function ascendingSample(): array;

    /** @return list<int|string> the same 5 values in non-ascending order */
    abstract protected function unsortedSample(): array;

    /** A value smaller than every value in ascendingSample() */
    abstract protected function valueSmallerThanAll(): int|string;

    /** A value larger than every value in ascendingSample() */
    abstract protected function valueLargerThanAll(): int|string;

    /** A value not present in ascendingSample(), strictly between min and max */
    abstract protected function valueInBetween(): int|string;

    public function testEmptyListCountIsZero(): void
    {
        self::assertSame(0, $this->createEmpty()->count());
    }

    public function testEmptyListIsEmpty(): void
    {
        self::assertTrue($this->createEmpty()->isEmpty());
    }

    public function testEmptyListIteratesZeroTimes(): void
    {
        $values = [];
        foreach ($this->createEmpty() as $value) {
            $values[] = $value;
        }
        self::assertSame([], $values);
    }

    public function testEmptyListToArrayReturnsEmptyArray(): void
    {
        self::assertSame([], $this->createEmpty()->toArray());
    }

    public function testFirstThrowsOnEmptyList(): void
    {
        $this->expectException(EmptyListException::class);
        $this->createEmpty()->first();
    }

    public function testLastThrowsOnEmptyList(): void
    {
        $this->expectException(EmptyListException::class);
        $this->createEmpty()->last();
    }

    public function testClearOnEmptyListIsNoop(): void
    {
        $list = $this->createEmpty();
        $list->clear();
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
    }

    public function testAddSingleValueResultsInCountOne(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);

        self::assertSame(1, $list->count());
        self::assertFalse($list->isEmpty());
    }

    public function testAddSingleValueMakesItBothFirstAndLast(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);

        self::assertSame($sample[2], $list->first());
        self::assertSame($sample[2], $list->last());
    }

    public function testAddTwoAscendingValues(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[1]);
        $list->add($sample[3]);

        self::assertSame(2, $list->count());
        self::assertSame($sample[1], $list->first());
        self::assertSame($sample[3], $list->last());
        self::assertSame([$sample[1], $sample[3]], $list->toArray());
    }

    public function testAddTwoDescendingValues(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[3]);
        $list->add($sample[1]);

        self::assertSame(2, $list->count());
        self::assertSame($sample[1], $list->first());
        self::assertSame($sample[3], $list->last());
        self::assertSame([$sample[1], $sample[3]], $list->toArray());
    }

    public function testAddSmallerThanHead(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $smaller = $this->valueSmallerThanAll();
        $list->add($smaller);

        self::assertSame($smaller, $list->first());
        $expected = $this->ascendingSample();
        array_unshift($expected, $smaller);
        self::assertSame($expected, $list->toArray());
    }

    public function testAddLargerThanTail(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $larger = $this->valueLargerThanAll();
        $list->add($larger);

        self::assertSame($larger, $list->last());
        $expected = $this->ascendingSample();
        $expected[] = $larger;
        self::assertSame($expected, $list->toArray());
    }

    public function testAddInMiddle(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[4]);

        $list->add($sample[2]);

        self::assertSame([$sample[0], $sample[2], $sample[4]], $list->toArray());
        self::assertSame($sample[0], $list->first());
        self::assertSame($sample[4], $list->last());
    }

    public function testAddManyInRandomOrderProducesSortedResult(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        self::assertSame($this->ascendingSample(), $list->toArray());
        self::assertSame(\count($this->ascendingSample()), $list->count());
    }

    public function testIterationYieldsValuesInAscendingOrder(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        $yielded = [];
        foreach ($list as $value) {
            $yielded[] = $value;
        }
        self::assertSame($this->ascendingSample(), $yielded);
    }
}
