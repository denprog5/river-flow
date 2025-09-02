# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

_Nothing yet._

## [0.3.0] - 2025-09-02

### Added
- Playground: complete coverage across Pipes, Strings, and Utils modules with concise examples demonstrating both direct and curried usage.
  - Pipes playground now includes examples for: `reduce`, `sum`, `pluck`, `reject`, `toArray`, `sortWith`, `keys`, `first`, `last`, `tail`, `init`, `find`, `count`, `isEmpty`, `contains`, `sort`, `keyBy`, `zip`, `uniqBy`, `min`, `max`, `scan`, `scanRight`, `partitionBy`, `countBy`, `aperture`, `drop`, `takeWhile`, `dropWhile`, `flatMap`, `chunk`, `range`, `repeat`, `times`, `distinctUntilChanged`, `intersperse`, and `pairwise`.
  - Strings playground expanded to cover: `includes`, `startsWith`, `endsWith`, `replace`, `slice`, `lines`, `testRegex`, `matchRegex`, `padStart`, `padEnd`.
  - Utils playground expanded to cover predicates, control/combinators, partial application and memoization, and comparators: `complement`, `both`, `either`, `allPass`, `anyPass`, `when`, `unless`, `ifElse`, `cond`, `converge`, `once`, `memoizeWith`, `partial`, `partialRight`, `ascend`, `descend`.

### Docs
- Playgrounds serve as live documentation for all exported APIs in Pipes, Strings, and Utils.

### QA
- Full suite green: Pest tests (220 tests, 462 assertions), PHPStan analyse OK, PHP-CS-Fixer dry-run OK, Rector dry-run OK.
- Verified all three playground scripts execute successfully.

### Notes
- Target PHP 8.5+. Some PHP 8.5-dev warnings (e.g., opcache already loaded) observed locally but not affecting library behavior.

## [0.2.6] - 2025-09-02

### Added
- Pipes: add lazy sequence generators `repeat(mixed $value, ?int $count = null)` and `times(int $count, ?callable $producer = null)`.
  - Eager parameter validation (throws immediately on negative counts), keys discarded; `repeat()` supports infinite mode with `null` count.

### Tests
- Add `tests/Unit/Pipes/RepeatTimesTest.php` covering finite and infinite repetition (via `take()`), producer callback mapping in `times()`, zero count yielding empty sequence, and eager exceptions for negative counts.

### Docs
- `docs/pipes.md`: document `repeat()` and `times()` in Sequence/Generation with examples and notes on laziness and key discarding.
- `README.md`: include `repeat` and `times` in the Pipes snapshot and add examples.

### QA
- Fixed PHPStan list type for `prepend()` direct path by passing `array_values($moreValues)` to `prepend_gen()`.
- Ran Composer scripts: `composer analyse`, `composer rector:fix`, `composer cs:fix`, and `composer test` — all green.

## [0.2.5] - 2025-09-01

### Added
- Pipes: add eager dual-mode `countBy()` with flexible argument order and currying. Returns a map of classifier value to count. Classifier must return an `array-key` (int|string).

### Tests
- Add `tests/Unit/Pipes/CountByTest.php` covering arrays and generators, flexible order, currying, empty input, and invalid classifier return type.

### Docs
- `docs/pipes.md`: document `countBy()` under Aggregation/Terminal, add example and imports.
- `README.md`: include `countBy` in the Pipes snapshot and add a brief example.

### QA
- To run locally: `composer test`, `composer analyse`, `composer cs:lint`, `composer rector:check`.

## [0.2.4] - 2025-09-01

### Added
- Pipes: add lazy dual-mode `distinctUntilChanged()`, `intersperse()`, and `pairwise()`

### Tests
- Add comprehensive tests for the new functions covering arrays and generators, currying, laziness, empty/single-element inputs, and selector-based distinctness

### Docs
- `docs/pipes.md`: document `distinctUntilChanged`, `intersperse`, and `pairwise` with semantics and examples
- `README.md`: add functions to the Pipes snapshot imports and examples

### QA
- All checks green: Pest, PHPStan, Rector (dry-run), PHP-CS-Fixer (dry-run)

## [0.2.3] - 2025-09-01

### Added
- Pipes: add lazy dual-mode `scan()`, `scanRight()`, and `partitionBy()`

### Tests
- Add comprehensive tests for `scan`, `scanRight`, and `partitionBy` covering associative/numeric keys, currying, generators, empty input, and invalid argument handling

