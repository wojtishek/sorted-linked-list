# SortedLinkedList Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Composer-installable PHP library `studio83/sorted-linked-list` providing two type-safe sorted linked list classes (`IntSortedLinkedList`, `StringSortedLinkedList`) sharing an abstract parent, with PHPUnit tests, PHPStan level 9 static analysis, PHP-CS-Fixer code style, and GitHub Actions CI matrix across PHP 8.2/8.3/8.4.

**Architecture:** `AbstractSortedLinkedList` (abstract) holds shared state (`?Node $head`, `?Node $tail`, `int $count`) and shared algorithms in `protected` methods (`insertSorted`, `removeFirst`, `containsValue`). Two final concrete classes provide typed public API (`add(int)` / `add(string)`) by delegating to the protected parent methods. PHP's contravariance rules force this split. Tests use a parameterised abstract `AbstractSortedLinkedListTestCase` so every test method runs for both concrete classes.

**Tech Stack:** PHP `>= 8.2`, PHPUnit 11, PHPStan 1.x level 9, PHP-CS-Fixer 3.x with `@PSR12` + `@Symfony` rulesets, GitHub Actions.

**Source spec:** `docs/specs/2026-05-07-sorted-linked-list-design.md`

**Project root:** `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/`

**Conventions for every task in this plan:**
- All paths in steps are absolute (rooted at project root above)
- All `cd` is to project root unless stated otherwise
- All commits use Conventional Commits style (`feat:`, `chore:`, `test:`, `docs:`, `ci:`)
- Every code change is followed by `composer test` showing the relevant test suite passing before commit

---

## File Structure (locked at start)

```
src/
├── AbstractSortedLinkedList.php     - abstract: state + shared protected algorithms + final public Countable/IteratorAggregate/JsonSerializable methods + first/last/toArray/__clone
├── IntSortedLinkedList.php          - final: typed public add/remove/contains, narrowed first/last return types, fromArray, variadic ctor
├── StringSortedLinkedList.php       - final: same surface as IntSortedLinkedList, string-typed
├── Internal/
│   └── Node.php                     - @internal, readonly value, mutable next
└── Exception/
    ├── SortedLinkedListException.php - marker interface (extends \Throwable)
    ├── EmptyListException.php        - extends \LogicException, implements marker
    └── InvalidValueException.php     - extends \InvalidArgumentException, implements marker

tests/
├── AbstractSortedLinkedListTestCase.php - abstract: factory methods + every behavioural test method
├── IntSortedLinkedListTest.php          - extends AbstractSortedLinkedListTestCase with int factories
├── StringSortedLinkedListTest.php       - extends AbstractSortedLinkedListTestCase with string factories
└── CloneTest.php                         - cross-cutting: deep-copy independence

composer.json
phpunit.xml.dist
phpstan.neon.dist
.php-cs-fixer.dist.php
.gitignore
README.md
LICENSE
.github/workflows/ci.yml
```

---

## Task 1: Project Scaffolding

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `phpstan.neon.dist`
- Create: `.php-cs-fixer.dist.php`
- Create: `.gitignore`
- Init: git repository

- [ ] **Step 1: Initialize git in project root**

```bash
cd /Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList
git init -b main
```

Expected: `Initialized empty Git repository in …/Shipmonk-SortedLinkedList/.git/`

