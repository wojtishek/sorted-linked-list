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
        foreach ($values as $value) {
            $this->insertSorted($value);
        }
    }

    public function add(int $value): void
    {
        $this->insertSorted($value);
    }
}
