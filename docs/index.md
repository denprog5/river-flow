# RiverFlow for PHP 8.5 — Canonical Documentation

Modern, strictly-typed functional utilities for PHP 8.5 with first-class support for the pipe operator (|>). RiverFlow provides:

- Pipes: lazy/eager collection operators with predictable key handling
- Strings: mbstring-aware helpers
- Utils: small utilities for composition and ergonomics (tap, identity, compose, pipe)

Quality gates: Pest tests, PHPStan at max, Rector automation, PHP-CS-Fixer, security audit, and cross-platform CI.

## Requirements
- PHP >= 8.5
- Composer 2

## Installation
```bash
composer require denprog/river-flow
```

## Quickstart
```php
<?php

declare(strict_types=1);

use function Denprog\RiverFlow\Pipes\{map, filter, toList};
use function Denprog\RiverFlow\Strings\{trim, toUpperCase};
use function Denprog\RiverFlow\Utils\{tap, compose, pipe};

$result = [10, 15, 20, 25, 30]
    |> filter(fn(int $n) => $n % 2 === 0)  // [10, 20, 30] (lazy)
    |> map(fn(int $n) => $n / 10)          // [1, 2, 3] (lazy)
    |> toList();                           // [1, 2, 3] (eager)

$text = "  river flow  "
    |> trim()
    |> toUpperCase(); // "RIVER FLOW"

$sum = fn(int $a, int $b): int => $a + $b;   // right-most may be variadic
$inc = fn(int $x): int => $x + 1;
$dbl = fn(int $x): int => $x * 2;

$f = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
assert($f(3, 4) === 16);

$out = pipe(5, fn($x) => $x + 3, fn($x) => $x * 2, 'strval');
assert($out === '16');
```

## Module Guides
- Pipes: see [pipes.md](./pipes.md)
- Strings: see [strings.md](./strings.md)
- Utils: see [utils.md](./utils.md)

## Conventions
- Laziness: functions returning Generator are lazy; eager ones return arrays/scalars
- Keys: docs call out when keys are preserved or discarded
- Safety: no use of unsafe functions (eval, exec, etc.); security audit runs in CI

## Development (optional)
```bash
composer install
composer test           # Pest
composer analyse        # PHPStan
composer cs:lint        # PHP-CS-Fixer (dry-run)
composer rector:check   # Rector (dry-run)
composer test:coverage  # Clover coverage
```