- [ ] **Step 2: Create `composer.json`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/composer.json`

```json
{
    "name": "studio83/sorted-linked-list",
    "description": "Type-safe sorted linked list for int or string values.",
    "type": "library",
    "license": "MIT",
    "keywords": ["sorted", "linked-list", "collection", "data-structure"],
    "authors": [
        {
            "name": "Vojtěch Kaizr",
            "email": "studio@studio83.cz"
        }
    ],
    "require": {
        "php": ">=8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.12",
        "friendsofphp/php-cs-fixer": "^3.64"
    },
    "autoload": {
        "psr-4": {
            "Studio83\\SortedLinkedList\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Studio83\\SortedLinkedList\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "analyse": "phpstan analyse",
        "fix": "php-cs-fixer fix",
        "check": [
            "@analyse",
            "@test"
        ]
    },
    "config": {
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 3: Create `.gitignore`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/.gitignore`

```
/vendor/
/composer.lock
/.phpunit.cache/
/.phpunit.result.cache
/.php-cs-fixer.cache
/.phpstan.cache/
/.idea/
/.vscode/
.DS_Store
```

Note: `composer.lock` is gitignored deliberately for libraries (libraries declare ranges; consumers lock).

- [ ] **Step 4: Create `phpunit.xml.dist`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/phpunit.xml.dist`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnWarning="true"
         failOnRisky="true"
         failOnNotice="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 5: Create `phpstan.neon.dist`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/phpstan.neon.dist`

```neon
parameters:
    level: 9
    paths:
        - src
        - tests
```

We deliberately keep PHPStan's default `treatPhpDocTypesAsCertain: true` so that `/** @var int $value */` narrowing works in concrete classes that override the parent's `int|string` return types with `int` / `string`.

- [ ] **Step 6: Create `.php-cs-fixer.dist.php`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/.php-cs-fixer.dist.php`

```php
<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
    ])
    ->setFinder($finder);
```

`global_namespace_import` is set conservatively — we want explicit `\Throwable`, `\Generator`, etc. in source for clarity.

- [ ] **Step 7: Install dependencies**

Run: `composer install --no-progress`
Expected: `vendor/` directory populated with phpunit, phpstan, php-cs-fixer and their deps. Exit code 0.

- [ ] **Step 8: Verify tooling works on empty project**

Run: `composer analyse`
Expected: `[OK] No errors` (or similar — passes because no source files exist yet)

Run: `composer test`
Expected: PHPUnit reports `No tests executed!` and exits with code 0 or 2 — that's fine; exit code may be non-zero but the run itself completed without crashing the runner.

If `composer test` fails with non-zero exit and warning treats it as failure, add a placeholder test in next task.

- [ ] **Step 9: Commit scaffolding**

```bash
cd /Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList
git add composer.json phpunit.xml.dist phpstan.neon.dist .php-cs-fixer.dist.php .gitignore docs/
git commit -m "chore: scaffold composer package with phpunit, phpstan, php-cs-fixer"
```

Expected: commit succeeds.

---

## Task 2: Exception Hierarchy

**Files:**
- Create: `src/Exception/SortedLinkedListException.php`
- Create: `src/Exception/EmptyListException.php`
- Create: `src/Exception/InvalidValueException.php`
- Test: `tests/ExceptionHierarchyTest.php`

- [ ] **Step 1: Write failing test for exception hierarchy**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/ExceptionHierarchyTest.php`

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: errors about `Class "Studio83\SortedLinkedList\Exception\SortedLinkedListException" not found`. Tests fail as expected.

- [ ] **Step 3: Create `SortedLinkedListException` interface**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/Exception/SortedLinkedListException.php`

```php
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
```

- [ ] **Step 4: Create `EmptyListException`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/Exception/EmptyListException.php`

```php
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
```

- [ ] **Step 5: Create `InvalidValueException`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/Exception/InvalidValueException.php`

```php
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
```

- [ ] **Step 6: Run tests to verify pass**

Run: `composer test`
Expected: `OK (4 tests, 6 assertions)` (count may differ; key thing is all green).

- [ ] **Step 7: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`

- [ ] **Step 8: Commit**

```bash
git add src/Exception/ tests/ExceptionHierarchyTest.php
git commit -m "feat: add exception hierarchy with marker interface"
```

---

## Task 3: Internal Node

**Files:**
- Create: `src/Internal/Node.php`
- Test: `tests/Internal/NodeTest.php`

- [ ] **Step 1: Write failing test for Node**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/Internal/NodeTest.php`

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: errors about `Class "Studio83\SortedLinkedList\Internal\Node" not found`.

- [ ] **Step 3: Create `Node`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/Internal/Node.php`

```php
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
```

- [ ] **Step 4: Run tests to verify pass**

Run: `composer test`
Expected: 8 tests passing total (4 from Task 2, 4 new). All green.

- [ ] **Step 5: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 6: Commit**

```bash
git add src/Internal/ tests/Internal/
git commit -m "feat: add internal Node value object"
```

---

## Task 4: Empty-List Behaviour

This task creates the abstract base, the first concrete class, and the abstract test base. Subsequent tasks add behaviour incrementally.

**Files:**
- Create: `src/AbstractSortedLinkedList.php` (with empty-list behaviour only)
- Create: `src/IntSortedLinkedList.php` (skeleton — extends parent, no add/remove yet)
- Create: `tests/AbstractSortedLinkedListTestCase.php` (with factory methods + empty-list tests)
- Create: `tests/IntSortedLinkedListTest.php`

- [ ] **Step 1: Write the abstract test base with empty-list assertions**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/AbstractSortedLinkedListTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\EmptyListException;

abstract class AbstractSortedLinkedListTestCase extends TestCase
{
    abstract protected function createEmpty(): AbstractSortedLinkedList;

    /**
     * Construct a list containing exactly the given values, in the order provided
     * (the list itself will sort them).
     *
     * @param int|string ...$values
     */
    abstract protected function fromValues(int|string ...$values): AbstractSortedLinkedList;

    /** @return list<int|string> 5 distinct values in ascending order */
    abstract protected function ascendingSample(): array;

    /** @return list<int|string> the same 5 values in non-ascending order */
    abstract protected function unsortedSample(): array;

    /** A value smaller than every value in ascendingSample() */
    abstract protected function valueSmallerThanAll(): int|string;

    /** A value larger than every value in ascendingSample() */
    abstract protected function valueLargerThanAll(): int|string;

    /** A value not present in ascendingSample(), strictly between min and max */
    abstract protected function valueInBetween(): int|string;

    public function testEmptyListCountIsZero(): void
    {
        self::assertSame(0, $this->createEmpty()->count());
    }

    public function testEmptyListIsEmpty(): void
    {
        self::assertTrue($this->createEmpty()->isEmpty());
    }

    public function testEmptyListIteratesZeroTimes(): void
    {
        $values = [];
        foreach ($this->createEmpty() as $value) {
            $values[] = $value;
        }
        self::assertSame([], $values);
    }

    public function testEmptyListToArrayReturnsEmptyArray(): void
    {
        self::assertSame([], $this->createEmpty()->toArray());
    }

    public function testFirstThrowsOnEmptyList(): void
    {
        $this->expectException(EmptyListException::class);
        $this->createEmpty()->first();
    }

    public function testLastThrowsOnEmptyList(): void
    {
        $this->expectException(EmptyListException::class);
        $this->createEmpty()->last();
    }

    public function testClearOnEmptyListIsNoop(): void
    {
        $list = $this->createEmpty();
        $list->clear();
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
    }
}
```

- [ ] **Step 2: Write the int-typed concrete test class**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/IntSortedLinkedListTest.php`

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\IntSortedLinkedList;

final class IntSortedLinkedListTest extends AbstractSortedLinkedListTestCase
{
    protected function createEmpty(): AbstractSortedLinkedList
    {
        return new IntSortedLinkedList();
    }

    protected function fromValues(int|string ...$values): AbstractSortedLinkedList
    {
        $ints = [];
        foreach ($values as $value) {
            self::assertIsInt($value);
            $ints[] = $value;
        }
        return new IntSortedLinkedList(...$ints);
    }

    protected function ascendingSample(): array
    {
        return [1, 2, 3, 4, 5];
    }

    protected function unsortedSample(): array
    {
        return [3, 1, 5, 2, 4];
    }

    protected function valueSmallerThanAll(): int|string
    {
        return 0;
    }

    protected function valueLargerThanAll(): int|string
    {
        return 99;
    }

    protected function valueInBetween(): int|string
    {
        return 25;
    }
}
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `composer test`
Expected: `Class "Studio83\SortedLinkedList\AbstractSortedLinkedList" not found` and similar for `IntSortedLinkedList`.

- [ ] **Step 4: Create `AbstractSortedLinkedList` with empty-list behaviour**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/AbstractSortedLinkedList.php`

```php
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
        return $this->count === 0;
    }

    final public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->count = 0;
    }

    /**
     * @return \Generator<int, int|string>
     */
    final public function getIterator(): \Generator
    {
        $current = $this->head;
        $i = 0;
        while ($current !== null) {
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
        while ($current !== null) {
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
        if ($this->head === null) {
            throw new EmptyListException('Cannot call first() on an empty list.');
        }
        return $this->head->value;
    }

    public function last(): int|string
    {
        if ($this->tail === null) {
            throw new EmptyListException('Cannot call last() on an empty list.');
        }
        return $this->tail->value;
    }
}
```

- [ ] **Step 5: Create `IntSortedLinkedList` skeleton**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/IntSortedLinkedList.php`

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList;

/**
 * Sorted singly-linked list of int values.
 *
 * @extends AbstractSortedLinkedList
 */
final class IntSortedLinkedList extends AbstractSortedLinkedList
{
    public function __construct(int ...$values)
    {
        // Insert logic added in Task 5.
        // For now the variadic just consumes the values without storing them;
        // tests in Task 4 only cover the empty-list case.
    }
}
```

- [ ] **Step 6: Run tests to verify pass**

Run: `composer test`
Expected: 7 new tests from `IntSortedLinkedListTest` all passing (`testEmptyListCountIsZero` … `testClearOnEmptyListIsNoop`), plus the 8 from earlier tasks. Total ≥ 15 tests, all green.

- [ ] **Step 7: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`

- [ ] **Step 8: Commit**

```bash
git add src/AbstractSortedLinkedList.php src/IntSortedLinkedList.php tests/AbstractSortedLinkedListTestCase.php tests/IntSortedLinkedListTest.php
git commit -m "feat: add abstract base and empty-list behaviour for sorted linked list"
```

---

## Task 5: Insert (Single Element + Head/Tail Maintenance)

**Files:**
- Modify: `src/AbstractSortedLinkedList.php` (add `insertSorted` method)
- Modify: `src/IntSortedLinkedList.php` (add `add()` method, wire variadic ctor)
- Modify: `tests/AbstractSortedLinkedListTestCase.php` (add insert tests)

- [ ] **Step 1: Write failing tests for single insert and head/tail maintenance**

Append to `tests/AbstractSortedLinkedListTestCase.php` (inside the class):

```php
    public function testAddSingleValueResultsInCountOne(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);

        self::assertSame(1, $list->count());
        self::assertFalse($list->isEmpty());
    }

    public function testAddSingleValueMakesItBothFirstAndLast(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);

        self::assertSame($sample[2], $list->first());
        self::assertSame($sample[2], $list->last());
    }

    public function testAddTwoAscendingValues(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[1]);
        $list->add($sample[3]);

        self::assertSame(2, $list->count());
        self::assertSame($sample[1], $list->first());
        self::assertSame($sample[3], $list->last());
        self::assertSame([$sample[1], $sample[3]], $list->toArray());
    }

    public function testAddTwoDescendingValues(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[3]);
        $list->add($sample[1]);

        self::assertSame(2, $list->count());
        self::assertSame($sample[1], $list->first());
        self::assertSame($sample[3], $list->last());
        self::assertSame([$sample[1], $sample[3]], $list->toArray());
    }

    public function testAddSmallerThanHead(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $smaller = $this->valueSmallerThanAll();
        $list->add($smaller);

        self::assertSame($smaller, $list->first());
        $expected = $this->ascendingSample();
        array_unshift($expected, $smaller);
        self::assertSame($expected, $list->toArray());
    }

    public function testAddLargerThanTail(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $larger = $this->valueLargerThanAll();
        $list->add($larger);

        self::assertSame($larger, $list->last());
        $expected = $this->ascendingSample();
        $expected[] = $larger;
        self::assertSame($expected, $list->toArray());
    }

    public function testAddInMiddle(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[4]);

        $list->add($sample[2]);

        self::assertSame([$sample[0], $sample[2], $sample[4]], $list->toArray());
        self::assertSame($sample[0], $list->first());
        self::assertSame($sample[4], $list->last());
    }

    public function testAddManyInRandomOrderProducesSortedResult(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        self::assertSame($this->ascendingSample(), $list->toArray());
        self::assertSame(count($this->ascendingSample()), $list->count());
    }

    public function testIterationYieldsValuesInAscendingOrder(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        $yielded = [];
        foreach ($list as $value) {
            $yielded[] = $value;
        }
        self::assertSame($this->ascendingSample(), $yielded);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test`
Expected: 9 new tests fail with `Error: Call to undefined method Studio83\SortedLinkedList\IntSortedLinkedList::add()`.

- [ ] **Step 3: Add `insertSorted` to `AbstractSortedLinkedList`**

Add the following method to `AbstractSortedLinkedList` (place it after `clear()` and before `getIterator()`):

```php
    /**
     * Insert a value at its sorted position.
     *
     * Stable: equal values are placed AFTER existing equals (insertion order
     * is preserved among equal elements).
     */
    final protected function insertSorted(int|string $value): void
    {
        $newNode = new Node($value);

        if ($this->head === null) {
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
        while ($current->next !== null && ($current->next->value <=> $value) <= 0) {
            $current = $current->next;
        }

        $newNode->next = $current->next;
        $current->next = $newNode;

        if ($newNode->next === null) {
            $this->tail = $newNode;
        }

        ++$this->count;
    }
```

- [ ] **Step 4: Wire the variadic constructor and add `add()` to `IntSortedLinkedList`**

Replace the body of `IntSortedLinkedList` with:

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList;

/**
 * Sorted singly-linked list of int values.
 */
final class IntSortedLinkedList extends AbstractSortedLinkedList
{
    public function __construct(int ...$values)
    {
        foreach ($values as $value) {
            $this->insertSorted($value);
        }
    }

    public function add(int $value): void
    {
        $this->insertSorted($value);
    }
}
```

- [ ] **Step 5: Run tests to verify pass**

Run: `composer test`
Expected: all previously-passing tests still green; the 9 new tests pass. Total ≥ 24 passing.

- [ ] **Step 6: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 7: Commit**

```bash
git add src/AbstractSortedLinkedList.php src/IntSortedLinkedList.php tests/AbstractSortedLinkedListTestCase.php
git commit -m "feat: add sorted insert with head/tail pointer maintenance"
```

---

## Task 6: Stable Insertion of Duplicates

**Files:**
- Modify: `tests/AbstractSortedLinkedListTestCase.php`

The algorithm in Task 5 already uses `<=` and is therefore stable. This task adds explicit verification.

- [ ] **Step 1: Write failing tests for stable duplicate ordering**

Append to `AbstractSortedLinkedListTestCase`:

```php
    public function testDuplicatesAreAllowedAndCounted(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);
        $list->add($sample[2]);
        $list->add($sample[2]);

        self::assertSame(3, $list->count());
        self::assertSame([$sample[2], $sample[2], $sample[2]], $list->toArray());
    }

    public function testDuplicateInsertedAfterExistingEqualValues(): void
    {
        // Use non-int/non-string distinguishability via strict identity is not possible
        // for primitive types — instead we verify ordering invariants relative to
        // surrounding distinct values: equals stay grouped, no surrounding ordering breaks.
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();

        // Build: sample[0], sample[2], sample[4]
        $list->add($sample[0]);
        $list->add($sample[2]);
        $list->add($sample[4]);

        // Insert another sample[2] — must land between the existing sample[2] and sample[4],
        // not before the existing sample[2].
        $list->add($sample[2]);

        self::assertSame(
            [$sample[0], $sample[2], $sample[2], $sample[4]],
            $list->toArray()
        );
    }

    public function testInsertingEqualToHeadDoesNotReplaceHead(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[2]);
        $list->add($sample[0]);

        // The new sample[0] should be inserted AFTER the existing one.
        // Verify by checking first() still returns the original head value
        // and the toArray order is correct.
        self::assertSame($sample[0], $list->first());
        self::assertSame([$sample[0], $sample[0], $sample[2]], $list->toArray());
        self::assertSame(3, $list->count());
    }

    public function testInsertingEqualToTailUpdatesTailPointer(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);
        $list->add($sample[4]);

        // Insert another value equal to the current tail.
        $list->add($sample[4]);

        self::assertSame($sample[4], $list->last());
        self::assertSame([$sample[0], $sample[4], $sample[4]], $list->toArray());
        self::assertSame(3, $list->count());
    }
