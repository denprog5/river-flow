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
  - Eager; returns the smallest value using PHP comparison semantics. Returns the original item (type preserved). Numeric strings are compared numerically against numbers but the original item is returned (e.g., `max([1, '3.5']) === '3.5'`). Returns `null` for empty input.
- `max(iterable<int|float|string> $data): int|float|string|null`
  - Eager; returns the largest value using PHP comparison semantics. Returns the original item (type preserved). Numeric strings are compared numerically against numbers but the original item is returned. Returns `null` for empty input.
- `count(iterable $data): int`
- `isEmpty(iterable $data): bool`
- `contains(iterable $data, mixed $needle): bool` (strict comparison)
- `every(iterable $data, callable(TValue, TKey): bool $predicate): bool` — true if all elements satisfy the predicate; true for empty iterables
- `some(iterable $data, callable(TValue, TKey): bool $predicate): bool` — true if any element satisfies the predicate; false for empty iterables

## Accumulation
- `scan(iterable $data, callable(TCarry|null, TValue, TKey): TCarry $reducer, TCarry|null $initial = null): Generator<TKey, TCarry>`
  - Lazy; inclusive left-to-right accumulation; yields each intermediate accumulated value; preserves keys. Supports currying: `scan($reducer, $initial)($data)`.
- `scanRight(iterable $data, callable(TCarry|null, TValue, TKey): TCarry $reducer, TCarry|null $initial = null): Generator<TKey, TCarry>`
  - Lazy; inclusive right-to-left accumulation; buffers input then yields results in original order; preserves keys. Supports currying: `scanRight($reducer, $initial)($data)`.

## Conversions
- `toList(iterable $data): array<int, mixed>` — eager; discards keys
- `toArray(iterable $data): array<array-key, mixed>` — eager; preserves keys
- `values(iterable $data): Generator<int, mixed>` — lazy; discards keys
- `keys(iterable $data): Generator<int, array-key>` — lazy; yields keys

## Sequence / Generation
- `range(int|float $start, int|float $end, int|float $step = 1): Generator<int, int|float>`
  - Lazy; end-exclusive; supports positive and negative steps; validates parameters eagerly
  - Examples: `range(0, 5)` yields 0,1,2,3,4; `range(5, 0, -2)` yields 5,3,1

## Reshaping / Ordering
- `groupBy(iterable $data, callable(TValue, TKey): array-key $grouper): array<array-key, array<TKey, TValue>>`
  - Eager; preserves original keys inside each group. Supports flexible order and currying: `groupBy($grouper, $data)` or `groupBy($grouper)($data)`.
- `keyBy(iterable $data, callable(TValue, TKey): array-key $keySelector): array<array-key, TValue>`
  - Eager; later keys overwrite earlier ones. Supports flexible order and currying: `keyBy($keySelector, $data)` or `keyBy($keySelector)($data)`.
- `sortBy(iterable $data, callable(TValue, TKey): int|float|string $getComparable): array<TKey, TValue>`
  - Eager; stable key preservation
- `sortWith(iterable $data, callable(TValue, TKey): int ...$comparators): array<TKey, TValue>`
  - Eager; preserves keys; accepts one or more comparator callables of the form `fn($a, $b): int` and applies them in order (later ones are tie-breakers). Supports currying: `sortWith($cmp1, $cmp2, ...)($data)`. See `Utils\ascend`/`Utils\descend` for building comparators.
- `sort(iterable<int|float|string> $data): array<array-key, int|float|string>`
  - Eager; natural ascending order; keys preserved

## Uniqueness
- `uniq(iterable $data): Generator<TKey, TValue>`
  - Lazy; first-occurrence keys preserved; objects by identity; arrays via serialize(); unhashable items (e.g., arrays with closures) are skipped
- `uniqBy(iterable $data, callable(TValue, TKey): mixed $identifier): Generator<TKey, TValue>`
  - Lazy; first-occurrence keys preserved; hashing rules like `uniq`. Supports flexible order and currying: `uniqBy($identifier, $data)` or `uniqBy($identifier)($data)`. Items with unhashable identifiers are skipped.

## Set operations (eager)
- `union(iterable $data, iterable $other): array<TKey, TValue>`
  - Eager; strict set union; preserves keys from the first occurrence across inputs; unhashable items skipped (see `uniq` hashing rules)
- `intersection(iterable $data, iterable $other): array<TKey, TValue>`
  - Eager; strict set intersection; preserves keys from the left iterable; unhashable items skipped
- `difference(iterable $data, iterable $other): array<TKey, TValue>`
  - Eager; strict set difference (left minus right); preserves keys from the left and collapses duplicates to the first occurrence; unhashable items skipped
- `symmetricDifference(iterable $data, iterable $other): array<TKey, TValue>`
  - Eager; strict symmetric difference (values present in exactly one of the inputs); left-only values first then right-only; preserves original keys; unhashable items skipped

