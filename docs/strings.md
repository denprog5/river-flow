# Strings Module Reference

Namespace: `Denprog\RiverFlow\Strings`

All functions are UTF-8 aware when mbstring is available.

## API
- `trim(string $data, string $characters = " \t\n\r\0\x0B"): string` and `trim(): callable(string $data): string`
  - Safe wrapper over PHP trim; avoids recursion by calling global trim
- `lines(string $data): array<int, string>`
  - Splits by any line break (CRLF/CR/LF) using `/\R/u`
- `replacePrefix(string $data, string $prefix, string $replacement): string`
  - If `$data` starts with `$prefix`, replace it with `$replacement`; empty prefix prepends `$replacement`
  - Curried form: `replacePrefix($prefix, $replacement): callable(string $data): string`
- `toLowerCase(string $data): string`
  - Uses `mb_strtolower($data, 'UTF-8')` when available; fallback to `strtolower`
  - Curried form: `toLowerCase(): callable(string $data): string`
- `toUpperCase(string $data): string`
  - Uses `mb_strtoupper($data, 'UTF-8')` when available; fallback to `strtoupper`
  - Curried form: `toUpperCase(): callable(string $data): string`
- `length(string $data): int`
  - Uses `mb_strlen($data, 'UTF-8')` when available; fallback to `strlen`
  - Curried form: `length(): callable(string $data): int`
- `split(string $data, string $delimiter, int $limit = PHP_INT_MAX): array<int, string>`
  - Positive limit behaves like `explode` with remainder in last element; zero treated as 1; negative drops last `-$limit` parts; empty delimiter throws `InvalidArgumentException`
  - Curried form: `split($delimiter, $limit = PHP_INT_MAX): callable(string $data): array<int, string>`
- `join(iterable $data, string $separator = ''): string`
  - Casts each element to string; accepts scalars and `Stringable`; throws `InvalidArgumentException` for non-stringable elements in iterables
  - Curried form: `join($separator = ''): callable(iterable $data): string`
- `includes(string $data, string $needle): bool`
  - Returns true if `$needle` is contained in `$data`; empty needle is true. Curried: `includes($needle): callable(string $data): bool`
- `startsWith(string $data, string $prefix): bool`
  - Returns true if `$data` starts with `$prefix`; empty prefix is true. Curried: `startsWith($prefix): callable(string $data): bool`
- `endsWith(string $data, string $suffix): bool`
  - Returns true if `$data` ends with `$suffix`; empty suffix is true. Curried: `endsWith($suffix): callable(string $data): bool`

## Examples
```php
use function Denprog\RiverFlow\Strings\{trim, lines, replacePrefix, toLowerCase, toUpperCase, length, split, join, includes, startsWith, endsWith};

$clean = " -- Hello -- " |> trim(); // "-- Hello --" (only spaces removed)
$cleanCustom = " -- Hello -- " |> trim(characters: " -"); // "Hello"
$rows  = "a\r\nb\nc" |> lines();     // ["a","b","c"]
$fix   = 'foobar' |> replacePrefix('foo', 'X'); // 'Xbar'

$lower = 'ПрИвЕт' |> toLowerCase();  // 'привет' (if mbstring)
$upper = 'Привет' |> toUpperCase();  // 'ПРИВЕТ' (if mbstring)

$len1 = '' |> length();           // 0
$len2 = '😀' |> length();         // 1 (if mbstring)

$parts1 = 'a|b|c|d' |> split('|', 2);  // ['a','b|c|d']
$parts2 = 'a|b|c|d' |> split('|', -1); // ['a','b','c']

$list = [2024, '01', 15] |> join('-');  // '2024-01-15'

// membership and boundary checks
$has = 'hello' |> includes('ell');        // true
$sw  = 'hello' |> startsWith('he');       // true
$ew  = 'hello' |> endsWith('lo');         // true
```

### Direct (non-pipe) usage
```php
use function Denprog\RiverFlow\Strings\{trim, lines, replacePrefix, toLowerCase, toUpperCase, length, split, join};

$clean = trim("  Hello  ");                          // "Hello"
$cleanCustom = trim(" -- Hi -- ", characters: " -"); // "Hi"
$rows  = lines("a\r\nb\nc");                       // ["a","b","c"]
$fix   = replacePrefix('foobar', 'foo', 'bar-');       // 'bar-bar-'
$lower = toLowerCase('ПрИвЕт');                       // 'привет'
$upper = toUpperCase('Привет');                       // 'ПРИВЕТ'
$len   = length('😀');                                 // 1 (if mbstring)
$parts = split('a|b|c|d', '|', 2);                     // ['a','b|c|d']
$list  = join([2024, '01', 15], '-');                  // '2024-01-15'
```

### Pipeline chaining (one-liners)
```php
use function Denprog\RiverFlow\Strings\{trim, replacePrefix, toLowerCase, toUpperCase, split, join, length};

// Normalize a title: trim, lowercase, unify prefix
$title = "  River FLOW: Intro  "
    |> trim()
    |> toLowerCase()
    |> replacePrefix('river ', 'river ');
// "river flow: intro"

// Split, transform by case, and re-join
$csv = ' foo | Bar |BAZ '
    |> trim()
    |> toLowerCase()
    |> split('|')
    |> join(',');
// "foo , bar ,baz" (whitespace kept around delimiter; customize trimming as needed)

// Compute length after transformation
$n = '  Hello  ' |> trim() |> toUpperCase() |> length(); // 5

// Replace an optional prefix, then uppercase
$out = 'foobar' |> replacePrefix('foo', 'bar-') |> toUpperCase(); // 'BAR-bar-'
```

Notes
- All functions support direct and curried usage; in pipelines use `trim()` or `trim(characters: " -")` with named args for custom charlist.
- The examples use PHP 8.5 pipeline operator syntax `|>` for readability.