```

- [ ] **Step 2: Run tests**

Run: `composer test`
Expected: all 4 new tests **pass** (the algorithm in Task 5 already implements stable insertion correctly). If any fail, the issue is in `insertSorted` and must be fixed before proceeding.

- [ ] **Step 3: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 4: Commit**

```bash
git add tests/AbstractSortedLinkedListTestCase.php
git commit -m "test: cover stable insertion of duplicates"
```

---

## Task 7: contains()

**Files:**
- Modify: `src/AbstractSortedLinkedList.php` (add `containsValue` protected method)
- Modify: `src/IntSortedLinkedList.php` (add public `contains`)
- Modify: `tests/AbstractSortedLinkedListTestCase.php`

- [ ] **Step 1: Write failing tests for `contains`**

Append to `AbstractSortedLinkedListTestCase`:

```php
    public function testContainsReturnsTrueForExistingValue(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        foreach ($this->ascendingSample() as $value) {
            self::assertTrue($list->contains($value), "expected list to contain $value");
        }
    }

    public function testContainsReturnsFalseForMissingValueSmallerThanAll(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueSmallerThanAll()));
    }

    public function testContainsReturnsFalseForMissingValueLargerThanAll(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueLargerThanAll()));
    }

    public function testContainsReturnsFalseForMissingValueInBetween(): void
    {
        // ascendingSample omits valueInBetween() (e.g. 25 missing from [1,2,3,4,5])
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->contains($this->valueInBetween()));
    }

    public function testContainsReturnsFalseOnEmptyList(): void
    {
        self::assertFalse($this->createEmpty()->contains($this->ascendingSample()[0]));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test`
Expected: 5 new tests fail with `Call to undefined method Studio83\SortedLinkedList\IntSortedLinkedList::contains()`.

- [ ] **Step 3: Add `containsValue` to `AbstractSortedLinkedList`**

Insert after `insertSorted` in `AbstractSortedLinkedList`:

```php
    /**
     * Check whether the list contains a value (uses === for equality after
     * locating the candidate by sort order; benefits from early exit).
     */
    final protected function containsValue(int|string $value): bool
    {
        $current = $this->head;
        while ($current !== null && ($current->value <=> $value) < 0) {
            $current = $current->next;
        }
        return $current !== null && $current->value === $value;
    }
```

- [ ] **Step 4: Add `contains` to `IntSortedLinkedList`**

Add after `add()` in `IntSortedLinkedList`:

```php
    public function contains(int $value): bool
    {
        return $this->containsValue($value);
    }
```

- [ ] **Step 5: Run tests to verify pass**

Run: `composer test`
Expected: 5 new tests pass.

- [ ] **Step 6: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 7: Commit**

```bash
git add src/AbstractSortedLinkedList.php src/IntSortedLinkedList.php tests/AbstractSortedLinkedListTestCase.php
git commit -m "feat: add contains() with early-exit search"
```

---

## Task 8: remove()

**Files:**
- Modify: `src/AbstractSortedLinkedList.php` (add `removeFirst`)
- Modify: `src/IntSortedLinkedList.php` (add public `remove`)
- Modify: `tests/AbstractSortedLinkedListTestCase.php`

- [ ] **Step 1: Write failing tests for `remove`**

Append to `AbstractSortedLinkedListTestCase`:

```php
    public function testRemoveOnEmptyListReturnsFalse(): void
    {
        $list = $this->createEmpty();
        self::assertFalse($list->remove($this->ascendingSample()[0]));
        self::assertSame(0, $list->count());
    }

    public function testRemoveNonExistentValueReturnsFalse(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        self::assertFalse($list->remove($this->valueLargerThanAll()));
        self::assertFalse($list->remove($this->valueSmallerThanAll()));
        self::assertFalse($list->remove($this->valueInBetween()));
        self::assertSame($this->ascendingSample(), $list->toArray());
    }

    public function testRemoveSingleElementEmptiesTheList(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[0]);

        self::assertTrue($list->remove($sample[0]));
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());

        // first()/last() must throw again after the list returns to empty state.
        $exceptionsThrown = 0;
        try {
            $list->first();
        } catch (\Studio83\SortedLinkedList\Exception\EmptyListException) {
            ++$exceptionsThrown;
        }
        try {
            $list->last();
        } catch (\Studio83\SortedLinkedList\Exception\EmptyListException) {
            ++$exceptionsThrown;
        }
        self::assertSame(2, $exceptionsThrown);
    }

    public function testRemoveHead(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();

        self::assertTrue($list->remove($sample[0]));
        self::assertSame($sample[1], $list->first());
        self::assertSame(array_slice($sample, 1), $list->toArray());
        self::assertSame(count($sample) - 1, $list->count());
    }

    public function testRemoveTailUpdatesTailPointer(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();

        self::assertTrue($list->remove($sample[count($sample) - 1]));
        self::assertSame($sample[count($sample) - 2], $list->last());
        self::assertSame(array_slice($sample, 0, count($sample) - 1), $list->toArray());
    }

    public function testRemoveMiddle(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }
        $sample = $this->ascendingSample();
        $middle = $sample[(int) (count($sample) / 2)];

        self::assertTrue($list->remove($middle));
        self::assertNotContains($middle, $list->toArray());
        self::assertSame(count($sample) - 1, $list->count());
    }

    public function testRemoveDuplicateRemovesOnlyOneOccurrence(): void
    {
        $list = $this->createEmpty();
        $sample = $this->ascendingSample();
        $list->add($sample[2]);
        $list->add($sample[2]);
        $list->add($sample[2]);

        self::assertTrue($list->remove($sample[2]));
        self::assertSame(2, $list->count());
        self::assertSame([$sample[2], $sample[2]], $list->toArray());

        self::assertTrue($list->remove($sample[2]));
        self::assertTrue($list->remove($sample[2]));
        self::assertFalse($list->remove($sample[2]));
        self::assertTrue($list->isEmpty());
    }

    public function testClearOnNonEmptyList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $list->clear();
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
        self::assertSame([], $list->toArray());
    }