## Combining / Windowing
- `zip(iterable $data, iterable ...$others): Generator<int, array<int, mixed>>`
  - Lazy; stops at shortest; keys discarded; yields numeric-indexed tuples
  - Accepts arrays, Iterator, and any Traversable (IteratorAggregate supported). All iterators are rewound before zipping.
  - If only one iterable is provided, yields 1-length rows. If any iterable is empty, the result is empty.
- `zipWith(iterable ...$others): callable(iterable $data): Generator<int, array<int, mixed>>`
  - Pipe-friendly curried form of `zip`. Same semantics as `zip` (keys discarded, stops at shortest, iterator inputs are rewound). If no other iterables are provided, behaves like `zip($data)` producing 1-length rows. Use in pipelines: `[1,2,3] |> zipWith(['a','b'])`
- `transpose(iterable<int, iterable> $rows): array<int, array<int, mixed>>`
  - Eager; keys discarded; aligns to the shortest row (extra elements in longer rows are ignored)
- `unzip(iterable<int, iterable> $rows): array<int, array<int, mixed>>`
  - Eager; keys discarded; equivalent to `transpose`; useful for splitting pairs into two lists
- `chunk(iterable $data, int $size): Generator<int, array<int, mixed>>`
  - Lazy; keys discarded; last chunk may be smaller; throws if size <= 0. Supports currying: `chunk($size)($data)`. For `$size = 1` yields singleton chunks; empty input yields no chunks.
- `aperture(iterable $data, int $size): Generator<int, array<int, mixed>>`
  - Lazy; keys discarded; sliding windows of exact length `$size`; throws if size <= 0
- `partitionBy(iterable $data, callable(TValue, TKey): array-key $discriminator): Generator<int, array<TKey, TValue>>`
  - Lazy; splits the sequence into contiguous chunks where the discriminator value stays the same. Preserves original keys inside chunks; outer result is numerically indexed. Supports currying: `partitionBy($discriminator)($data)` or direct-call.
- `partition(iterable $data, callable(TValue, TKey): bool $predicate): array{0: array<TKey, TValue>, 1: array<TKey, TValue>}`
  - Eager; keys preserved; returns [pass, fail]
  - Direct-call requires an iterable first argument and a callable predicate. A non-iterable first argument will throw `InvalidArgumentException`. A non-callable predicate in direct invocation will result in a PHP `TypeError` due to parameter typing.
- `splitAt(iterable $data, int $index): array{0: list<mixed>, 1: list<mixed>}`
  - Eager; keys discarded; returns `[left, right]` where `left` has the first `$index` items, `right` the remainder. Supports currying: `splitAt($index)($data)`.
  - Edge cases: `$index <= 0` -> `[[], all]`; `$index >= count(data)` -> `[all, []]`.
- `splitWhen(iterable $data, callable(TValue, TKey): bool $predicate): array{0: list<TValue>, 1: list<TValue>}`
  - Eager; keys discarded; splits before the first element where predicate is true.
  - The matching element is the first of the right part. No match -> `[all, []]`.
  - Supports currying: `splitWhen($predicate)($data)`. Direct-call requires an iterable first argument and a callable predicate; non-callable predicate will result in a PHP `TypeError` due to parameter typing, and a non-iterable first argument will throw `InvalidArgumentException`.

## Control flow
- `take(iterable $data, int $count): Generator<TKey, TValue>` — lazy; preserves keys; yields up to $count
- `takeWhile(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>` — lazy; preserves keys
- `drop(iterable $data, int $count): Generator<TKey, TValue>` — lazy; preserves keys; skips first $count
- `dropWhile(iterable $data, callable(TValue, TKey): bool $predicate): Generator<TKey, TValue>` — lazy; preserves keys
- `dropLast(iterable $data, int $count): Generator<TKey, TValue>` — lazy with lookahead; preserves keys; skips last $count
- `takeLast(iterable $data, int $count): Generator<TKey, TValue>` — lazy (buffers at most `$count`); preserves keys; yields only the final `$count`
- `tail(iterable $data): Generator<TKey, TValue>` — lazy; preserves keys; drops the first element
- `init(iterable $data): Generator<TKey, TValue>` — lazy; preserves keys; drops the last element

## Flattening / Mapping
- `flatten(iterable $data, int $depth = 1): Generator<int, mixed>`
  - Lazy; keys discarded; depth 0 yields original elements as-is (keys lost)
- `flatMap(iterable $data, callable(TValue, TKey): (iterable|mixed) $transformer): Generator<int, mixed>`
  - Lazy; keys discarded; maps to iterable and flattens by one

