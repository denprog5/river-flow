# Pipes Module Reference

Namespace: `Denprog\RiverFlow\Pipes`

General rules
- Lazy vs eager: functions returning `Generator` are lazy; those returning arrays/scalars are eager
- Key behavior: explicitly stated per function; many lazy transforms preserve keys, some discard

Dual-mode usage
- Every function supports both direct and curried usage. In pipelines (PHP 8.5 `|>`), call the function without the data argument to get a callable, then chain.

## Transform
- `filter(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>`
  - Lazy; preserves keys; yields items where predicate is true
- `reject(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>`
  - Lazy; preserves keys; opposite of filter
- `map(iterable $data, callable(TValue, TKey): TNewValue $transformer): Generator<TKey, TNewValue>`
  - Lazy; preserves keys
- `pluck(iterable $data, string|int $key, mixed $default = null): Generator<TKey, mixed>`
  - Lazy; preserves original keys; for arrays reads by key; for objects reads public property by name; yields $default when missing

## Aggregation / Terminal
- `reduce(iterable $data, callable(TCarry|null, TValue, TKey): TCarry $reducer, TCarry|null $initial = null): TCarry|null`
- `sum(iterable<int|float|string|bool|null> $data): int|float`
  - true => 1; false/null => 0; numeric strings converted; others ignored
- `average(iterable<int|float|string|bool|null> $data): float`
  - Eager; 0.0 for empty; generator-safe (single pass). Numeric handling like `sum`. The denominator is the total number of elements (including non-numeric and null elements, which contribute 0 to the sum).
- `first(iterable $data, mixed $default = null): mixed`
- `last(iterable $data, mixed $default = null): mixed`
- `find(iterable $data, callable(T, TKey): bool $predicate, mixed $default = null): mixed`
- `min(iterable<int|float|string> $data): int|float|string|null`
- `max(iterable<int|float|string> $data): int|float|string|null`
- `count(iterable $data): int`
- `isEmpty(iterable $data): bool`
- `contains(iterable $data, mixed $needle): bool` (strict comparison)
- `every(iterable $data, callable(TValue, TKey): bool $predicate): bool` — true if all elements satisfy the predicate; true for empty iterables
- `some(iterable $data, callable(TValue, TKey): bool $predicate): bool` — true if any element satisfies the predicate; false for empty iterables

## Conversions
- `toList(iterable $data): array<int, mixed>` — eager; discards keys
- `toArray(iterable $data): array<array-key, mixed>` — eager; preserves keys
- `values(iterable $data): Generator<int, mixed>` — lazy; discards keys
- `keys(iterable $data): Generator<int, array-key>` — lazy; yields keys

## Reshaping / Ordering
- `groupBy(iterable $data, callable(TValue, TKey): array-key $grouper): array<array-key, array<TKey, TValue>>`
  - Eager; preserves original keys inside each group
- `keyBy(iterable $data, callable(TValue, TKey): array-key $keySelector): array<array-key, TValue>`
  - Eager; later keys overwrite earlier ones
- `sortBy(iterable $data, callable(TValue, TKey): int|float|string $getComparable): array<TKey, TValue>`
  - Eager; stable key preservation
- `sort(iterable<int|float|string> $data): array<array-key, int|float|string>`
  - Eager; natural ascending order; keys preserved

## Uniqueness
- `uniq(iterable $data): Generator<TKey, TValue>`
  - Lazy; first-occurrence keys preserved; objects by identity; arrays via serialize(); unhashable items (e.g., arrays with closures) are skipped
- `uniqBy(iterable $data, callable(TValue, TKey): mixed $identifier): Generator<TKey, TValue>`
  - Lazy; hashing rules like `uniq`

## Combining / Windowing
- `zip(iterable $data, iterable ...$others): Generator<int, array<int, mixed>>`
  - Lazy; stops at shortest; keys discarded; yields numeric-indexed tuples
- `zipWith(iterable ...$others): callable(iterable $data): Generator<int, array<int, mixed>>`
  - Pipe-friendly curried form of `zip`. Use in pipelines: `[1,2,3] |> zipWith(['a','b'])`
