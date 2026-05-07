<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\InvalidValueException;
use Studio83\SortedLinkedList\IntSortedLinkedList;

/**
 * @extends AbstractSortedLinkedListTestCase<IntSortedLinkedList>
 */
final class IntSortedLinkedListTest extends AbstractSortedLinkedListTestCase
{
    /** @return IntSortedLinkedList */
    protected function createEmpty(): AbstractSortedLinkedList
    {
        return new IntSortedLinkedList();
    }

    protected function fromValues(int|string ...$values): AbstractSortedLinkedList
    {
        $ints = [];
        foreach ($values as $value) {
            self::assertIsInt($value);
            $ints[] = $value;
        }

        return new IntSortedLinkedList(...$ints);
    }

    protected function ascendingSample(): array
    {
        return [1, 2, 3, 4, 5];
    }

    protected function unsortedSample(): array
    {
        return [3, 1, 5, 2, 4];
    }

    protected function valueSmallerThanAll(): int|string
    {
        return 0;
    }

    protected function valueLargerThanAll(): int|string
    {
        return 99;
    }

    protected function valueInBetween(): int|string
    {
        return 25;
    }

    /**
     * @param AbstractSortedLinkedListTestCase<IntSortedLinkedList> $testCase
     * @param array<array-key, mixed>                               $values
     */
    protected static function fromArrayHelper(AbstractSortedLinkedListTestCase $testCase, array $values): AbstractSortedLinkedList
    {
        return IntSortedLinkedList::fromArray($values);
    }

    public function testFromArrayWithIntValues(): void
    {
        $list = IntSortedLinkedList::fromArray([3, 1, 2]);
        self::assertSame([1, 2, 3], $list->toArray());
    }

    public function testFromArrayWithNonIntValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/index 1/');
        IntSortedLinkedList::fromArray([1, 'two', 3]);
    }

    public function testFromArrayWithFloatValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, 2.5, 3]);
    }

    public function testFromArrayWithBoolValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, true, 3]);
    }

    public function testFromArrayWithNullValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, null, 3]);
    }

    public function testFirstReturnsIntType(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $first = $list->first();
        self::assertIsInt($first);
        self::assertSame(1, $first);
    }

    public function testLastReturnsIntType(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $last = $list->last();
        self::assertIsInt($last);
        self::assertSame(3, $last);
    }

    public function testToArrayReturnsListOfInts(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $array = $list->toArray();
        self::assertContainsOnly('int', $array);
    }
}
