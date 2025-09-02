# RiverFlow roadmap: planned functions (target v0.3.0)

This document tracks new functions planned for the next minor release. It lists only functions that are not yet implemented. Design follows project principles:

- Dual‑mode API (direct and pipe‑friendly)
- Strict typing and predictable semantics
- Laziness where it makes sense; clear key behavior
- No hidden mutation; immutable results

## Pipes — planned

- __aperture(size)__
  ```php
  // Direct
  function aperture(iterable $data, int $size): Generator<int, array<int, mixed>>
  // Curried
  function aperture(int $size): callable(iterable $data): Generator
  ```
  - Lazy sliding window of fixed size.
  - Emits only full windows; keys inside each window are numeric 0..size-1; outer keys are sequential ints.
  - Validate: size >= 1.
  - Priority: high.

- __takeLast(n)__
  ```php
  function takeLast(iterable $data, int $n): array<int, mixed>
  ```
  - Eager (materializes only up to the last n via ring buffer, then returns list with keys 0..m-1).
  - Validate: n >= 0.
  - Priority: high.

- __dropLast(n)__
  ```php
  // Direct
  function dropLast(iterable $data, int $n): Generator<array-key, mixed>
  // Curried
  function dropLast(int $n): callable(iterable $data): Generator
  ```
  - Lazy using ring buffer of size n; preserves original keys for yielded items.
  - Validate: n >= 0.
  - Priority: high.

## Strings — planned

- __matchRegex(pattern)__
  ```php
  // Direct
  function matchRegex(string $data, string $pattern, int $flags = 0): array<int|string, mixed>
  // Curried
  function matchRegex(string $pattern, int $flags = 0): callable(string $data): array
  ```
  - Wrapper over preg_match_all; safe error handling; default 'u' added if not present.
  - Returns matches structure as per PCRE with clarified shape in docs.
  - Priority: medium.

- __testRegex(pattern)__
  ```php
  // Direct
  function testRegex(string $data, string $pattern, int $flags = 0): bool
  // Curried
  function testRegex(string $pattern, int $flags = 0): callable(string $data): bool
  ```
  - Wrapper over preg_match; safe error handling; default 'u' added if not present.
  - Priority: medium.

- __padStart(len, padChar = ' ')__ / __padEnd(len, padChar = ' ')__
  ```php
  // Direct
  function padStart(string $data, int $len, string $padChar = ' '): string
  function padEnd(string $data, int $len, string $padChar = ' '): string
  // Curried
  function padStart(int $len, string $padChar = ' '): callable(string $data): string
  function padEnd(int $len, string $padChar = ' '): callable(string $data): string
  ```
  - Wrappers over str_pad; document that padChar is a single “character” (note about multibyte in docs).
  - Priority: low/medium.

## Structs (new module) — planned

- __pick(keys, from)__ / __omit(keys, from)__
  ```php
  function pick(array $keys, array|object $from): array
  function omit(array $keys, array|object $from): array
  ```
  - Reads public object properties when given objects.
  - Priority: high.

- __getPath(path, data)__ / __getPathOr(path, default, data)__
  ```php
  function getPath(array $path, array|object $data): mixed
  function getPathOr(array $path, mixed $default, array|object $data): mixed
  ```
  - Safe deep access for arrays/objects (public props); never throws for missing segments.
  - Priority: high.

- __setPath(path, value, data)__ / __updatePath(path, fn, data)__
  ```php
  function setPath(array $path, mixed $value, array $data): array
  function updatePath(array $path, callable $fn, array $data): array
  ```
  - Immutable updates (returns cloned structure with change applied).
  - Priority: high.

- __evolve(spec, data)__
  ```php
  /**
   * @param array<array-key, callable(mixed): mixed> $spec
   */
  function evolve(array $spec, array $data): array
  ```
  - Applies transformer functions from spec to corresponding keys.
  - Priority: medium.

- __zipAssoc(keys, values)__ / __unzipAssoc(pairs)__
  ```php
  function zipAssoc(array $keys, iterable $values): array
  /** @return array{0: array<int, array-key>, 1: array<int, mixed>} */
  function unzipAssoc(iterable $pairs): array
  ```
  - Explicitly typed equivalents of zipping keys with values and splitting [key,value] pairs.
  - Priority: medium.

## Notes and implementation checklist

- Validate inputs eagerly in public APIs; delegate to internal helpers for laziness where needed.
- Document laziness and key behavior precisely; include dual‑mode examples in docs.
- Add comprehensive unit tests, including currying paths and edge cases.
- QA: PHPStan (max), CS Fixer, Rector dry‑run; integrate into CI.
- Target: v0.3.0 (minor) once feature set is complete and green.
