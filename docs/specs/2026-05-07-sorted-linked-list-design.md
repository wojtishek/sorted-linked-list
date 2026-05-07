# SortedLinkedList — Design Specification

**Date:** 2026-05-07
**Package:** `studio83/sorted-linked-list`
**Namespace:** `Studio83\SortedLinkedList`
**PHP requirement:** `>= 8.2`
**License:** MIT

---

## 1. Overview & Scope

A small PHP library providing a singly-linked list that maintains its elements in ascending sorted order. A single instance holds either `int` values or `string` values — never both. The constraint is enforced at the language level by exposing two distinct concrete classes.

### What this library is
- A self-contained, type-safe sorted collection
- Composer-installable, framework-agnostic (works in any PHP 8.2+ project, naturally fits Symfony)
- Optimised for clarity, not for raw performance

### What this library is not
- Not a priority queue (no custom comparator, no peek-and-extract semantics)
- Not a sorted set (duplicates are allowed)
- Not a balanced tree, not an indexed structure (no O(log n) operations, no random access)
- Not locale/Unicode-collation aware (string ordering is byte-wise)

### Non-goals (explicit)
| Excluded | Why |
|---|---|
| Custom comparator | Belongs to a priority queue, not a sorted list |
| Configurable descending order | Trivially achievable via `array_reverse($list->toArray())` |
| `get(int $index)` random access | Contradicts the linked-list nature (O(n)); use `toArray()` |
| Immutable `with*()` API | Linked lists naturally fit a mutable model; `with*` API is over-engineering for this scope |
| Unified `SortedListInterface` | Methods would need `int\|string` argument types (LSP), losing the type sharpness of concrete classes |

---

## 2. Type-Safety Strategy

PHP has no language-level generics. The chosen approach: **two concrete final classes sharing an abstract parent.**

```
AbstractSortedLinkedList   (abstract — shared state and shared algorithms)
├── IntSortedLinkedList    (final — public API typed to int)
└── StringSortedLinkedList (final — public API typed to string)
```

### Why this and not alternatives

| Alternative | Verdict |
|---|---|
| Single class with PHPStan `@template` + runtime check | Rejected: type errors only at runtime; PHP itself doesn't enforce. |
| Single class with type-lock on first add | Rejected: latent bug, IDE has no insight, defeats type safety. |
| **Two concrete classes + abstract parent** | **Chosen**: PHP itself enforces correctness; idiomatic Symfony pattern. |

### Why the abstract parent cannot declare `add()` / `remove()` / `contains()`

PHP requires **contravariant argument types**. `int` is not a supertype of `int|string`, so a child overriding `add(int|string)` with `add(int)` violates LSP and PHP raises a fatal error. Therefore:

- **Shared algorithms** live in `protected` methods on the parent: `insertSorted(int|string)`, `removeFirst(int|string)`, `containsValue(int|string)`.
- **Public typed wrappers** live on the concrete classes and delegate to the protected parent methods.

Return types may be **narrowed** in concrete classes (PHP supports return-type covariance), so `first(): int` on `IntSortedLinkedList` is legitimate.

---

## 3. Public API

Both `IntSortedLinkedList` and `StringSortedLinkedList` expose the same surface, with `T` = `int` or `string` respectively.

### Construction

| Signature | Behaviour |
|---|---|
| `__construct(T ...$values)` | Variadic; supports both empty and pre-populated initialisation. PHP's parameter type enforces correctness. |
| `static fromArray(array $values): self` | Bulk init. Validates every element is of type `T`; throws `InvalidValueException` with the offending index otherwise. Associative arrays are accepted; only values are used (keys ignored). |

### Mutation

| Signature | Behaviour | Complexity |
|---|---|---|
| `add(T $value): void` | Insert at the correct sorted position | O(n) |
| `remove(T $value): bool` | Remove first occurrence; returns whether anything was removed | O(n) |
| `clear(): void` | Empty the list | O(1) |

