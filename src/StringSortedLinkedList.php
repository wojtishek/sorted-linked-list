<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList;

use Studio83\SortedLinkedList\Exception\InvalidValueException;

/**
 * Sorted singly-linked list of string values.
 *
 * Comparison is byte-wise (PHP's `<=>` on strings). For ASCII / Latin-1
 * this matches alphabetical order; for UTF-8 multi-byte sequences the
 * order is by byte representation, which can surprise. If you need
 * locale-aware or Unicode-collation ordering, use a different data structure
 * with an `intl Collator`.
 */
final class StringSortedLinkedList extends AbstractSortedLinkedList
{
    public function __construct(string ...$values)
    {
        foreach ($values as $value) {
            $this->insertSorted($value);
        }
    }

    public function add(string $value): void
    {
        $this->insertSorted($value);
    }

    public function contains(string $value): bool
    {
        return $this->containsValue($value);
    }

    public function remove(string $value): bool
    {
        return $this->removeFirst($value);
    }

    public function first(): string
    {
        /** @var string $value */
        $value = parent::first();

        return $value;
    }

    public function last(): string
    {
        /** @var string $value */
        $value = parent::last();

        return $value;
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        /** @var list<string> $array */
        $array = parent::toArray();

        return $array;
    }

    /**
     * Build a list from an array of string values.
     *
     * Keys are ignored; only values are inserted.
     *
     * @param array<array-key, mixed> $values
     *
     * @throws InvalidValueException when any value is not a string
     */
    public static function fromArray(array $values): self
    {
        $list = new self();
        $index = 0;
        foreach ($values as $value) {
            if (!\is_string($value)) {
                throw new InvalidValueException(\sprintf('Expected string at index %d, got %s.', $index, get_debug_type($value)));
            }
            $list->insertSorted($value);
            ++$index;
        }

        return $list;
    }
}
