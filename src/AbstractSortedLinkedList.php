<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList;

use Studio83\SortedLinkedList\Exception\EmptyListException;
use Studio83\SortedLinkedList\Internal\Node;

/**
 * Shared state and algorithms for a sorted singly-linked list.
 *
 * Concrete subclasses (IntSortedLinkedList, StringSortedLinkedList) provide
 * type-narrowed public entry points (add / remove / contains) that delegate
 * to the protected helpers defined here.
 *
 * The split is forced by PHP's contravariance rules: a subclass cannot
 * narrow an int|string parameter to int.
 *
 * @implements \IteratorAggregate<int, int|string>
 */
abstract class AbstractSortedLinkedList implements \Countable, \IteratorAggregate, \JsonSerializable
{
    protected ?Node $head = null;
    protected ?Node $tail = null;
    protected int $count = 0;

    final public function count(): int
    {
        return $this->count;
    }

    final public function isEmpty(): bool
    {
        return 0 === $this->count;
    }

    final public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->count = 0;
    }

    abstract public function add(int|string $value): void;

    /**
     * Insert a value at its sorted position.
     *
     * Stable: equal values are placed AFTER existing equals (insertion order
     * is preserved among equal elements).
     */
    final protected function insertSorted(int|string $value): void
    {
        $newNode = new Node($value);

        if (null === $this->head) {
            $this->head = $newNode;
            $this->tail = $newNode;
            $this->count = 1;

            return;
        }

        if (($value <=> $this->head->value) < 0) {
            $newNode->next = $this->head;
            $this->head = $newNode;
            ++$this->count;

            return;
        }

        // Walk past all values <= $value so the new node lands AFTER existing equals (stable).
        $current = $this->head;
        while (null !== $current->next && ($current->next->value <=> $value) <= 0) {
            $current = $current->next;
        }

        $newNode->next = $current->next;
        $current->next = $newNode;

        if (null === $newNode->next) {
            $this->tail = $newNode;
        }

        ++$this->count;
    }

    /**
     * @return \Generator<int, int|string>
     */
    final public function getIterator(): \Generator
    {
        $current = $this->head;
        $i = 0;
        while (null !== $current) {
            yield $i => $current->value;
            $current = $current->next;
            ++$i;
        }
    }

    /**
     * @return list<int|string>
     */
    public function toArray(): array
    {
        $result = [];
        $current = $this->head;
        while (null !== $current) {
            $result[] = $current->value;
            $current = $current->next;
        }

        return $result;
    }

    /**
     * @return list<int|string>
     */
    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function first(): int|string
    {
        if (null === $this->head) {
            throw new EmptyListException('Cannot call first() on an empty list.');
        }

        return $this->head->value;
    }

    public function last(): int|string
    {
        if (null === $this->tail) {
            throw new EmptyListException('Cannot call last() on an empty list.');
        }

        return $this->tail->value;
    }
}
