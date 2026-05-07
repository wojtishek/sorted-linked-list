<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\EmptyListException;

/**
 * @template T of AbstractSortedLinkedList
 */
abstract class AbstractSortedLinkedListTestCase extends TestCase
{
    /** @return T */
    abstract protected function createEmpty(): AbstractSortedLinkedList;

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

    public function testDuplicatesAreAllowedAndCounted(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);
        $list->add($sample[2]);
        $list->add($sample[2]);

        self::assertSame(3, $list->count());
        self::assertSame([$sample[2], $sample[2], $sample[2]], $list->toArray());
    }

    public function testDuplicateInsertedAfterExistingEqualValues(): void
    {
        // Use non-int/non-string distinguishability via strict identity is not possible
        // for primitive types — instead we verify ordering invariants relative to
        // surrounding distinct values: equals stay grouped, no surrounding ordering breaks.
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();

        // Build: sample[0], sample[2], sample[4]
        $list->add($sample[0]);
        $list->add($sample[2]);
        $list->add($sample[4]);

        // Insert another sample[2] — must land between the existing sample[2] and sample[4],
        // not before the existing sample[2].
        $list->add($sample[2]);

        self::assertSame(
            [$sample[0], $sample[2], $sample[2], $sample[4]],
            $list->toArray()
        );
    }

    public function testInsertingEqualToHeadDoesNotReplaceHead(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[2]);
        $list->add($sample[0]);

        // The new sample[0] should be inserted AFTER the existing one.
        // Verify by checking first() still returns the original head value
        // and the toArray order is correct.
        self::assertSame($sample[0], $list->first());
        self::assertSame([$sample[0], $sample[0], $sample[2]], $list->toArray());
        self::assertSame(3, $list->count());
    }

    public function testInsertingEqualToTailUpdatesTailPointer(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[4]);

        // Insert another value equal to the current tail.
        $list->add($sample[4]);

        self::assertSame($sample[4], $list->last());
        self::assertSame([$sample[0], $sample[4], $sample[4]], $list->toArray());
        self::assertSame(3, $list->count());
    }

    public function testContainsReturnsTrueForExistingValue(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        foreach ($this->ascendingSample() as $value) {
            self::assertTrue($list->contains($value), "expected list to contain $value");
        }
    }

    public function testContainsReturnsFalseForMissingValueSmallerThanAll(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueSmallerThanAll()));
    }

    public function testContainsReturnsFalseForMissingValueLargerThanAll(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueLargerThanAll()));
    }

    public function testContainsReturnsFalseForMissingValueInBetween(): void
    {
        // ascendingSample omits valueInBetween() (e.g. 25 missing from [1,2,3,4,5])
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueInBetween()));
    }

    public function testContainsReturnsFalseOnEmptyList(): void
    {
        self::assertFalse($this->createEmpty()->contains($this->ascendingSample()[0]));
    }

    public function testRemoveOnEmptyListReturnsFalse(): void
    {
        $list = $this->createEmpty();
        self::assertFalse($list->remove($this->ascendingSample()[0]));
        self::assertSame(0, $list->count());
    }

    public function testRemoveNonExistentValueReturnsFalse(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->remove($this->valueLargerThanAll()));
        self::assertFalse($list->remove($this->valueSmallerThanAll()));
        self::assertFalse($list->remove($this->valueInBetween()));
        self::assertSame($this->ascendingSample(), $list->toArray());
    }

    public function testRemoveSingleElementEmptiesTheList(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);

        self::assertTrue($list->remove($sample[0]));
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());

        // first()/last() must throw again after the list returns to empty state.
        $exceptionsThrown = 0;
        try {
            $list->first();
        } catch (EmptyListException) {
            ++$exceptionsThrown;
        }
        try {
            $list->last();
        } catch (EmptyListException) {
            ++$exceptionsThrown;
        }
        self::assertSame(2, $exceptionsThrown);
    }

    public function testRemoveHead(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();

        self::assertTrue($list->remove($sample[0]));
        self::assertSame($sample[1], $list->first());
        self::assertSame(\array_slice($sample, 1), $list->toArray());
        self::assertSame(\count($sample) - 1, $list->count());
    }

    public function testRemoveTailUpdatesTailPointer(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();

        self::assertTrue($list->remove($sample[\count($sample) - 1]));
        self::assertSame($sample[\count($sample) - 2], $list->last());
        self::assertSame(\array_slice($sample, 0, \count($sample) - 1), $list->toArray());
    }

    public function testRemoveMiddle(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();
        $middle = $sample[(int) (\count($sample) / 2)];

        self::assertTrue($list->remove($middle));
        self::assertNotContains($middle, $list->toArray());
        self::assertSame(\count($sample) - 1, $list->count());
    }

    public function testRemoveDuplicateRemovesOnlyOneOccurrence(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);
        $list->add($sample[2]);
        $list->add($sample[2]);

        self::assertTrue($list->remove($sample[2]));
        self::assertSame(2, $list->count());
        self::assertSame([$sample[2], $sample[2]], $list->toArray());

        self::assertTrue($list->remove($sample[2]));
        self::assertTrue($list->remove($sample[2]));
        self::assertFalse($list->remove($sample[2]));
        self::assertTrue($list->isEmpty());
    }

    public function testClearOnNonEmptyList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $list->clear();
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
        self::assertSame([], $list->toArray());
    }

    public function testJsonSerializeProducesArray(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }
        self::assertSame($this->ascendingSample(), $list->jsonSerialize());
    }

    public function testJsonEncodeProducesArrayNotObjectForEmptyList(): void
    {
        $encoded = json_encode($this->createEmpty());
        self::assertSame('[]', $encoded);
    }

    public function testJsonEncodeProducesArrayForPopulatedList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $encoded = json_encode($list);
        self::assertSame(json_encode($this->ascendingSample()), $encoded);
    }

    public function testToArrayReturnsZeroIndexedList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        $array = $list->toArray();
        self::assertSame(array_values($array), $array);
        self::assertSame(0, array_key_first($array));
        self::assertSame(\count($array) - 1, array_key_last($array));
    }

    public function testIterationYieldsZeroIndexedKeys(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $expectedIndex = 0;
        foreach ($list as $key => $value) {
            self::assertSame($expectedIndex, $key);
            ++$expectedIndex;
        }
    }

    public function testFromArrayWithEmptyArrayProducesEmptyList(): void
    {
        $list = static::fromArrayHelper($this, []);
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
    }

    public function testFromArraySortsValues(): void
    {
        $list = static::fromArrayHelper($this, $this->unsortedSample());
        self::assertSame($this->ascendingSample(), $list->toArray());
    }

    public function testFromArrayIgnoresStringKeys(): void
    {
        $sample = $this->ascendingSample();
        $assoc = ['a' => $sample[2], 'b' => $sample[0], 'c' => $sample[4]];

        $list = static::fromArrayHelper($this, $assoc);
        self::assertSame([$sample[0], $sample[2], $sample[4]], $list->toArray());
    }

    public function testFromArrayAllowsDuplicates(): void
    {
        $sample = $this->ascendingSample();
        $list = static::fromArrayHelper($this, [$sample[1], $sample[1], $sample[3]]);
        self::assertSame([$sample[1], $sample[1], $sample[3]], $list->toArray());
        self::assertSame(3, $list->count());
    }

    /**
     * Concrete test classes route through this so abstract tests can build
     * a list via fromArray without knowing the concrete class name.
     *
     * @param self<AbstractSortedLinkedList> $testCase
     * @param array<array-key, mixed>        $values
     */
    abstract protected static function fromArrayHelper(self $testCase, array $values): AbstractSortedLinkedList;
}
