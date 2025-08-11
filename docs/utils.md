# Utils Module Reference

Namespace: `Denprog\RiverFlow\Utils`

## API
- `tap(mixed $data, callable(mixed): void $callback): mixed`
  - Calls `$callback($data)` for side effects and returns `$data` unchanged
- `identity(mixed $data): mixed`
  - Returns the value as-is
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
```