### Queries

| Signature | Behaviour | Complexity |
|---|---|---|
| `contains(T $value): bool` | Linear search with early exit on overshoot | O(n) worst, often less |
| `count(): int` | Element count (maintained as a property) | O(1) |
| `isEmpty(): bool` | | O(1) |
| `first(): T` | Smallest element. Throws `EmptyListException` on empty. | O(1) |
| `last(): T` | Largest element. Throws `EmptyListException` on empty. | O(1) (tail pointer maintained) |

### Exports & interop

| Signature | Behaviour |
|---|---|
| `toArray(): array` | List<T> in ascending order. PHPDoc `@return list<int>` / `@return list<string>` for static analysis. |
| `getIterator(): \Generator` | Implements `IteratorAggregate`. Walks the node chain; O(1) memory. |
| `jsonSerialize(): array` | Returns the same as `toArray()`; produces a JSON array when encoded. |

### Implemented interfaces

- `\Countable`
- `\IteratorAggregate`
- `\JsonSerializable`

---

## 4. Internal Structure

### Project layout

```
Shipmonk-SortedLinkedList/
├── composer.json
├── README.md
├── LICENSE
├── phpunit.xml.dist
├── phpstan.neon.dist
├── .php-cs-fixer.dist.php
├── .github/
│   └── workflows/
│       └── ci.yml
├── docs/
│   └── specs/
│       └── 2026-05-07-sorted-linked-list-design.md  (this file)
├── src/
│   ├── AbstractSortedLinkedList.php
│   ├── IntSortedLinkedList.php
│   ├── StringSortedLinkedList.php
│   ├── Internal/
│   │   └── Node.php                          (@internal)
│   └── Exception/
│       ├── SortedLinkedListException.php     (interface, marker)
│       ├── EmptyListException.php
│       └── InvalidValueException.php
└── tests/
    ├── AbstractSortedLinkedListTestCase.php
    ├── IntSortedLinkedListTest.php
    ├── StringSortedLinkedListTest.php
    └── CloneTest.php
```

### `AbstractSortedLinkedList`

- **State (protected):** `?Node $head`, `?Node $tail`, `int $count`
- **Final shared methods (no override):**
  - `protected insertSorted(int|string $value): void`
  - `protected removeFirst(int|string $value): bool`
  - `protected containsValue(int|string $value): bool`
  - `public count(): int`
  - `public isEmpty(): bool`
  - `public clear(): void`
  - `public getIterator(): \Generator`
  - `public jsonSerialize(): array`
  - `public first(): int|string` (throws `EmptyListException` on empty)
  - `public last(): int|string` (throws `EmptyListException` on empty)
  - `public toArray(): array`
  - `public __clone(): void` (deep-copies the node chain — see §5.4)

### Concrete classes (`IntSortedLinkedList`, `StringSortedLinkedList`)

Each concrete class declares:

- `__construct(int ...$values)` / `__construct(string ...$values)` calling `insertSorted` for each
- `public static fromArray(array $values): self` with type validation
- `public add(T $value): void` — delegates to `$this->insertSorted($value)`
- `public remove(T $value): bool` — delegates to `$this->removeFirst($value)`
- `public contains(T $value): bool` — delegates to `$this->containsValue($value)`
- `public first(): T` — narrowed return type
- `public last(): T` — narrowed return type
- `public toArray(): array` with `@return list<T>` PHPDoc

### `Internal\Node`

```php
namespace Studio83\SortedLinkedList\Internal;

/** @internal */
final class Node
{
    public function __construct(
        public readonly int|string $value,
        public ?Node $next = null,
    ) {}
}
```

`value` is `readonly` (we never mutate the value of a node, only insert/remove nodes). `next` remains writable because relinking is the basis of all list operations.

### Comparison

