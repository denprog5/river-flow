# Structs Module Reference

Namespace: `Denprog\RiverFlow\Structs`

Helpers for working with associative arrays and simple objects (public properties only). All functions are immutable: they never mutate inputs.

## API
- `pick(array<int, int|string> $keys, array|object $from): array` and `pick(array<int, int|string> $keys): callable(array|object $from): array`
  - Returns a new array with only the requested keys (for arrays) or public props (for objects) that exist. Missing keys are ignored.
- `omit(array<int, int|string> $keys, array|object $from): array` and `omit(array<int, int|string> $keys): callable(array|object $from): array`
  - Returns a new array without the listed keys/props.
- `getPath(array<int, int|string> $path, array|object $data): mixed` and `getPath(array<int, int|string> $path): callable(array|object $data): mixed`
  - Safely reads from nested arrays/objects (public props) by path. Returns `null` if any segment is missing.
- `getPathOr(array<int, int|string> $path, mixed $default, array|object $data): mixed` and `getPathOr(array<int, int|string> $path, mixed $default): callable(array|object $data): mixed`
  - Like `getPath` but returns `$default` when the path is missing.
- `setPath(array<int, int|string> $path, mixed $value, array $data): array` and `setPath(array<int, int|string> $path, mixed $value): callable(array $data): array`
  - Returns a new array with `$value` written at the given path. Creates nested arrays as needed. Throws if `$path` is empty.
- `updatePath(array<int, int|string> $path, callable $fn, array $data): array` and `updatePath(array<int, int|string> $path, callable $fn): callable(array $data): array`
  - Returns a new array with the value at `$path` replaced by `$fn(currentOrNull)`. Creates nested arrays for intermediate segments. Throws if `$path` is empty.
- `evolve(array<array-key, callable(mixed): mixed> $spec, array $data): array` and `evolve(array<array-key, callable(mixed): mixed> $spec): callable(array $data): array`
  - Applies functions to corresponding keys that exist in `$data`. Non-existing keys in `$spec` are ignored.
- `zipAssoc(array<int, int|string> $keys, iterable $values): array` and `zipAssoc(array<int, int|string> $keys): callable(iterable $values): array`
  - Zips keys with values into an associative array. Stops when keys are exhausted.
- `unzipAssoc(iterable $pairs): array{0: array<int, int|string>, 1: array<int, mixed>}`
  - Splits `[key, value]` pairs into two arrays. Malformed pairs are ignored.

## Examples
```php
use function Denprog\RiverFlow\Structs\{pick, omit, getPath, getPathOr, setPath, updatePath, evolve, zipAssoc, unzipAssoc};

$conf = [
    'db' => ['host' => 'localhost', 'port' => 5432],
    'env' => 'prod',
];

$host = $conf |> getPath(['db', 'host']);              // 'localhost'
$mode = $conf |> getPathOr(['mode'], 'release');       // 'release'

$thin = $conf |> pick(['env']);                        // ['env' => 'prod']
$rest = $conf |> omit(['env']);                        // ['db' => [...]]

$withFlag = $conf |> setPath(['flags', 'debug'], true);
// ['db'=>['host'=>'localhost','port'=>5432], 'env'=>'prod', 'flags'=>['debug'=>true]]

$inc = updatePath(['db', 'port'], fn ($p) => ($p ?? 0) + 1)($conf);
// ['db'=>['host'=>'localhost','port'=>5433], 'env'=>'prod']

$spec = [
  'env' => fn (string $s) => strtoupper($s),
];
$out = $conf |> evolve($spec);                          // ['db'=>..., 'env'=>'PROD']

[$ks, $vs] = unzipAssoc([["a", 1], ["b", 2]]);      // [['a','b'], [1,2]]
$z = zipAssoc(['x','y'], [10,20,30]);                  // ['x'=>10,'y'=>20]
```
