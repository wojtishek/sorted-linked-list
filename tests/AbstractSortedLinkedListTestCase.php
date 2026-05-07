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
}