The library uses PHP's `<=>` operator directly inside `insertSorted` / `removeFirst` / `containsValue`. The public API of each concrete class is type-narrowed, so values reaching these methods are always homogeneous (all `int` or all `string`). `<=>` produces correct results for both:

- `int <=> int` — numeric comparison
- `string <=> string` — byte-wise lexicographic comparison

**No abstract `compare()` extension point** — adding one would imply support for custom comparators, which is an explicit non-goal.

### Exception hierarchy

```php
namespace Studio83\SortedLinkedList\Exception;

interface SortedLinkedListException extends \Throwable {}

final class EmptyListException
    extends \LogicException
    implements SortedLinkedListException {}

final class InvalidValueException
    extends \InvalidArgumentException
    implements SortedLinkedListException {}
```

- The marker interface allows callers to `catch (SortedLinkedListException $e)` for everything this library throws.
- `EmptyListException` extends `\LogicException` (caller error — should have checked `isEmpty()`).
- `InvalidValueException` extends `\InvalidArgumentException` (invalid input passed by caller).
- `add()` / `remove()` / `contains()` rely on PHP's native `\TypeError` for type violations — we deliberately do **not** wrap that.

### Strict types

`declare(strict_types=1);` at the top of **every** file. Without it, `IntSortedLinkedList::add(int $value)` would silently accept `"5"` via implicit conversion, which would defeat the entire premise of the library.

---

## 5. Algorithms

### 5.1 `insertSorted` — stable insert

New equal values are inserted **after** all existing equal values, preserving insertion order among equals (stable ordering).

```
1. Empty list                    → newNode becomes both head and tail
2. value < head.value            → newNode becomes new head
3. Walk: while current.next !== null and current.next.value <= value
   → advance current             (note: <= is what makes it stable)
4. Insert newNode between current and current.next
5. If newNode.next === null      → newNode is the new tail
6. Increment count
```

The `<=` predicate is intentional. With `<` the new equal value would land **before** existing equals (LIFO among equals).

### 5.2 `removeFirst` — early-exit, tail-aware

```
1. Empty list                    → return false
2. head.value === value          → unlink head; if list now empty, also clear tail
3. Walk: while prev.next !== null and prev.next.value < value
   → advance prev                (note: <, not <=, because we need to stop AT the value)
4. If prev.next === null or prev.next.value !== value → return false
5. Unlink prev.next; if removed === tail → tail becomes prev
6. Decrement count; return true
```

The asymmetry (`<=` for insert, `<` for remove) is deliberate, not cosmetic. Insert needs to skip past equals (for stability); remove needs to stop at the first equal (to remove the first occurrence).

### 5.3 `containsValue` — early-exit search

```
walk current from head while current !== null and current.value < value
return current !== null and current.value === value
```

Average case is better than naive linear scan because the sorted invariant lets us stop as soon as we overshoot.

### 5.4 `__clone` — deep copy of node chain

PHP's default `clone` performs a shallow copy. Without intervention, a cloned list would share `Node` instances with its source — modifying either would corrupt the other (because list operations mutate `next` pointers of shared nodes).

```php
public function __clone(): void
{
    if ($this->head === null) {
        return;
    }

    $newHead = new Node($this->head->value);
    $newTail = $newHead;
    $current = $this->head->next;

    while ($current !== null) {
        $newTail->next = new Node($current->value);
        $newTail = $newTail->next;
        $current = $current->next;
    }

    $this->head = $newHead;
    $this->tail = $newTail;
    // $count is an int — already copied by value
}
```

A dedicated `CloneTest` verifies independence of clone and original under further mutation.

---

## 6. Edge Cases (exhaustive)

