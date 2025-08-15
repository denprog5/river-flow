# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [0.1.1] - 2025-08-15

### Docs
- Add "Dual-mode usage" section to docs/index.md (direct vs curried/pipe-friendly) with examples
- Expand playground scripts for Pipes/Strings/Utils to mirror canonical multi-step pipelines

### Chore
- Remove development-only `technical/` directory from the repository

## [0.1.0] - 2025-08-11

- Initial public release for PHP 8.5 (alpha-compatible)
- Pipes:
  - Implemented full function set per docs; clarified `average()` behavior (eager, generator-safe single pass; denominator includes all elements)
  - Fixed `average()` to compute sum and count in a single pass; removed redundant cast flagged by PHPStan
  - Added comprehensive tests for transforms and reshaping (filter, reject, map, pluck, toList/toArray, values/keys, sortBy/sort, groupBy/keyBy, take) and aggregation/misc (reduce, sum, average, first, last, find, count, isEmpty, contains, every, some)
- Strings: full documented functions with tests (mbstring-aware)
- Utils: tap, identity, compose, pipe with tests
- Tooling/QA: Pest, PHPStan (max), Rector, PHP-CS-Fixer, CI across Linux/macOS/Windows; Composer scripts added
- Docs: Canonical documentation in `docs/` (index, pipes, strings, utils); README updated

[Unreleased]: https://github.com/denprog5/river-flow/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/denprog5/river-flow/releases/tag/v0.1.1
[0.1.0]: https://github.com/denprog5/river-flow/releases/tag/v0.1.0
