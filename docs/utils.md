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

## Examples
```php
use function Denprog\RiverFlow\Utils\{tap, identity, compose, pipe};

$seen = null;
$out = tap(['a' => 1], function ($x) use (&$seen) { $seen = $x; });
assert($out === $seen);

$sum = fn(int $a, int $b): int => $a + $b;
$inc = fn(int $x): int => $x + 1;
$dbl = fn(int $x): int => $x * 2;

$f = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
assert($f(3, 4) === 16);

$res = pipe(5, fn($x) => $x + 3, fn($x) => $x * 2, 'strval');
assert($res === '16');

// Pipe-friendly usage (curried forms)
$val = 42 |> identity(); // 42

$log = [];
$after = [1,2,3] |> tap(function(array $xs) use (&$log) { $log[] = array_sum($xs); });
// $after === [1,2,3]; $log === [6]
```

### Pipeline chaining (PHP 8.5 `|>`) examples
```php
use function Denprog\RiverFlow\Utils\{tap, identity};
use function Denprog\RiverFlow\Strings\{trimWith, toUpperCase, toLowerCase, split, join};

// 1) Observe intermediate values with tap()
$result = '  Hello  '
    |> trimWith()
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
    |> trimWith()
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

Notes
- `tap()` and `identity()` both support direct and curried usage; in pipelines, call them with no data argument.
- The examples mix Utils with Strings to demonstrate real chains; all modules are designed to compose.