| Situation | Behaviour |
|---|---|
| `add` on empty | head = tail = newNode |
| `add` smaller than head | newNode becomes new head; tail unchanged |
| `add` larger than tail | newNode becomes new tail |
| `add` duplicate | inserted after existing equals (stable) |
| `remove` on empty | returns `false`, no-op |
| `remove` only element | head = tail = null, count = 0 |
| `remove` non-existent | returns `false`, no change |
| `remove` head | head replaced by head.next; if list empties, tail also nulled |
| `remove` tail | walk locates penultimate; it becomes new tail |
| `first()/last()` on empty | throws `EmptyListException` |
| `contains` on empty | returns `false` |
| `foreach` on empty | zero iterations, no error |
| `toArray()` on empty | returns `[]` |
| `jsonSerialize()` on empty | returns `[]`; encodes as JSON `[]` (not `{}`) |
| `fromArray([])` | empty list, valid |
| `fromArray([1, 'x'])` on `IntSortedLinkedList` | `InvalidValueException` with message identifying the offending index |
| `fromArray($assocArray)` | values used in iteration order; keys ignored |
| Modification during iteration | undefined behaviour; documented in `getIterator()` PHPDoc; no fail-fast (consistent with `ArrayIterator`) |
| `clone $list` | independent deep copy via `__clone` |
| `serialize($list)` | works out of the box (PHP serialises the Node chain); custom `__serialize` is not required |

### String-specific caveats (documented in README)

- `<=>` on strings is **byte-wise**, not Unicode-aware. ASCII / Latin-1 sorts as expected; UTF-8 multi-byte sequences sort by their byte representation, which can surprise (`"z" <=> "ä"` returns `-1`).
- `===` (used in `remove` / `contains`) is case-sensitive.
- For locale-aware ordering, this library is the wrong tool — use `intl`'s `Collator` with a different data structure.

---

## 7. Testing Strategy

### Tooling
- **PHPUnit 11** as the test runner
- **PHPStan level 9** for static analysis (no baseline)
- **PHP-CS-Fixer** with `@PSR12` + `@Symfony` presets
- **Coverage target:** ≥ 95% (aim for 100% — the library is small enough)

### Test architecture

```
AbstractSortedLinkedListTestCase  (abstract — every test method, parameterised by type)
├── IntSortedLinkedListTest       (provides int factories and sample data)
└── StringSortedLinkedListTest    (provides string factories and sample data)

CloneTest                         (cross-cutting tests for deep-copy behaviour)
```

The abstract base class declares factory methods to be implemented by concrete tests:

```php
abstract protected function createEmpty(): AbstractSortedLinkedList;
abstract protected function fromArray(array $values): AbstractSortedLinkedList;
/** @return list<int|string> ascending sample, length >= 5 */
abstract protected function ascendingSample(): array;
abstract protected function unsortedSample(): array;
abstract protected function differentValue(): int|string;
```

This is preferred over data-providers or traits because it preserves precise typing in IDE and static analysis, and clearly signals intent.

### Test coverage checklist

**State observation on empty list**
- `count()` = 0, `isEmpty()` = true, `foreach` yields nothing, `toArray()` = `[]`, `jsonSerialize()` = `[]`, `first()` and `last()` throw `EmptyListException`

**Insert**
- Single insert → count 1, head equals tail
- Insert at head (smaller than current head)
- Insert at tail (larger than current tail)
- Insert in middle
- Bulk insert in ascending order
- Bulk insert in descending order
- Bulk insert in random order — `toArray()` is always sorted ascending
- **Stability:** insert three equal values interleaved with distinct values; verify insertion order among equals is preserved

**Remove**
- Remove head, middle, tail — verify `last()` returns correct value after each (validates tail pointer)
- Remove single element → list empties (verify via `isEmpty()` and `first()` throwing)
- Remove duplicate → only one occurrence removed; count decreases by 1
- Remove non-existent → returns `false`; list unchanged

**Contains**
- Found at head, middle, tail
- Not found: smaller than head, larger than tail, between existing values

**Iteration & exports**
- `foreach` yields values in ascending order
- `toArray()` returns a `list` (zero-indexed)
- `json_encode($list)` produces a valid JSON array

