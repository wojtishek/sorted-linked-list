<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Exception;

/**
 * Thrown when a bulk-loading operation (fromArray()) encounters a value
 * whose type does not match the list's element type.
 *
 * Note: single-value entry points (add(), remove(), contains()) rely on
 * PHP's native \TypeError instead — the language already enforces the type
 * via parameter declarations.
 */
final class InvalidValueException extends \InvalidArgumentException implements SortedLinkedListException
{
}