```

- [ ] **Step 2: Run tests to verify failure**

Run: `composer test`
Expected: 7 new tests (testClearOnNonEmptyList passes immediately) fail with `Call to undefined method ...::remove()`.

- [ ] **Step 3: Add `removeFirst` to `AbstractSortedLinkedList`**

Insert after `containsValue`:

```php
    /**
     * Remove the first occurrence of $value, if present.
     *
     * Returns true when something was removed, false when the value is not present.
     * Maintains tail pointer correctly when the removed node was the tail.
     */
    final protected function removeFirst(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }

        if ($this->head->value === $value) {
            $this->head = $this->head->next;
            if ($this->head === null) {
                $this->tail = null;
            }
            --$this->count;
            return true;
        }

        $prev = $this->head;
        while ($prev->next !== null && ($prev->next->value <=> $value) < 0) {
            $prev = $prev->next;
        }

        if ($prev->next === null || $prev->next->value !== $value) {
            return false;
        }

        $removed = $prev->next;
        $prev->next = $removed->next;
        if ($removed === $this->tail) {
            $this->tail = $prev;
        }
        --$this->count;
        return true;
    }
```

- [ ] **Step 4: Add `remove` to `IntSortedLinkedList`**

Append after `contains`:

```php
    public function remove(int $value): bool
    {
        return $this->removeFirst($value);
    }