**Constructors & factories**
- Variadic `__construct()` with no args → empty list
- Variadic `__construct(...$mixedOrder)` → sorted
- `fromArray([])` → empty list
- `fromArray($assoc)` → keys ignored
- `fromArray([wrongType])` → `InvalidValueException` with offending index in message

**Cloning** (in `CloneTest`)
- Mutate clone → original unchanged
- Mutate original → clone unchanged
- Cloning an empty list works
- `count()` is correct after cloning

**Static analysis**
- `phpstan analyse` passes at level 9 with no baseline

---

## 8. Packaging

### `composer.json` (skeleton)

```json
{
    "name": "studio83/sorted-linked-list",
    "description": "Type-safe sorted linked list for int or string values.",
    "type": "library",
    "license": "MIT",
    "keywords": ["sorted", "linked-list", "collection", "data-structure"],
    "require": { "php": ">=8.2" },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.12",
        "friendsofphp/php-cs-fixer": "^3.64"
    },
    "autoload": { "psr-4": { "Studio83\\SortedLinkedList\\": "src/" } },
    "autoload-dev": { "psr-4": { "Studio83\\SortedLinkedList\\Tests\\": "tests/" } },
    "scripts": {
        "test": "phpunit",
        "analyse": "phpstan analyse",
        "fix": "php-cs-fixer fix",
        "check": ["@analyse", "@test"]
    },
    "config": { "sort-packages": true }
}
```

### Continuous Integration

`.github/workflows/ci.yml` — runs on `push` and `pull_request`, with a **PHP version matrix** to verify the declared `>=8.2` compatibility:

- Matrix: `php-version: [8.2, 8.3, 8.4]`
- Steps:
  1. `actions/checkout@v4`
  2. `shivammathur/setup-php@v2` with the matrix PHP version and `mbstring`, `json` extensions
  3. Composer dependency cache
  4. `composer install --no-progress --prefer-dist`
  5. `composer analyse` — PHPStan
  6. `composer test` — PHPUnit
  7. `php-cs-fixer fix --dry-run --diff` — code-style check (does not modify files)

Running the full check across three PHP versions makes the `>=8.2` claim in `composer.json` actually verified, not just declared.

### README outline

1. **Brief description** + 5-line usage example
2. **Installation** — `composer require studio83/sorted-linked-list`
3. **Usage** — separate examples for `IntSortedLinkedList` and `StringSortedLinkedList`
4. **API reference** — method table with complexity column
5. **Design decisions** — explicit non-goals (custom comparator, descending mode, indexed access, immutable API, unified interface) with one-sentence rationales each, plus the stable-duplicate ordering note
6. **Limitations** — byte-wise string comparison, O(n) insert/contains; pointers to alternatives (`intl Collator`, sorted arrays, balanced trees)
7. **Development** — `composer test`, `composer analyse`, `composer fix`
8. **License** — MIT

Section 5 (Design decisions) is the most important — that's where the reader sees engineering judgment, not just code.

---

## 9. Deliverables

```
✅ src/AbstractSortedLinkedList.php
✅ src/IntSortedLinkedList.php
✅ src/StringSortedLinkedList.php
✅ src/Internal/Node.php
✅ src/Exception/SortedLinkedListException.php
✅ src/Exception/EmptyListException.php
✅ src/Exception/InvalidValueException.php
✅ tests/AbstractSortedLinkedListTestCase.php
✅ tests/IntSortedLinkedListTest.php
✅ tests/StringSortedLinkedListTest.php
✅ tests/CloneTest.php
✅ composer.json
✅ phpunit.xml.dist
✅ phpstan.neon.dist
✅ .php-cs-fixer.dist.php
✅ .github/workflows/ci.yml
✅ README.md
✅ LICENSE (MIT)
✅ docs/specs/2026-05-07-sorted-linked-list-design.md (this document)
```