## Examples
```php
use function Denprog\RiverFlow\Pipes\{map, filter, toList, toArray, flatten, zip, zipWith, transpose, unzip, uniq, partition, aperture, dropLast, takeLast, tail, init, range, chunk, union, intersection, difference, symmetricDifference, sortWith};
use function Denprog\RiverFlow\Utils\{ascend, descend};

$evens = [1,2,3,4,5,6]
    |> filter(fn(int $n) => $n % 2 === 0)
    |> toList(); // [2,4,6]

$flat = [[1,2], [3, [4]], 5]
    |> flatten(depth: 2)
    |> toList(); // [1,2,3,4,5]

$z = [1,2]
    |> zipWith(['a','b','c'])
    |> toList(); // [[1,'a'], [2,'b']]

// Transpose (eager, keys discarded, aligns to shortest)
$cols = transpose([
    ['r1c1', 'r1c2', 'r1c3'],
    ['r2c1', 'r2c2'],
]);
// $cols === [['r1c1','r2c1'], ['r1c2','r2c2']]

// Unzip pairs (eager)
$unz = unzip([[1, 'a'], [2, 'b'], [3, 'c']]);
// $unz === [[1,2,3], ['a','b','c']]

$uniq = [1,1,'1',2]
    |> uniq()
    |> toList(); // [1,'1',2]

// sortWith using Utils comparators (eager, keys preserved)
$people = [
    'u3' => ['name' => 'Cara',  'age' => 40],
    'u1' => ['name' => 'Alice', 'age' => 30],
    'u2' => ['name' => 'Bob',   'age' => 35],
];
$byAgeAsc   = ascend(fn (array $p) => $p['age']);
$byNameDesc = descend(fn (array $p) => $p['name']);
$sorted = sortWith($people, $byAgeAsc, $byNameDesc);
// $sorted keeps keys and sorts by age ascending; ties broken by name descending

// Set operations (eager) — keys preserved
$a = ['a' => 1, 'b' => 2, 'c' => '2', 'd' => 3, 'e' => 4];
$b = ['x' => 2, 'y' => 5, 'z' => '2', 'w' => 6];
$u = union($a, $b);                // ['a'=>1,'b'=>2,'c'=>'2','d'=>3,'e'=>4,'y'=>5,'w'=>6]
$i = intersection($a, $b);          // ['b'=>2,'c'=>'2'] (keys from left)
$d = difference($a, $b);            // ['a'=>1,'d'=>3,'e'=>4]
$s = symmetricDifference($a, $b);   // ['a'=>1,'d'=>3,'e'=>4,'y'=>5,'w'=>6]

// Sequence generation: range (lazy, end-exclusive)
$nums = range(0, 5) |> toList(); // [0,1,2,3,4]

// Tail and init (lazy, keys preserved)
$t = ['a'=>1,'b'=>2,'c'=>3]
    |> tail()
    |> toArray(); // ['b'=>2,'c'=>3]

$i = ['x'=>10,'y'=>20,'z'=>30]
    |> init()
    |> toArray(); // ['x'=>10,'y'=>20]

[$pass, $fail] = partition(['a'=>1,'b'=>2,'c'=>3], fn(int $v) => $v > 1);
// $pass = ['b'=>2,'c'=>3], $fail = ['a'=>1]

// Sliding window of size 2 (keys discarded)
$wins = ['x'=>1,'y'=>2,'z'=>3,'w'=>4]
    |> aperture(2)
    |> toList(); // [[1,2],[2,3],[3,4]]

// Drop last and take last
$dropped = ['a'=>1,'b'=>2,'c'=>3,'d'=>4]
    |> dropLast(2)
    |> toList(); // [1,2]

$last2 = ['a'=>1,'b'=>2,'c'=>3,'d'=>4]
    |> takeLast(2)
    |> toList(); // [3,4]

// Chunk into size 2 (keys discarded)
$chunks = ['x'=>1,'y'=>2,'z'=>3]
    |> chunk(2)
    |> toList(); // [[1,2],[3]]

// splitAt and splitWhen (keys discarded)
[$l, $r] = ['a'=>1,'b'=>2,'c'=>3,'d'=>4]
    |> splitAt(2);
// $l = [1,2], $r = [3,4]

[$before, $after] = ['a'=>1,'b'=>2,'c'=>3,'d'=>4]
    |> splitWhen(fn (int $v) => $v >= 3);
// $before = [1,2], $after = [3,4]
```

### More examples: scans and partitionBy
```php
use function Denprog\RiverFlow\Pipes\{scan, scanRight, partitionBy, toList, toArray};

// Prefix sums (lazy, keys preserved)
$prefix = [1, 2, 3, 4]
    |> scan(fn (?int $c, int $v) => ($c ?? 0) + $v, 0)
    |> toList(); // [1, 3, 6, 10]

// Suffix sums (lazy, yields in original order)
$suffix = [1, 2, 3]
    |> scanRight(fn (?int $c, int $v) => ($c ?? 0) + $v, 0)
    |> toList(); // [6, 5, 3]

// Partition contiguous groups by first letter (lazy)
$groups = ['ant', 'apple', 'bear', 'bob', 'cat']
    |> partitionBy(fn (string $s) => $s[0])
    |> toList();
// $groups === [[0=>'ant',1=>'apple'], [2=>'bear',3=>'bob'], [4=>'cat']]
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