```

- [ ] **Step 5: Run tests to verify pass**

Run: `composer test`
Expected: all new tests pass.

- [ ] **Step 6: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 7: Commit**

```bash
git add src/AbstractSortedLinkedList.php src/IntSortedLinkedList.php tests/AbstractSortedLinkedListTestCase.php
git commit -m "feat: add remove() with tail pointer maintenance"
```

---

## Task 9: jsonSerialize Coverage and Iteration Keys

**Files:**
- Modify: `tests/AbstractSortedLinkedListTestCase.php`

The implementation already exists — this task adds explicit test coverage for behaviours that have only been incidentally tested.

- [ ] **Step 1: Add tests for `jsonSerialize`, iteration keys, and `toArray` indexing**

Append to `AbstractSortedLinkedListTestCase`:

```php
    public function testJsonSerializeProducesArray(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }
        self::assertSame($this->ascendingSample(), $list->jsonSerialize());
    }

    public function testJsonEncodeProducesArrayNotObjectForEmptyList(): void
    {
        $encoded = json_encode($this->createEmpty());
        self::assertSame('[]', $encoded);
    }

    public function testJsonEncodeProducesArrayForPopulatedList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $encoded = json_encode($list);
        self::assertSame(json_encode($this->ascendingSample()), $encoded);
    }

    public function testToArrayReturnsZeroIndexedList(): void
    {
        $list = $this->createEmpty();
        foreach ($this->unsortedSample() as $value) {
            $list->add($value);
        }

        $array = $list->toArray();
        self::assertSame(array_values($array), $array);
        self::assertSame(0, array_key_first($array));
        self::assertSame(count($array) - 1, array_key_last($array));
    }

    public function testIterationYieldsZeroIndexedKeys(): void
    {
        $list = $this->createEmpty();
        foreach ($this->ascendingSample() as $value) {
            $list->add($value);
        }

        $expectedIndex = 0;
        foreach ($list as $key => $value) {
            self::assertSame($expectedIndex, $key);
            ++$expectedIndex;
        }
    }
```

- [ ] **Step 2: Run tests**

Run: `composer test`
Expected: 5 new tests pass.

- [ ] **Step 3: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 4: Commit**

```bash
git add tests/AbstractSortedLinkedListTestCase.php
git commit -m "test: cover jsonSerialize, iteration keys, and toArray indexing"
```

---

## Task 10: fromArray Factory with Validation

**Files:**
- Modify: `src/IntSortedLinkedList.php`
- Modify: `tests/AbstractSortedLinkedListTestCase.php`
- Modify: `tests/IntSortedLinkedListTest.php`

The factory needs different per-type validation logic, so most of the test coverage lives on each concrete test class. The abstract base only validates the shape that is identical across types.

- [ ] **Step 1: Add cross-type tests for `fromArray` to the abstract base**

Append to `AbstractSortedLinkedListTestCase`:

```php
    public function testFromArrayWithEmptyArrayProducesEmptyList(): void
    {
        $list = static::fromArrayHelper($this, []);
        self::assertTrue($list->isEmpty());
        self::assertSame(0, $list->count());
    }

    public function testFromArraySortsValues(): void
    {
        $list = static::fromArrayHelper($this, $this->unsortedSample());
        self::assertSame($this->ascendingSample(), $list->toArray());
    }

    public function testFromArrayIgnoresStringKeys(): void
    {
        $sample = $this->ascendingSample();
        $assoc = ['a' => $sample[2], 'b' => $sample[0], 'c' => $sample[4]];

        $list = static::fromArrayHelper($this, $assoc);
        self::assertSame([$sample[0], $sample[2], $sample[4]], $list->toArray());
    }

    public function testFromArrayAllowsDuplicates(): void
    {
        $sample = $this->ascendingSample();
        $list = static::fromArrayHelper($this, [$sample[1], $sample[1], $sample[3]]);
        self::assertSame([$sample[1], $sample[1], $sample[3]], $list->toArray());
        self::assertSame(3, $list->count());
    }

    /**
     * Concrete test classes route through this so abstract tests can build
     * a list via fromArray without knowing the concrete class name.
     *
     * @param array<array-key, mixed> $values
     */
    abstract protected static function fromArrayHelper(self $testCase, array $values): AbstractSortedLinkedList;
