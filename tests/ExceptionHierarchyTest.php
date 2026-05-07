<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\Exception\EmptyListException;
use Studio83\SortedLinkedList\Exception\InvalidValueException;
use Studio83\SortedLinkedList\Exception\SortedLinkedListException;

final class ExceptionHierarchyTest extends TestCase
{
    public function testMarkerInterfaceExtendsThrowable(): void
    {
        self::assertTrue(is_subclass_of(SortedLinkedListException::class, \Throwable::class));
    }

    public function testEmptyListExceptionExtendsLogicExceptionAndImplementsMarker(): void
    {
        $exception = new EmptyListException('list is empty');

        self::assertInstanceOf(\LogicException::class, $exception);
        self::assertInstanceOf(SortedLinkedListException::class, $exception);
    }

    public function testInvalidValueExceptionExtendsInvalidArgumentExceptionAndImplementsMarker(): void
    {
        $exception = new InvalidValueException('bad value');

        self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        self::assertInstanceOf(SortedLinkedListException::class, $exception);
    }

    public function testCatchAllViaMarkerInterface(): void
    {
        $caught = null;
        try {
            throw new EmptyListException('boom');
        } catch (SortedLinkedListException $e) {
            $caught = $e;
        }
        self::assertInstanceOf(EmptyListException::class, $caught);
    }
}
