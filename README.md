# River Flow for PHP 8.5

[![CI](https://github.com/denprog/river-flow/actions/workflows/ci.yml/badge.svg)](https://github.com/denprog/river-flow/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Modern, strictly-typed PHP 8.5 functional utilities with a focus on lazy pipelines (Pipes), mbstring-aware string helpers (Strings), and small utilities (Utils). Built for the PHP 8.5 pipe operator and rigorous quality gates (Pest, PHPStan, Rector, CS).

## Features
- PHP 8.5-ready; idiomatic use of the pipe operator (|>)
- Lazy/eager operations with predictable key behavior
- Strong typing and generics via PHPDoc; PHPStan at max level
- Mbstring-aware string helpers
- Utilities for composition: tap, identity, compose, pipe
- Cross-platform CI (Linux/macOS/Windows)

## Requirements
- PHP >= 8.5
- Composer 2

## Installation
```bash
composer require denprog/river-flow
```

## Usage
See full documentation in [docs/index.md](docs/index.md). Quick examples:
Module references: [Pipes](docs/pipes.md), [Strings](docs/strings.md), [Utils](docs/utils.md)

```php
<?php

declare(strict_types=1);

use function Denprog\RiverFlow\Pipes\{map, filter, toList};
use function Denprog\RiverFlow\Strings\{trim, toUpperCase};
use function Denprog\RiverFlow\Utils\{tap, compose, pipe};

$result = [10, 15, 20, 25, 30]
    |> filter(fn(int $n) => $n % 2 === 0)  // [10, 20, 30] (lazy)
    |> map(fn(int $n) => $n / 10)          // [1, 2, 3] (lazy)
    |> toList();                            // [1, 2, 3] (eager)

$text = "  river flow  "
    |> trim()
    |> toUpperCase(); // "RIVER FLOW"

// Utils: compose/pipe
$sum = fn(int $a, int $b): int => $a + $b;   // right-most may be variadic
$inc = fn(int $x): int => $x + 1;
$dbl = fn(int $x): int => $x * 2;

$f = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
assert($f(3, 4) === 16);

$out = pipe(5, fn($x) => $x + 3, fn($x) => $x * 2, 'strval');
assert($out === '16');
```

## Development
Clone the repository and install dependencies:
```bash
composer install
```

Run the checks:
```bash
composer test           # run tests (Pest)
composer analyse        # static analysis (PHPStan)
composer cs:lint        # code style (PHP-CS-Fixer dry-run)
composer cs:fix         # fix code style issues
composer test:coverage  # run tests with coverage
```

## Security
Please review SECURITY.md for our vulnerability disclosure policy and how to report issues securely.

## Contributing
See CONTRIBUTING.md for guidelines on setting up the environment, coding standards (PSR-12), commit message conventions, and pull request process.

## Code of Conduct
This project adheres to the Contributor Covenant. By participating, you are expected to uphold this code. See CODE_OF_CONDUCT.md.

## Versioning
This project follows Semantic Versioning (SemVer). Breaking changes will only occur in a new major version.

## License
Licensed under the MIT License. See the LICENSE file for details.