```

Note: `fromArrayHelper` is declared abstract here so the abstract class's tests can call into the concrete class's static `fromArray`. PHP doesn't allow calling a static method on a generic supertype without help.

- [ ] **Step 2: Implement `fromArrayHelper` in `IntSortedLinkedListTest`**

Append to `IntSortedLinkedListTest`:

```php
    /**
     * @param array<array-key, mixed> $values
     */
    protected static function fromArrayHelper(AbstractSortedLinkedListTestCase $testCase, array $values): AbstractSortedLinkedList
    {
        return IntSortedLinkedList::fromArray($values);
    }

    public function testFromArrayWithIntValues(): void
    {
        $list = IntSortedLinkedList::fromArray([3, 1, 2]);
        self::assertSame([1, 2, 3], $list->toArray());
    }

    public function testFromArrayWithNonIntValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/index 1/');
        IntSortedLinkedList::fromArray([1, 'two', 3]);
    }

    public function testFromArrayWithFloatValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, 2.5, 3]);
    }

    public function testFromArrayWithBoolValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, true, 3]);
    }

    public function testFromArrayWithNullValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        IntSortedLinkedList::fromArray([1, null, 3]);
    }
```

The new test methods need an import. Update the `use` statements at the top of `IntSortedLinkedListTest.php`:

```php
use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\InvalidValueException;
use Studio83\SortedLinkedList\IntSortedLinkedList;
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `composer test`
Expected: failures about undefined `IntSortedLinkedList::fromArray`.

- [ ] **Step 4: Implement `fromArray` in `IntSortedLinkedList`**

Append to `IntSortedLinkedList` (after `remove()`):

```php
    /**
     * Build a list from an array of int values.
     *
     * Keys are ignored; only values are inserted (in iteration order before sorting).
     *
     * @param array<array-key, mixed> $values
     *
     * @throws \Studio83\SortedLinkedList\Exception\InvalidValueException when any value is not an int
     */
    public static function fromArray(array $values): self
    {
        $list = new self();
        $index = 0;
        foreach ($values as $value) {
            if (!\is_int($value)) {
                throw new \Studio83\SortedLinkedList\Exception\InvalidValueException(
                    sprintf(
                        'Expected int at index %d, got %s.',
                        $index,
                        get_debug_type($value),
                    )
                );
            }
            $list->insertSorted($value);
            ++$index;
        }
        return $list;
    }
```

- [ ] **Step 5: Run tests to verify pass**

Run: `composer test`
Expected: all new tests pass; previously-passing tests still green.

- [ ] **Step 6: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 7: Commit**

```bash
git add src/IntSortedLinkedList.php tests/AbstractSortedLinkedListTestCase.php tests/IntSortedLinkedListTest.php
git commit -m "feat: add fromArray factory with type validation"
```

---

## Task 11: Deep-Copy `__clone`

**Files:**
- Modify: `src/AbstractSortedLinkedList.php`
- Create: `tests/CloneTest.php`

- [ ] **Step 1: Write failing test for clone independence**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/CloneTest.php`

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use PHPUnit\Framework\TestCase;
use Studio83\SortedLinkedList\IntSortedLinkedList;

final class CloneTest extends TestCase
{
    public function testCloneOfEmptyListIsIndependentEmptyList(): void
    {
        $original = new IntSortedLinkedList();
        $copy = clone $original;

        $copy->add(1);
        self::assertSame(0, $original->count());
        self::assertSame(1, $copy->count());
    }

    public function testMutatingCloneDoesNotAffectOriginal(): void
    {
        $original = new IntSortedLinkedList(2, 4, 6);
        $copy = clone $original;

        $copy->add(5);

        self::assertSame([2, 4, 6], $original->toArray());
        self::assertSame([2, 4, 5, 6], $copy->toArray());
    }

    public function testMutatingOriginalDoesNotAffectClone(): void
    {
        $original = new IntSortedLinkedList(2, 4, 6);
        $copy = clone $original;

        $original->add(5);

        self::assertSame([2, 4, 5, 6], $original->toArray());
        self::assertSame([2, 4, 6], $copy->toArray());
    }

    public function testRemovingFromOriginalDoesNotAffectClone(): void
    {
        $original = new IntSortedLinkedList(1, 2, 3);
        $copy = clone $original;

        $original->remove(2);

        self::assertSame([1, 3], $original->toArray());
        self::assertSame([1, 2, 3], $copy->toArray());
    }

    public function testCloneCountMatchesOriginal(): void
    {
        $original = new IntSortedLinkedList(1, 2, 3, 4, 5);
        $copy = clone $original;

        self::assertSame(5, $copy->count());
    }

    public function testCloneTailIsIndependent(): void
    {
        // After cloning then appending to the original, the clone's tail must not move.
        $original = new IntSortedLinkedList(1, 2, 3);
        $copy = clone $original;

        $original->add(99);

        self::assertSame(3, $copy->last());
        self::assertSame(99, $original->last());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test`
Expected: at least `testMutatingCloneDoesNotAffectOriginal` and similar fail because PHP's default shallow `clone` shares Node references — adding a node to the copy will mutate `next` pointers visible from the original.

- [ ] **Step 3: Add `__clone` to `AbstractSortedLinkedList`**

Insert after `last()`:

```php
    /**
     * Deep-copy the node chain so a cloned list is fully independent
     * of its source. Without this, PHP's default shallow clone would
     * share Node instances — mutating either list would corrupt the other
     * because list operations re-link `Node::$next`.
     */
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
        // $count is an int — already copied by value by PHP.
    }
```

- [ ] **Step 4: Run tests to verify pass**

Run: `composer test`
Expected: all CloneTest tests pass.

- [ ] **Step 5: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 6: Commit**

```bash
git add src/AbstractSortedLinkedList.php tests/CloneTest.php
git commit -m "feat: deep-copy node chain on clone"
```

---

## Task 12: Narrow Return Types on Concrete Class

**Files:**
- Modify: `src/IntSortedLinkedList.php`
- Modify: `tests/IntSortedLinkedListTest.php`

The abstract base returns `int|string` from `first()`/`last()`. Concrete classes should narrow the return types so callers and static analysis benefit from precise typing.

