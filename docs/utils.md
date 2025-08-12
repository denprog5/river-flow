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
