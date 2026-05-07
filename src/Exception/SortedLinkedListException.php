<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Exception;

/**
 * Marker interface implemented by every exception thrown by this library.
 *
 * Allows callers to catch all library-specific failures with a single catch block:
 *
 *     try {
 *         $list->first();
 *     } catch (SortedLinkedListException $e) {
 *         // handles EmptyListException, InvalidValueException, …
 *     }
 */
interface SortedLinkedListException extends \Throwable
{
}