- [ ] **Step 1: Add tests verifying first()/last() return precise int types**

Append to `IntSortedLinkedListTest`:

```php
    public function testFirstReturnsIntType(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $first = $list->first();
        self::assertIsInt($first);
        self::assertSame(1, $first);
    }

    public function testLastReturnsIntType(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $last = $list->last();
        self::assertIsInt($last);
        self::assertSame(3, $last);
    }

    public function testToArrayReturnsListOfInts(): void
    {
        $list = new IntSortedLinkedList(3, 1, 2);
        $array = $list->toArray();
        self::assertContainsOnly('int', $array);
    }
```

- [ ] **Step 2: Run tests**

Run: `composer test`
Expected: pass (the abstract methods already return correct values; this just documents the intent).

- [ ] **Step 3: Add narrowed return types in `IntSortedLinkedList`**

Append to `IntSortedLinkedList`:

```php
    public function first(): int
    {
        /** @var int $value PHP's int|string return is narrowed by the int-only public API. */
        $value = parent::first();
        return $value;
    }

    public function last(): int
    {
        /** @var int $value */
        $value = parent::last();
        return $value;
    }

    /**
     * @return list<int>
     */
    public function toArray(): array
    {
        /** @var list<int> $array */
        $array = parent::toArray();
        return $array;
    }
```

- [ ] **Step 4: Run tests + static analysis**

Run: `composer test`
Expected: all green.

Run: `composer analyse`
Expected: `[OK] No errors`. PHPStan should now type `(new IntSortedLinkedList(...))->first()` as `int`, not `int|string`.

- [ ] **Step 5: Commit**

```bash
git add src/IntSortedLinkedList.php tests/IntSortedLinkedListTest.php
git commit -m "feat: narrow return types on IntSortedLinkedList concrete API"
```

---

## Task 13: StringSortedLinkedList

**Files:**
- Create: `src/StringSortedLinkedList.php`
- Create: `tests/StringSortedLinkedListTest.php`

- [ ] **Step 1: Create the concrete test class**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/tests/StringSortedLinkedListTest.php`

```php
<?php

declare(strict_types=1);

namespace Studio83\SortedLinkedList\Tests;

use Studio83\SortedLinkedList\AbstractSortedLinkedList;
use Studio83\SortedLinkedList\Exception\InvalidValueException;
use Studio83\SortedLinkedList\StringSortedLinkedList;

final class StringSortedLinkedListTest extends AbstractSortedLinkedListTestCase
{
    protected function createEmpty(): AbstractSortedLinkedList
    {
        return new StringSortedLinkedList();
    }

    protected function fromValues(int|string ...$values): AbstractSortedLinkedList
    {
        $strings = [];
        foreach ($values as $value) {
            self::assertIsString($value);
            $strings[] = $value;
        }
        return new StringSortedLinkedList(...$strings);
    }

    /**
     * @param array<array-key, mixed> $values
     */
    protected static function fromArrayHelper(AbstractSortedLinkedListTestCase $testCase, array $values): AbstractSortedLinkedList
    {
        return StringSortedLinkedList::fromArray($values);
    }

    protected function ascendingSample(): array
    {
        return ['apple', 'banana', 'cherry', 'date', 'elderberry'];
    }

    protected function unsortedSample(): array
    {
        return ['cherry', 'apple', 'elderberry', 'banana', 'date'];
    }

    protected function valueSmallerThanAll(): int|string
    {
        return 'aardvark';
    }

    protected function valueLargerThanAll(): int|string
    {
        return 'zebra';
    }

    protected function valueInBetween(): int|string
    {
        return 'cucumber';
    }

    public function testFromArrayWithStringValues(): void
    {
        $list = StringSortedLinkedList::fromArray(['c', 'a', 'b']);
        self::assertSame(['a', 'b', 'c'], $list->toArray());
    }

    public function testFromArrayWithNonStringValueThrows(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessageMatches('/index 1/');
        StringSortedLinkedList::fromArray(['x', 42, 'y']);
    }

    public function testFirstReturnsStringType(): void
    {
        $list = new StringSortedLinkedList('c', 'a', 'b');
        $first = $list->first();
        self::assertIsString($first);
        self::assertSame('a', $first);
    }

    public function testLastReturnsStringType(): void
    {
        $list = new StringSortedLinkedList('c', 'a', 'b');
        $last = $list->last();
        self::assertIsString($last);
        self::assertSame('c', $last);
    }

    public function testByteWiseComparisonOrder(): void
    {
        // ASCII byte order: uppercase before lowercase, digits before letters.
        $list = new StringSortedLinkedList('apple', 'Banana', '1apple', 'aardvark');
        self::assertSame(['1apple', 'Banana', 'aardvark', 'apple'], $list->toArray());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `composer test`
Expected: errors about `Class "Studio83\SortedLinkedList\StringSortedLinkedList" not found`.

- [ ] **Step 3: Create `StringSortedLinkedList`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/src/StringSortedLinkedList.php`

```php
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
                throw new InvalidValueException(
                    sprintf(
                        'Expected string at index %d, got %s.',
                        $index,
                        get_debug_type($value),
                    )
                );
            }
            $list->insertSorted($value);
            ++$index;
        }
        return $list;
    }
}
```

- [ ] **Step 4: Run tests to verify all pass**

Run: `composer test`
Expected: every test method in `AbstractSortedLinkedListTestCase` now runs **twice** — once for int, once for string — plus the type-specific tests on each concrete test class. Total ~70 tests, all green.

- [ ] **Step 5: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`.

- [ ] **Step 6: Run code-style fixer**

Run: `composer fix -- --dry-run --diff`
Expected: minimal or no changes proposed. If changes are proposed, run `composer fix` to apply them.

- [ ] **Step 7: Commit**

```bash
git add src/StringSortedLinkedList.php tests/StringSortedLinkedListTest.php
git commit -m "feat: add StringSortedLinkedList with byte-wise ordering"
```

---

## Task 14: Native TypeError Coverage on Wrong-Typed Direct Calls

**Files:**
- Modify: `tests/IntSortedLinkedListTest.php`
- Modify: `tests/StringSortedLinkedListTest.php`

PHP's parameter type system rejects wrong-typed values at the boundary; this task documents that contract with explicit tests so it's deliberate and visible.

- [ ] **Step 1: Add native TypeError tests to `IntSortedLinkedListTest`**

Append:

