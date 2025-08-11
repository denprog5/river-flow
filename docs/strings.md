# Strings Module Reference

Namespace: `Denprog\RiverFlow\Strings`

All functions are UTF-8 aware when mbstring is available.

## API
- `trim(string $data, string $characters = " \t\n\r\0\x0B"): string`
  - Safe wrapper over PHP trim; avoids recursion by calling global trim
- `lines(string $data): array<int, string>`
  - Splits by any line break (CRLF/CR/LF) using `/\R/u`
- `replacePrefix(string $data, string $prefix, string $replacement): string`
  - If `$data` starts with `$prefix`, replace it with `$replacement`; empty prefix prepends `$replacement`
- `toLowerCase(string $data): string`
  - Uses `mb_strtolower($data, 'UTF-8')` when available; fallback to `strtolower`
- `toUpperCase(string $data): string`
  - Uses `mb_strtoupper($data, 'UTF-8')` when available; fallback to `strtoupper`
- `length(string $data): int`
  - Uses `mb_strlen($data, 'UTF-8')` when available; fallback to `strlen`
- `split(string $data, string $delimiter, int $limit = PHP_INT_MAX): array<int, string>`
  - Positive limit behaves like `explode` with remainder in last element; zero treated as 1; negative drops last `-$limit` parts; empty delimiter throws `InvalidArgumentException`
- `join(iterable $data, string $separator = ''): string`
  - Casts each element to string; accepts scalars and `Stringable`; throws `InvalidArgumentException` for non-stringable elements in iterables

## Examples
```php
use function Denprog\RiverFlow\Strings\{trim, lines, replacePrefix, toLowerCase, toUpperCase, length, split, join};

$clean = " -- Hello -- " |> trim(" -"); // "Hello"
$rows  = "a\r\nb\nc" |> lines();     // ["a","b","c"]
$fix   = 'foobar' |> replacePrefix('foo', 'X'); // 'Xbar'

$lower = 'ПрИвЕт' |> toLowerCase();  // 'привет' (if mbstring)
$upper = 'Привет' |> toUpperCase();  // 'ПРИВЕТ' (if mbstring)

$len1 = '' |> length();           // 0
$len2 = '😀' |> length();         // 1 (if mbstring)

$parts1 = 'a|b|c|d' |> split('|', 2);  // ['a','b|c|d']
$parts2 = 'a|b|c|d' |> split('|', -1); // ['a','b','c']

$list = [2024, '01', 15] |> join('-');  // '2024-01-15'
```
