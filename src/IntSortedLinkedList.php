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

    public function contains(int $value): bool
    {
        return $this->containsValue($value);
    }

    public function remove(int $value): bool
    {
        return $this->removeFirst($value);
    }

    /**
     * Build a list from an array of int values.
     *
     * Keys are ignored; only values are inserted (in iteration order before sorting).
     *
     * @param array<array-key, mixed> $values
     *
     * @throws Exception\InvalidValueException when any value is not an int
     */
    public static function fromArray(array $values): self
    {
        $list = new self();
        $index = 0;
        foreach ($values as $value) {
            if (!\is_int($value)) {
                throw new Exception\InvalidValueException(\sprintf('Expected int at index %d, got %s.', $index, get_debug_type($value)));
            }
            $list->insertSorted($value);
            ++$index;
        }

        return $list;
    }
}
