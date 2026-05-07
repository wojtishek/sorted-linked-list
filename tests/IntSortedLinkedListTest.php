<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use Studio83\SortedLinkedList\AbstractSortedLinkedList;
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
}