- `chunk(iterable $data, int $size): Generator<int, array<int, mixed>>`
  - Lazy; keys discarded; last chunk may be smaller; throws if size <= 0
- `partition(iterable $data, callable(TValue, TKey): bool $predicate): array{0: array<TKey, TValue>, 1: array<TKey, TValue>}`
  - Eager; keys preserved; returns [pass, fail]

## Control flow
- `take(iterable $data, int $count): Generator<TKey, TValue>` — lazy; preserves keys; yields up to $count
- `takeWhile(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>` — lazy; preserves keys
- `drop(iterable $data, int $count): Generator<TKey, TValue>` — lazy; preserves keys; skips first $count
- `dropWhile(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>` — lazy; preserves keys

## Flattening / Mapping
- `flatten(iterable $data, int $depth = 1): Generator<int, mixed>`
  - Lazy; keys discarded; depth 0 yields original elements as-is (keys lost)
- `flatMap(iterable $data, callable(TValue, TKey): (iterable|mixed) $transformer): Generator<int, mixed>`
  - Lazy; keys discarded; maps to iterable and flattens by one

## Examples
```php
use function Denprog\RiverFlow\Pipes\{map, filter, toList, flatten, zip, zipWith, uniq, partition};

$evens = [1,2,3,4,5,6]
    |> filter(fn(int $n) => $n % 2 === 0)
    |> toList(); // [2,4,6]

$flat = [[1,2], [3, [4]], 5]
    |> flatten(depth: 2)
    |> toList(); // [1,2,3,4,5]

$z = [1,2]
    |> zipWith(['a','b','c'])
    |> toList(); // [[1,'a'], [2,'b']]

$uniq = [1,1,'1',2]
    |> uniq()
    |> toList(); // [1,'1',2]

[$pass, $fail] = partition(['a'=>1,'b'=>2,'c'=>3], fn(int $v) => $v > 1);
// $pass = ['b'=>2,'c'=>3], $fail = ['a'=>1]
```

### Direct (non-pipe) usage
```php
use function Denprog\RiverFlow\Pipes\{map, filter, toList, flatten, zip, uniq, groupBy, values, sortBy};

// Filter and materialize
$evens = toList(filter([1,2,3,4,5,6], fn (int $n) => $n % 2 === 0)); // [2,4,6]

// Flatten nested structure to depth 2
$flat = toList(flatten([[1,2], [3, [4]], 5], depth: 2)); // [1,2,3,4,5]

// Zip multiple iterables
$z = toList(zip([1,2], ['a','b','c'])); // [[1,'a'], [2,'b']]

// Group and sort groups by size
$byFirst = groupBy(['apple','apricot','banana','blueberry','avocado'], fn (string $s) => $s[0]);
$sorted  = sortBy(values($byFirst), fn (array $xs) => \count($xs));
```

### Pipeline chaining (one-liners)
```php
use function Denprog\RiverFlow\Pipes\{filter, map, take, toList, flatten, uniq, groupBy, values, sortBy, zipWith};

// 1) Transform → filter → take → materialize
$topSquares = [1,2,3,4,5,6,7,8,9]
    |> map(fn(int $n) => $n * $n)
    |> filter(fn(int $x) => $x % 2 === 0)
    |> take(3)
    |> toList(); // [4, 16, 36]

// 2) Flatten nested, uniquify, then toList
$flatUnique = [[1,2], [2,3, [3,4]], 4]
    |> flatten(2)
    |> uniq()
    |> toList(); // [1,2,3,4]

// 3) Group, then traverse group values and sort by size (eager steps in between)
$byFirstLetter = ['apple','apricot','banana','blueberry','avocado']
    |> groupBy(fn(string $s) => $s[0]) // array groups
    |> values()                         // generator over grouped arrays
    |> map(fn(array $xs) => $xs)
    |> toList()                         // materialize to array<list<string>>
    |> sortBy(fn(array $xs) => \count($xs)); // smallest group first

// 4) Zipping pipelines
$zipped = [1, 2, 3]
    |> zipWith(['a','b'], ['X','Y','Z'])
    |> toList(); // [[1,'a','X'], [2,'b','Y']]
```
