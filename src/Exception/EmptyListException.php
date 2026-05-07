<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Exception;

/**
 * Thrown when an operation requires a non-empty list (first(), last())
 * and the list has no elements.
 *
 * Extends LogicException because this is a caller error: the caller
 * should have checked isEmpty() before calling first()/last().
 */
final class EmptyListException extends \LogicException implements SortedLinkedListException
{
}
