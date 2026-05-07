<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Internal;

/**
 * Singly-linked list node.
 *
 * @internal Not part of the public API; subject to change without notice.
 */
final class Node
{
    public function __construct(
        public readonly int|string $value,
        public ?Node $next = null,
    ) {
    }
}
