# Utils Module Reference

Namespace: `Denprog\RiverFlow\Utils`

## API
- `tap(mixed $data, callable(mixed): void $callback): mixed` and `tap(callable(mixed): void $callback): callable(mixed $data): mixed`
  - Calls the callback with the value for side effects and returns the value unchanged. The curried form is pipe-friendly.
- `identity(mixed $data): mixed` and `identity(): callable(mixed): mixed`
  - Returns the value as-is. The curried form is pipe-friendly.
- `compose(callable ...$functions): callable`
  - Right-to-left composition; `compose(f, g, h)` returns a function equivalent to `fn(...$args) => f(g(h(...$args)))`
  - The right-most callable may accept multiple arguments; others are unary
- `pipe(mixed $value, callable ...$functions): mixed`
  - Left-to-right application; `pipe($v, f, g, h) === h(g(f($v)))`

- `complement(callable $pred): callable`
  - Logical negation of a predicate; returns a predicate that is true when `$pred` is false
- `both(callable $a, callable $b): callable` / `either(callable $a, callable $b): callable`
  - Combine two predicates with logical AND / OR
- `allPass(array<int, callable> $predicates): callable`
  - Returns a predicate that is true only if all predicates return true
- `anyPass(array<int, callable> $predicates): callable`
  - Returns a predicate that is true if any predicate returns true
- `when(callable $pred, callable $fn): callable` / `unless(callable $pred, callable $fn): callable`
  - Conditionally transform a value based on a predicate
- `ifElse(callable $pred, callable $onTrue, callable $onFalse): callable`
  - Branching transformer; selects one of two callables based on predicate
- `cond(array<int, array{0: callable, 1: callable}> $pairs): callable`
  - Applies the function of the first matching predicate; returns `null` if none match
- `converge(callable $after, array<int, callable> $branches): callable`
  - Runs branches with the same input and passes their results to `after`
- `once(callable $fn): callable`
  - Ensures a function runs only once; subsequent calls return cached result
- `memoizeWith(callable $keyFn, callable $fn): callable`
  - Caches results by normalized keys from `$keyFn` (scalar|null|Stringable)
- `partial(callable $fn, mixed ...$args): callable` / `partialRight(callable $fn, mixed ...$args): callable`
  - Pre-applies arguments on the left or right
- `ascend(callable $by): callable(mixed, mixed): int` / `descend(callable $by): callable(mixed, mixed): int`
  - Build comparators for sorting; `$by` must return scalar|null or Stringable

## Examples
```php
use function Denprog\RiverFlow\Utils\{tap, identity};

$seen = null;
$out = tap(['a' => 1], function ($x) use (&$seen) { $seen = $x; });
assert($out === $seen);

// Pipe-friendly usage (curried forms)
$val = 42 |> identity(); // 42

$log = [];
$after = [1,2,3] |> tap(function(array $xs) use (&$log) { $log[] = array_sum($xs); });
// $after === [1,2,3]; $log === [6]
```

### Pipeline chaining (PHP 8.5 `|>`) examples
```php
use function Denprog\RiverFlow\Utils\{tap, identity};
use function Denprog\RiverFlow\Strings\{trim, toUpperCase, toLowerCase, split, join};

// 1) Observe intermediate values with tap()
$result = '  Hello  '
    |> trim()
    |> tap(fn (string $s) => error_log("after trim: $s"))
    |> toUpperCase()
    |> tap(fn (string $s) => error_log("after upper: $s"));
// $result === 'HELLO'

// 2) Identity in a longer chain (useful when branching/conditional transforms)
$value = 10
    |> identity()
    |> (fn (int $x) => $x + 5)
    |> (fn (int $x) => $x * 2); // 30

// 3) Strings pipeline with Utils::tap for logging, then join()
$csv = ' foo | Bar |BAZ '
    |> trim()
    |> toLowerCase()
    |> tap(fn (string $s) => error_log("normalized: $s"))
    |> split('|')
    |> join(',');
// "foo , bar ,baz"

// 4) Use tap to capture metrics without breaking the chain
$sum = 0;
$out = [1, 2, 3]
    |> tap(function (array $xs) use (&$sum) { $sum = array_sum($xs); })
    |> identity();
// $sum === 6; $out === [1,2,3]
```

### Classic composition (non-pipe)
The `compose()` and `pipe()` helpers provide traditional composition without requiring the PHP 8.5 pipe operator.

```php
use function Denprog\RiverFlow\Utils\{compose, pipe};

$sum = fn (int $a, int $b): int => $a + $b;   // right-most may be variadic
$inc = fn (int $x): int => $x + 1;
$dbl = fn (int $x): int => $x * 2;

// Right-to-left composition
$f = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
assert($f(3, 4) === 16);

// Left-to-right application starting from an initial value
$out = pipe(5, fn($x) => $x + 3, fn($x) => $x * 2, 'strval');
assert($out === '16');
```

Notes
- Use `compose()` to build reusable functions from right to left.
- Use `pipe($value, ...)` for one-off flows without `|>`.
- `tap()` and `identity()` both support direct and curried usage; in pipelines, call them with no data argument.
- The examples mix Utils with Strings to demonstrate real chains; all modules are designed to compose.

### Predicates and control combinators
```php
use function Denprog\RiverFlow\Utils\{complement, both, either, allPass, anyPass, when, unless, ifElse, cond, converge, once, memoizeWith, partial, partialRight, ascend, descend};

// complement, both, either
$odd = complement(fn (int $x): bool => $x % 2 === 0);
$between = both(fn (int $x): bool => $x > 1, fn (int $x): bool => $x < 5);
$ok = either(fn (int $x): bool => $x > 10, fn (int $x): bool => $x % 2 === 0);

// allPass, anyPass
$all = allPass([fn (int $x): bool => $x > 0, fn (int $x): bool => $x % 2 === 0]);
$any = anyPass([fn (int $x): bool => $x > 0, fn (int $x): bool => $x % 2 === 0]);

// when, unless
$wrapIfStr = when(fn (mixed $x): bool => is_string($x), fn (mixed $x): string => "[$x]");
$wrapIfNotStr = unless(fn (mixed $x): bool => is_string($x), fn (mixed $x): string => "[$x]");

// ifElse, cond
$branch = ifElse(fn (int $x): bool => $x >= 0, fn (int $x): string => "pos:$x", fn (int $x): string => "neg:$x");
$sign = cond([
  [fn (int $x): bool => $x < 0, fn (): string => 'neg'],
  [fn (int $x): bool => $x === 0, fn (): string => 'zero'],
  [fn (int $x): bool => $x > 0, fn (): string => 'pos'],
]);

// converge (fan-in)
$sumLens = converge(fn (int $a, int $b): int => $a + $b, [
  fn (string $s): int => strlen($s),
  fn (string $s): int => ord($s[0]),
]);

// once, memoizeWith
$onlyOnce = once(fn (int $x): int => $x * 2);
$m = memoizeWith(fn (mixed $k): mixed => $k, 'strval');

// partial, partialRight
$concat = fn (string $a, string $b, string $c): string => "$a-$b-$c";
$left = partial($concat, 'L');         // $left('M','N') => 'L-M-N'
$right = partialRight($concat, 'R');   // $right('M','N') => 'M-N-R'

// ascend/descend comparators (for usort)
$byAgeAsc  = ascend(fn (array $x): int => $x['age']);
$byAgeDesc = descend(fn (array $x): int => $x['age']);
```