```php
    public function testAddRejectsStringWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new IntSortedLinkedList();
        /** @phpstan-ignore-next-line - intentionally violating the type for the test */
        $list->add('not an int');
    }

    public function testAddRejectsFloatWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new IntSortedLinkedList();
        /** @phpstan-ignore-next-line - intentionally violating the type for the test */
        $list->add(1.5);
    }

    public function testRemoveRejectsStringWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new IntSortedLinkedList(1, 2, 3);
        /** @phpstan-ignore-next-line */
        $list->remove('1');
    }

    public function testContainsRejectsStringWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new IntSortedLinkedList(1, 2, 3);
        /** @phpstan-ignore-next-line */
        $list->contains('1');
    }
```

- [ ] **Step 2: Add native TypeError tests to `StringSortedLinkedListTest`**

Append:

```php
    public function testAddRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList();
        /** @phpstan-ignore-next-line */
        $list->add(42);
    }

    public function testRemoveRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList('a', 'b');
        /** @phpstan-ignore-next-line */
        $list->remove(0);
    }

    public function testContainsRejectsIntWithTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $list = new StringSortedLinkedList('a', 'b');
        /** @phpstan-ignore-next-line */
        $list->contains(0);
    }
```

- [ ] **Step 3: Run tests**

Run: `composer test`
Expected: all 7 new tests pass.

- [ ] **Step 4: Run static analysis**

Run: `composer analyse`
Expected: `[OK] No errors` (the `@phpstan-ignore-next-line` tags suppress the deliberate violations).

- [ ] **Step 5: Commit**

```bash
git add tests/IntSortedLinkedListTest.php tests/StringSortedLinkedListTest.php
git commit -m "test: cover native TypeError contract on add/remove/contains"
```

---

## Task 15: README

**Files:**
- Create: `README.md`

- [ ] **Step 1: Write `README.md`**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/README.md`

```markdown
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

```php
use Studio83\SortedLinkedList\Exception\EmptyListException;

$list = new IntSortedLinkedList();
try {
    $list->first();
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
```

- [ ] **Step 2: Verify markdown is well-formed**

Run: `cat README.md | head -20`
Expected: title and first usage block visible, no formatting glitches.

- [ ] **Step 3: Commit**

```bash
git add README.md
git commit -m "docs: add README with usage, API table, design decisions, and limitations"
```

---

## Task 16: LICENSE

**Files:**
- Create: `LICENSE`

- [ ] **Step 1: Create the MIT LICENSE file**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/LICENSE`

```
MIT License

Copyright (c) 2026 Vojtěch Kaizr / Studio 83

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

- [ ] **Step 2: Commit**

```bash
git add LICENSE
git commit -m "docs: add MIT license"
```

---

## Task 17: GitHub Actions CI

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Create the workflow file**

Path: `/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList/.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  ci:
    name: PHP ${{ matrix.php-version }}
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        php-version: ['8.2', '8.3', '8.4']

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: mbstring, json
          coverage: none
          tools: composer:v2

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-php${{ matrix.php-version }}-composer-${{ hashFiles('composer.json') }}
          restore-keys: ${{ runner.os }}-php${{ matrix.php-version }}-composer-

      - name: Install dependencies
        run: composer install --no-progress --prefer-dist

      - name: Static analysis (PHPStan)
        run: composer analyse

      - name: Tests (PHPUnit)
        run: composer test

      - name: Code-style check (PHP-CS-Fixer)
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add GitHub Actions matrix across PHP 8.2/8.3/8.4"
```

---

## Task 18: Final Verification

**Files:** none — this is a verification-only task.

- [ ] **Step 1: Full test run**

Run: `composer test`
Expected: ~80+ tests passing, 0 failures, 0 errors, 0 risky, 0 warnings.

- [ ] **Step 2: Static analysis**

Run: `composer analyse`
Expected: `[OK] No errors`. Level 9 with no baseline file.

- [ ] **Step 3: Code-style verification**

Run: `composer fix -- --dry-run --diff`
Expected: no changes proposed (exit code 0).

If changes are proposed, run `composer fix` and commit:

```bash
git add -A
git commit -m "style: apply php-cs-fixer rules"
```

- [ ] **Step 4: Verify package installability locally (optional smoke test)**

```bash
cd /tmp
mkdir sortedlist-smoketest && cd sortedlist-smoketest
composer init --no-interaction --name=smoketest/x --require=studio83/sorted-linked-list:@dev --repository='{"type":"path","url":"/Users/vojtechkaizr/playground/Shipmonk-SortedLinkedList","options":{"symlink":false}}'
composer install --no-progress
php -r 'require "vendor/autoload.php"; $l = new \Studio83\SortedLinkedList\IntSortedLinkedList(3,1,2); echo implode(",", $l->toArray()), PHP_EOL;'
```

Expected output: `1,2,3`

Clean up: `rm -rf /tmp/sortedlist-smoketest`

- [ ] **Step 5: Verify git history is clean**

Run: `git log --oneline`
Expected: ~17 commits with conventional-commit prefixes (`chore:`, `feat:`, `test:`, `docs:`, `ci:`).

- [ ] **Step 6: Final summary**

The library is ready for submission. Deliverables checklist (matches spec §9):

- [ ] `src/AbstractSortedLinkedList.php`
- [ ] `src/IntSortedLinkedList.php`
- [ ] `src/StringSortedLinkedList.php`
- [ ] `src/Internal/Node.php`
- [ ] `src/Exception/SortedLinkedListException.php`
- [ ] `src/Exception/EmptyListException.php`
- [ ] `src/Exception/InvalidValueException.php`
- [ ] `tests/AbstractSortedLinkedListTestCase.php`
- [ ] `tests/IntSortedLinkedListTest.php`
- [ ] `tests/StringSortedLinkedListTest.php`
- [ ] `tests/CloneTest.php`
- [ ] `tests/ExceptionHierarchyTest.php`
- [ ] `tests/Internal/NodeTest.php`
- [ ] `composer.json`
- [ ] `phpunit.xml.dist`
- [ ] `phpstan.neon.dist`
- [ ] `.php-cs-fixer.dist.php`
- [ ] `.github/workflows/ci.yml`
- [ ] `README.md`
- [ ] `LICENSE`
- [ ] `docs/specs/2026-05-07-sorted-linked-list-design.md`
- [ ] `docs/plans/2026-05-07-sorted-linked-list-implementation.md` (this file)
