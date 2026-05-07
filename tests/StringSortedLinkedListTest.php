<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\InvalidValueException;
use Studio83\SortedLinkedList\StringSortedLinkedList;

/**
 * @extends AbstractSortedLinkedListTestCase<StringSortedLinkedList>
 */
final class StringSortedLinkedListTest extends AbstractSortedLinkedListTestCase
{
    /** @return StringSortedLinkedList */
    protected function createEmpty(): AbstractSortedLinkedList
    {
        return new StringSortedLinkedList();
    }

    protected function fromValues(int|string ...$values): AbstractSortedLinkedList
    {
        $strings = [];
        foreach ($values as $value) {
            self::assertIsString($value);
            $strings[] = $value;
        }

        return new StringSortedLinkedList(...$strings);
    }

    protected function ascendingSample(): array
    {
        return ['apple', 'banana', 'cherry', 'date', 'elderberry'];
    }

    protected function unsortedSample(): array
    {
        return ['cherry', 'apple', 'elderberry', 'banana', 'date'];
    }

    protected function valueSmallerThanAll(): int|string
    {
        return 'aardvark';
    }

    protected function valueLargerThanAll(): int|string
    {
        return 'zebra';
    }

    protected function valueInBetween(): int|string
    {
        return 'cucumber';
    }

    /**
     * @param AbstractSortedLinkedListTestCase<StringSortedLinkedList> $testCase
     * @param array<array-key, mixed>                                  $values
     */
    protected static function fromArrayHelper(AbstractSortedLinkedListTestCase $testCase, array $values): AbstractSortedLinkedList
    {
        return StringSortedLinkedList::fromArray($values);
    }

    public function testFromArrayWithStringValues(): void
    {
        $list = StringSortedLinkedList::fromArray(['c', 'a', 'b']);
        self::assertSame(['a', 'b', 'c'], $list->toArray());
    }

    public function testFromArrayWithNonStringValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/index 1/');
        StringSortedLinkedList::fromArray(['x', 42, 'y']);
    }

    public function testFirstReturnsStringType(): void
    {
        $list = new StringSortedLinkedList('c', 'a', 'b');
        $first = $list->first();
        self::assertIsString($first);
        self::assertSame('a', $first);
    }

    public function testLastReturnsStringType(): void
    {
        $list = new StringSortedLinkedList('c', 'a', 'b');
        $last = $list->last();
        self::assertIsString($last);
        self::assertSame('c', $last);
    }

    public function testByteWiseComparisonOrder(): void
    {
        // ASCII byte order: uppercase before lowercase, digits before letters.
        $list = new StringSortedLinkedList('apple', 'Banana', '1apple', 'aardvark');
        self::assertSame(['1apple', 'Banana', 'aardvark', 'apple'], $list->toArray());
    }

    public function testAddRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList();
        /* @phpstan-ignore-next-line */
        $list->add(42);
    }

    public function testRemoveRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList('a', 'b');
        /* @phpstan-ignore-next-line */
        $list->remove(0);
    }

    public function testContainsRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList('a', 'b');
        /* @phpstan-ignore-next-line */
        $list->contains(0);
    }
}
