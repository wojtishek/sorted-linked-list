<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\IntSortedLinkedList;

final class CloneTest extends TestCase
{
    public function testCloneOfEmptyListIsIndependentEmptyList(): void
    {
        $original = new IntSortedLinkedList();
        $copy = clone $original;

        $copy->add(1);
        self::assertSame(0, $original->count());
        self::assertSame(1, $copy->count());
    }

    public function testMutatingCloneDoesNotAffectOriginal(): void
    {
        $original = new IntSortedLinkedList(2, 4, 6);
        $copy = clone $original;

        $copy->add(5);

        self::assertSame([2, 4, 6], $original->toArray());
        self::assertSame([2, 4, 5, 6], $copy->toArray());
    }

    public function testMutatingOriginalDoesNotAffectClone(): void
    {
        $original = new IntSortedLinkedList(2, 4, 6);
        $copy = clone $original;

        $original->add(5);

        self::assertSame([2, 4, 5, 6], $original->toArray());
        self::assertSame([2, 4, 6], $copy->toArray());
    }

    public function testRemovingFromOriginalDoesNotAffectClone(): void
    {
        $original = new IntSortedLinkedList(1, 2, 3);
        $copy = clone $original;

        $original->remove(2);

        self::assertSame([1, 3], $original->toArray());
        self::assertSame([1, 2, 3], $copy->toArray());
    }

    public function testCloneCountMatchesOriginal(): void
    {
        $original = new IntSortedLinkedList(1, 2, 3, 4, 5);
        $copy = clone $original;

        self::assertSame(5, $copy->count());
    }

    public function testCloneTailIsIndependent(): void
    {
        // After cloning then appending to the original, the clone's tail must not move.
        $original = new IntSortedLinkedList(1, 2, 3);
        $copy = clone $original;

        $original->add(99);

        self::assertSame(3, $copy->last());
        self::assertSame(99, $original->last());
    }
}
