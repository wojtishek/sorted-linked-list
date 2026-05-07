<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList;

/**
 * Sorted singly-linked list of int values.
 */
final class IntSortedLinkedList extends AbstractSortedLinkedList
{
    public function __construct(int ...$values)
    {
        // Insert logic added in Task 5.
        // For now the variadic just consumes the values without storing them;
        // tests in Task 4 only cover the empty-list case.
        unset($values);
    }
}
