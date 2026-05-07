# studio83/sorted-linked-list

A small, type-safe sorted singly-linked list for PHP. A single instance holds either `int` values or `string` values — never both. The constraint is enforced at the language level by exposing two distinct concrete classes.

## Installation

```bash
composer require studio83/sorted-linked-list
```

Requires PHP 8.2 or newer.

## Usage

### Integers

```php
use Studio83\SortedLinkedList\IntSortedLinkedList;

$list = new IntSortedLinkedList(3, 1, 4, 1, 5);
$list->add(2);

$list->toArray();      // [1, 1, 2, 3, 4, 5]
$list->first();        // 1
$list->last();         // 5
$list->count();        // 6
$list->contains(4);    // true
$list->remove(1);      // true (removes ONE occurrence)
$list->toArray();      // [1, 2, 3, 4, 5]

foreach ($list as $value) {
    echo $value, "\n"; // 1, 2, 3, 4, 5
}

json_encode($list);    // "[1,2,3,4,5]"
```

### Strings

```php
use Studio83\SortedLinkedList\StringSortedLinkedList;

$list = StringSortedLinkedList::fromArray(['cherry', 'apple', 'banana']);
$list->toArray();      // ['apple', 'banana', 'cherry']
```

### Empty-list safety

The idiomatic guard is `isEmpty()`:

```php
$list = new IntSortedLinkedList();

if (!$list->isEmpty()) {
    $head = $list->first();
}
```

If pre-checking is awkward (e.g. inside a generic helper), catch the exception:

```php
use Studio83\SortedLinkedList\Exception\EmptyListException;

try {
    $head = $list->first();
} catch (EmptyListException $e) {
    // handle empty case
}
```

### Catching every library error

```php
use Studio83\SortedLinkedList\Exception\SortedLinkedListException;

try {
    IntSortedLinkedList::fromArray([1, 'not an int', 3]);
} catch (SortedLinkedListException $e) {
    // catches EmptyListException, InvalidValueException, …
}
```

## API Reference

| Method | Returns | Complexity | Notes |
|---|---|---|---|
| `__construct(T ...$values)` | — | O(n²) for n inserts | Variadic; supports empty initialisation |
| `static fromArray(array $values): self` | new instance | O(n²) | Validates element types; throws `InvalidValueException` |
| `add(T $value): void` | — | O(n) | Inserts at sorted position; stable for duplicates |
| `remove(T $value): bool` | bool | O(n) | Removes first occurrence; returns whether anything was removed |
| `contains(T $value): bool` | bool | O(n) worst | Early exit when sort order overshoots |
| `count(): int` | int | O(1) | Maintained as a counter |
| `isEmpty(): bool` | bool | O(1) | |
| `clear(): void` | — | O(1) | |
| `first(): T` | T | O(1) | Throws `EmptyListException` on empty |
| `last(): T` | T | O(1) | Tail pointer maintained |
| `toArray(): array` | `list<T>` | O(n) | Zero-indexed, ascending |
| `getIterator(): Generator` | Generator | O(1) memory | Implements `IteratorAggregate` |
| `jsonSerialize(): array` | `list<T>` | O(n) | Implements `JsonSerializable` |
| `clone $list` | new instance | O(n) | Deep-copies the node chain |

`T` = `int` for `IntSortedLinkedList`, `string` for `StringSortedLinkedList`.

## Design Decisions

### Why two concrete classes instead of one generic class

PHP has no language-level generics. Three approaches were considered:

| Approach | Verdict |
|---|---|
| Single class with PHPStan `@template` + runtime check | Type errors only at runtime; PHP itself doesn't enforce. |
| Single class with type-lock on first add | Latent bug — error surfaces only on the second wrong-typed add. |
| **Two final concrete classes + abstract parent** | **Chosen**. PHP enforces correctness at the language level; idiomatic Symfony pattern. |

### Stable insertion of duplicates

When inserting a value equal to one already in the list, the new element is placed **after** existing equals — insertion order is preserved among equals. This matches the standard expectation for a "sorted by key" collection.

### Explicit non-goals

- **No custom comparator.** That's a priority queue concern; this library uses natural ordering only.
- **No configurable descending mode.** Use `array_reverse($list->toArray())`.
- **No `get(int $index)` random access.** Contradicts the linked-list nature (O(n) random access). Use `toArray()` if you need indexing.
- **No immutable `with*()` API.** Linked lists naturally fit a mutable model.
- **No unified `SortedListInterface`.** It would need `int|string` argument types (LSP), losing the type sharpness of concrete classes.

## Limitations

- **String comparison is byte-wise**, not Unicode-aware. ASCII / Latin-1 sorts as expected; UTF-8 multi-byte sequences sort by byte representation. Example: `"z" <=> "ä"` returns `-1` (so `"z"` precedes `"ä"`). For locale-aware or Unicode-collation sorting, use `intl`'s `Collator` with a different data structure.
- **String equality is case-sensitive** (`"Foo" !== "foo"`).
- **O(n) insert and lookup.** For large collections or performance-critical paths, a balanced BST or sorted array with binary search will outperform this structure. This library prioritises clarity and a faithful "sorted linked list" implementation.
- **Modifying the list during `foreach` is undefined behaviour** (consistent with `ArrayIterator`). No fail-fast.

## Development

```bash
composer install
composer test       # PHPUnit
composer analyse    # PHPStan level 9
composer fix        # PHP-CS-Fixer
composer check      # analyse + test
```

## License

MIT — see [LICENSE](LICENSE).
