# RiverFlow for PHP 8.5

[![CI](https://github.com/denprog5/river-flow/actions/workflows/ci.yml/badge.svg)](https://github.com/denprog5/river-flow/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-8.5%2B-777bb3?logo=php&logoColor=white)

Modern, strictly-typed functional utilities for PHP 8.5: lazy collection pipelines (Pipes), mbstring‑aware string helpers (Strings), and ergonomic composition tools (Utils). Designed for the PHP 8.5 pipe operator `|>` and built with rigorous QA (Pest, PHPStan max, Rector, CS).

---

## Highlights
- __Pipe operator first__: idiomatic `|>` pipelines, no external wrappers
- __Lazy + eager__: predictable key behavior, memory‑friendly where it matters
- __Strong typing__: precise PHPDoc generics, PHPStan at max level
- __Unicode aware__: `Strings` use mbstring when available
- __Ergonomics__: `tap`, `identity`, `compose`, `pipe`
- __Cross‑platform CI__: Linux/macOS/Windows

## Requirements
- PHP >= 8.5
- Composer 2

## Install
```bash
composer require denprog/river-flow
```

## Quickstart
```php
<?php

declare(strict_types=1);

use function Denprog\RiverFlow\Pipes\{map, filter, toList};
use function Denprog\RiverFlow\Strings\{trim, toUpperCase};

$result = [10, 15, 20, 25, 30]
    |> filter(fn (int $n) => $n % 2 === 0) // [10, 20, 30]
    |> map(fn (int $n) => $n / 10)         // [1, 2, 3]
    |> toList();                           // [1, 2, 3]

$text = "  river flow  "
    |> trim()
    |> toUpperCase(); // "RIVER FLOW"
```

## Dual‑mode usage (direct and pipe‑friendly)
- __Direct__: pass data as the first argument, e.g. `toList([1,2,3])`
- __Curried / pipe‑friendly__: call a function without the data argument to get a callable, then chain with `|>`

```php
use function Denprog\RiverFlow\Pipes\{map, filter, toList};

$res1 = toList(map(filter([1,2,3,4], fn($x)=>$x%2===0), fn($x)=>$x*10))); // [20, 40]

$res2 = [1,2,3,4]
    |> filter(fn($x) => $x % 2 === 0)
    |> map(fn($x) => $x * 10)
    |> toList(); // [20, 40]
```

## Other composition helpers (non‑pipe)
In addition to the `|>` operator, RiverFlow provides classic composition utilities in `Utils` which do not require pipes.

```php
use function Denprog\RiverFlow\Utils\{compose, pipe};

$sum = fn (int $a, int $b): int => $a + $b;   // right‑most may be variadic
$inc = fn (int $x): int => $x + 1;
$dbl = fn (int $x): int => $x * 2;

$f = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
assert($f(3, 4) === 16);

$out = pipe(5, fn($x) => $x + 3, fn($x) => $x * 2, 'strval');
assert($out === '16');
```

## Module snapshots

### Pipes
```php
use function Denprog\RiverFlow\Pipes\{filter, map, take, toList, toArray, flatten, uniq, groupBy, values, sortBy, zipWith, range, repeat, times, tail, init, scan, scanRight, partitionBy, distinctUntilChanged, intersperse, pairwise, countBy};

// Transform → filter → take → materialize
$topSquares = [1,2,3,4,5,6,7,8,9]
    |> map(fn (int $n) => $n * $n)
    |> filter(fn (int $x) => $x % 2 === 0)
    |> take(3)
    |> toList(); // [4, 16, 36]

// Flatten nested, uniquify
$flatUnique = [[1,2], [2,3, [3,4]], 4]
    |> flatten(2)
    |> uniq()
    |> toList(); // [1,2,3,4]

// Group, traverse group values and sort by size
$byFirstLetter = ['apple','apricot','banana','blueberry','avocado']
    |> groupBy(fn (string $s) => $s[0])
    |> values()
    |> map(fn (array $xs) => $xs)
    |> toList()
    |> sortBy(fn (array $xs) => \count($xs));

// Zip in pipelines
$zipped = [1, 2, 3]
    |> zipWith(['a','b'], ['X','Y','Z'])
    |> toList(); // [[1,'a','X'], [2,'b','Y']]

// Numeric ranges and generation
$nums = range(0, 5) |> toList(); // [0,1,2,3,4]

// Infinite repetition capped with take
$threes = repeat(3) |> take(4) |> toList(); // [3,3,3,3]

// Produce values by index
$squares = times(5, fn (int $i): int => $i * $i) |> toList(); // [0,1,4,9,16]

$rest = ['a'=>1,'b'=>2,'c'=>3]
    |> tail()
    |> toArray(); // ['b'=>2,'c'=>3]

$allButLast = ['x'=>10,'y'=>20,'z'=>30]
    |> init()
    |> toArray(); // ['x'=>10,'y'=>20]

// Inclusive prefix and suffix scans (lazy, keys preserved)
$prefix = [1, 2, 3, 4]
    |> scan(fn (?int $c, int $v) => ($c ?? 0) + $v, 0)
    |> toList(); // [1, 3, 6, 10]

$suffix = [1, 2, 3]
    |> scanRight(fn (?int $c, int $v) => ($c ?? 0) + $v, 0)
    |> toList(); // [6, 5, 3]

// Partition into contiguous groups by discriminator (lazy)
$groups = ['ant', 'apple', 'bear', 'bob', 'cat']
    |> partitionBy(fn (string $s) => $s[0])
    |> toList();
// [[0=>'ant',1=>'apple'], [2=>'bear',3=>'bob'], [4=>'cat']]

// Skip consecutive duplicates (preserves first keys of runs)
$d = ['ant', 'apple', 'bear', 'bob', 'cat']
    |> distinctUntilChanged(fn (string $s) => $s[0])
    |> toArray(); // [0=>'ant', 2=>'bear', 4=>'cat']

// Intersperse a separator (keys discarded)
$withPipes = ['a','b','c']
    |> intersperse('|')
    |> toList(); // ['a','|','b','|','c']

// Pairwise (consecutive pairs)
$pairs = [1,2,3]
    |> pairwise()
    |> toList(); // [[1,2],[2,3]]

// Count by classifier (eager)
$counts = ['apple','apricot','banana','blueberry','avocado']
    |> countBy(fn (string $s) => $s[0]); // ['a' => 3, 'b' => 2]
```

### Strings
```php
use function Denprog\RiverFlow\Strings\{trim, replacePrefix, toLowerCase, toUpperCase, split, join, length};

$title = "  River FLOW: Intro  "
    |> trim()
    |> toLowerCase()
    |> replacePrefix('river ', 'river ');
// "river flow: intro"

$csv = ' foo | Bar |BAZ '
    |> trim()
    |> toLowerCase()
    |> split('|')
    |> join(','); // "foo , bar ,baz"

$n = '  Hello  ' |> trim() |> toUpperCase() |> length(); // 5
```

### Utils
```php
use function Denprog\RiverFlow\Utils\{tap, identity};
use function Denprog\RiverFlow\Strings\{trim, toUpperCase};

$result = '  Hello  '
    |> trim()
    |> tap(fn (string $s) => error_log("after trim: $s"))
    |> toUpperCase()
    |> tap(fn (string $s) => error_log("after upper: $s"));
// 'HELLO'

$val = 10
    |> identity()
    |> (fn (int $x) => $x + 5)
    |> (fn (int $x) => $x * 2); // 30
```

### Structs
```php
use function Denprog\RiverFlow\Structs\{pick, omit, getPath, setPath, evolve, zipAssoc};

$user = ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30, 'password' => 'secret'];

// Pick specific keys
$partial = pick(['name', 'email'], $user);
// ['name' => 'Alice', 'email' => 'alice@example.com']

// Omit sensitive keys
$safe = omit(['password'], $user);
// ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30]

// Safe deep access (returns null when path missing)
$data = ['user' => ['profile' => ['bio' => 'Hello world']]];
$bio = getPath(['user', 'profile', 'bio'], $data); // 'Hello world'
$missing = getPath(['user', 'settings', 'theme'], $data); // null

// Immutable deep set (creates nested arrays as needed)
$updated = setPath(['user', 'profile', 'bio'], 'Updated bio', $data);
// $data remains unchanged, $updated has new bio

// Transform with spec — apply functions to specific keys
$evolved = evolve([
    'age' => fn($a) => $a + 1,
    'name' => fn($n) => strtoupper($n),
], $user);
// ['name' => 'ALICE', 'email' => '...', 'age' => 31, 'password' => 'secret']

// Zip keys with values
$result = zipAssoc(['a', 'b', 'c'], [1, 2, 3]);
// ['a' => 1, 'b' => 2, 'c' => 3]
```

## Documentation
- Start here: `docs/index.md`
- Module references: [Pipes](docs/pipes.md), [Strings](docs/strings.md), [Utils](docs/utils.md), [Structs](docs/structs.md)

## Development
```bash
composer install

# QA
composer test            # Pest
composer analyse         # PHPStan (max)
composer cs:lint         # PHP-CS-Fixer (dry-run)
composer rector:check    # Rector (dry-run)
composer cs:fix          # Apply PHP-CS-Fixer fixes
composer rector:fix      # Apply Rector refactors
```

## Security
See [SECURITY.md](SECURITY.md) for our vulnerability disclosure policy.

## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md) for setup, standards (PSR-12), and PR process.

## Code of Conduct
This project adheres to the Contributor Covenant. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Versioning & Changelog
We follow SemVer. See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License
MIT — see [LICENSE](LICENSE).
