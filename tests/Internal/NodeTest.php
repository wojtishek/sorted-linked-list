<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\Internal\Node;

final class NodeTest extends TestCase
{
    public function testIntValueIsAccessibleAndReadonly(): void
    {
        $node = new Node(42);
        self::assertSame(42, $node->value);
        self::assertNull($node->next);

        $reflection = new \ReflectionProperty($node, 'value');
        self::assertTrue($reflection->isReadOnly());
    }

    public function testStringValueIsAccessibleAndReadonly(): void
    {
        $node = new Node('foo');
        self::assertSame('foo', $node->value);
    }

    public function testNextIsMutable(): void
    {
        $first = new Node(1);
        $second = new Node(2);

        self::assertNull($first->next);
        $first->next = $second;
        self::assertSame($second, $first->next);

        $first->next = null;
        self::assertNull($first->next);
    }

    public function testCanConstructWithNextProvided(): void
    {
        $tail = new Node(2);
        $head = new Node(1, $tail);

        self::assertSame(1, $head->value);
        self::assertSame($tail, $head->next);
    }
}