### Docs
- `docs/pipes.md`: add Accumulation section (`scan`, `scanRight`) and document `partitionBy` under Combining/Windowing with examples

### QA
- All checks green: Pest, PHPStan, Rector, PHP-CS-Fixer
- Test suite: 183 tests, 392 assertions

## [0.2.2] - 2025-09-01

### Added
- Pipes: add lazy dual-mode `range()` (end-exclusive; supports positive/negative steps with eager validation)
- Pipes: add lazy dual-mode `tail()` (drops first element; preserves keys)
- Pipes: add lazy dual-mode `init()` (drops last element; preserves keys)

### Tests
- Add comprehensive tests for `range()` (positive/negative steps, floats, empty ranges, invalid step errors)
- Add comprehensive tests for `tail()` and `init()` (arrays, generators, laziness, edge cases, currying)

### Docs
- `docs/pipes.md`: document `range()`, `tail()`, `init()`; add Sequence/Generation section; expand examples and imports

## [0.2.1] - 2025-09-01

### Tests
- Pipes: add edge-case tests for `splitWhen()` invalid direct-call arguments (non-iterable first arg, non-callable predicate) and expand `chunk()` tests (currying form, size 1, empty input, invalid size in currying).
- Pipes: add tests for `zip()` confirming keys are discarded and that zipping with an empty iterable yields an empty result.
- Pipes: add tests for `partition()` invalid direct-call arguments (non-iterable first arg throws `InvalidArgumentException`; non-callable predicate results in PHP `TypeError`).

### Docs
- `docs/pipes.md`: clarify currying and error behaviors for `splitAt()`, `splitWhen()`, and `chunk()`; add a `chunk()` example in the Examples section.
- `docs/pipes.md`: document that `zip()` yields 1-length rows when only one iterable is provided and returns empty when any iterable is empty; `zipWith()` with no additional iterables behaves likewise; both continue to discard keys and rewind iterators, supporting arrays, `Iterator`, and `Traversable`/`IteratorAggregate`.
- `docs/pipes.md`: document `partition()` direct-call error handling: non-iterable first argument throws `InvalidArgumentException`; non-callable predicate in direct invocation results in PHP `TypeError`.

### QA
- Ran Composer scripts: php-cs-fixer (dry-run), PHPStan, Rector (dry-run). All OK. Import ordering adjusted in tests to satisfy fixer.

## [0.2.0] - 2025-08-15

### Breaking
- Remove `Denprog\\RiverFlow\\Strings\\trimWith()` in favor of dual-mode `trim()`

### Added / Changed
- Make `Strings\\trim()` dual-mode: direct `trim($data, $characters)` and curried `trim()` for pipes
- Docs: expand to include both pipe and non-pipe usage
  - README and docs/index.md: add section for classic composition helpers `Utils\\compose()` and `Utils\\pipe()`
  - docs/strings.md: add "Direct (non-pipe) usage" section
  - docs/pipes.md: add "Direct (non-pipe) usage" section
  - docs/utils.md: add "Classic composition (non-pipe)" with examples; unify notes
  - Minor fixes: correct namespace backslashes in examples
- Playground: ensure `trim()` and named arguments for custom charlist (e.g., `trim(characters: " -")`)

## [0.1.2] - 2025-08-15

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

[Unreleased]: https://github.com/denprog5/river-flow/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/denprog5/river-flow/releases/tag/v0.3.0
[0.2.6]: https://github.com/denprog5/river-flow/releases/tag/v0.2.6
[0.2.5]: https://github.com/denprog5/river-flow/releases/tag/v0.2.5
[0.2.4]: https://github.com/denprog5/river-flow/releases/tag/v0.2.4
[0.2.3]: https://github.com/denprog5/river-flow/releases/tag/v0.2.3
[0.2.2]: https://github.com/denprog5/river-flow/releases/tag/v0.2.2
[0.2.1]: https://github.com/denprog5/river-flow/releases/tag/v0.2.1
[0.2.0]: https://github.com/denprog5/river-flow/releases/tag/v0.2.0
[0.1.2]: https://github.com/denprog5/river-flow/releases/tag/v0.1.2
[0.1.1]: https://github.com/denprog5/river-flow/releases/tag/v0.1.1
[0.1.0]: https://github.com/denprog5/river-flow/releases/tag/v0.1.0
