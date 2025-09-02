<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Structs;

use InvalidArgumentException;

/**
 * Pick a subset of keys from an array or public props from an object.
 *
 * Dual-mode:
 *  - pick($keys, $from): array
 *  - pick($keys): callable(array|object $from): array
 *
 * @param array<int, int|string> $keys
 * @phpstan-param array<array-key, mixed>|object|null $from
 * @return array<array-key, mixed>|callable(array<array-key, mixed>|object): array<array-key, mixed>
 * @phpstan-return array<array-key, mixed>|callable(array<array-key, mixed>|object): array<array-key, mixed>
 */
function pick(array $keys, array|object|null $from = null): array|callable
{
    if ($from === null) {
        return static fn (array|object $data): array => pick_impl($keys, $data);
    }

    return pick_impl($keys, $from);
}

/**
 * @param array<int, int|string> $keys
 * @phpstan-param array<int|string, mixed>|object $from
 * @return array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>
 * @internal
 */
function pick_impl(array $keys, array|object $from): array
{
    if (\is_array($from)) {
        $out = [];
        foreach ($keys as $key) {
            if (\array_key_exists($key, $from)) {
                $out[$key] = $from[$key];
            }
        }

        return $out;
    }

    $props = get_object_vars($from);
    $out   = [];
    foreach ($keys as $key) {
        $ks = (string) $key;
        if (\array_key_exists($ks, $props)) {
            $out[$ks] = $props[$ks];
        }
    }

    return $out;
}

/**
 * Omit a set of keys from an array or public props of an object.
 *
 * Dual-mode:
 *  - omit($keys, $from): array
 *  - omit($keys): callable(array|object $from): array
 *
 * @param array<int, int|string> $keys
 * @phpstan-param array<array-key, mixed>|object|null $from
 * @return array<array-key, mixed>|callable(array<array-key, mixed>|object): array<array-key, mixed>
 * @phpstan-return array<array-key, mixed>|callable(array<array-key, mixed>|object): array<array-key, mixed>
 */
function omit(array $keys, array|object|null $from = null): array|callable
{
    if ($from === null) {
        return static fn (array|object $data): array => omit_impl($keys, $data);
    }

    return omit_impl($keys, $from);
}

/**
 * @param array<int, int|string> $keys
 * @phpstan-param array<int|string, mixed>|object $from
 * @return array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>
 * @internal
 */
function omit_impl(array $keys, array|object $from): array
{
    $set = [];
    foreach ($keys as $k) {
        // store both raw and string-cast to match object prop names and array keys
        $set[$k]          = true;
        $set[(string) $k] = true;
    }

    if (\is_array($from)) {
        $out = [];
        foreach ($from as $k => $v) {
            if (!\array_key_exists($k, $set)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    $props = get_object_vars($from);
    $out   = [];
    foreach ($props as $k => $v) {
        if (!\array_key_exists($k, $set)) {
            $out[$k] = $v;
        }
    }

    return $out;
}

/**
 * Safe deep access into arrays/objects (public props). Returns null when path missing.
 *
 * Dual-mode:
 *  - getPath($path, $data): mixed
 *  - getPath($path): callable(array|object $data): mixed
 *
 * @param array<int, int|string> $path
 * @phpstan-param array<int, int|string> $path
 * @phpstan-param array<int|string, mixed>|object|null $data
 */
function getPath(array $path, array|object|null $data = null): mixed
{
    if ($data === null) {
        return static fn (array|object $d): mixed => getPath_impl($path, $d);
    }

    return getPath_impl($path, $data);
}

/**
 * @param array<int, int|string> $path
 * @phpstan-param array<int, int|string> $path
 * @phpstan-param array<int|string, mixed>|object $data
 * @internal
 */
function getPath_impl(array $path, array|object $data): mixed
{
    $cur = $data;
    if ($path === []) {
        return $cur;
    }

    foreach ($path as $seg) {
        if (\is_array($cur)) {
            if (!\array_key_exists($seg, $cur)) {
                return null;
            }
            $cur = $cur[$seg];
            continue;
        }

        if (!\is_object($cur)) {
            return null;
        }
        $props = get_object_vars($cur);
        $key   = (string) $seg;
        if (!\array_key_exists($key, $props)) {
            return null;
        }
        $cur = $props[$key];
    }

    return $cur;
}

/**
 * Safe deep access with default when path missing.
 *
 * Dual-mode:
 *  - getPathOr($path, $default, $data): mixed
 *  - getPathOr($path, $default): callable(array|object $data): mixed
 *
 * @param array<int, int|string> $path
 * @phpstan-param array<int, int|string> $path
 * @phpstan-param array<int|string, mixed>|object|null $data
 */
function getPathOr(array $path, mixed $default, array|object|null $data = null): mixed
{
    if ($data === null) {
        return static fn (array|object $d): mixed => getPathOr_impl($path, $default, $d);
    }

    return getPathOr_impl($path, $default, $data);
}

/**
 * @param array<int, int|string> $path
 * @phpstan-param array<int, int|string> $path
 * @phpstan-param array<int|string, mixed>|object $data
 * @internal
 */
function getPathOr_impl(array $path, mixed $default, array|object $data): mixed
{
    $val = getPath_impl($path, $data);

    return $val ?? $default;
}

/**
 * Immutable deep set into arrays (creates nested arrays as needed).
 *
 * Dual-mode:
 *  - setPath($path, $value, $data): array
 *  - setPath($path, $value): callable(array $data): array
 *
 * @param array<int, int|string> $path
 * @param array<int|string, mixed> $data
 * @phpstan-param array<int|string, mixed>|null $data
 * @return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 */
function setPath(array $path, mixed $value, array|null $data = null): array|callable
{
    if ($data === null) {
        return static fn (array $d): array => setPath_impl($path, $value, $d);
    }

    return setPath_impl($path, $value, $data);
}

/**
 * @param array<int, int|string> $path
 * @param array<int|string, mixed> $data
 * @return array<int|string, mixed>
 * @internal
 */
function setPath_impl(array $path, mixed $value, array $data): array
{
    if ($path === []) {
        throw new InvalidArgumentException('setPath(): path must not be empty');
    }

    $result = $data;
    $ref    = & $result;
    $last   = array_key_last($path);
    foreach ($path as $i => $seg) {
        if ($i === $last) {
            $ref[$seg] = $value;
            break;
        }
        if (!\is_array($ref[$seg] ?? null)) {
            $ref[$seg] = [];
        }
        $ref = & $ref[$seg];
    }

    return $result;
}

/**
 * Immutable deep update into arrays using $fn (receives current or null when missing).
 *
 * Dual-mode:
 *  - updatePath($path, $fn, $data): array
 *  - updatePath($path, $fn): callable(array $data): array
 *
 * @param array<int, int|string> $path
 * @param array<int|string, mixed> $data
 * @phpstan-param array<int|string, mixed>|null $data
 * @return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 */
function updatePath(array $path, callable $fn, array|null $data = null): array|callable
{
    if ($data === null) {
        return static fn (array $d): array => updatePath_impl($path, $fn, $d);
    }

    return updatePath_impl($path, $fn, $data);
}

/**
 * @param array<int, int|string> $path
 * @param array<int|string, mixed> $data
 * @return array<int|string, mixed>
 * @internal
 */
function updatePath_impl(array $path, callable $fn, array $data): array
{
    if ($path === []) {
        throw new InvalidArgumentException('updatePath(): path must not be empty');
    }

    $result = $data;
    $ref    = & $result;
    $last   = array_key_last($path);
    foreach ($path as $i => $seg) {
        if ($i === $last) {
            $cur       = $ref[$seg] ?? null;
            $ref[$seg] = $fn($cur);
            break;
        }
        if (!\is_array($ref[$seg] ?? null)) {
            $ref[$seg] = [];
        }
        $ref = & $ref[$seg];
    }

    return $result;
}

/**
 * Apply transformer functions from spec to corresponding keys.
 * Only keys present in $data are transformed.
 *
 * Dual-mode:
 *  - evolve($spec, $data): array
 *  - evolve($spec): callable(array $data): array
 *
 * @param array<array-key, callable(mixed): mixed> $spec
 * @param array<int|string, mixed> $data
 * @phpstan-param array<int|string, mixed>|null $data
 * @return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>|callable(array<int|string, mixed>): array<int|string, mixed>
 */
function evolve(array $spec, array|null $data = null): array|callable
{
    if ($data === null) {
        return static fn (array $d): array => evolve_impl($spec, $d);
    }

    return evolve_impl($spec, $data);
}

/**
 * @param array<array-key, callable(mixed): mixed> $spec
 * @param array<int|string, mixed> $data
 * @return array<int|string, mixed>
 * @internal
 */
function evolve_impl(array $spec, array $data): array
{
    $out = $data;
    foreach ($spec as $k => $fn) {
        if (\array_key_exists($k, $out)) {
            /** @var callable(mixed): mixed $fn */
            $out[$k] = $fn($out[$k]);
        }
    }

    return $out;
}

/**
 * Zip keys with values into an associative array.
 *
 * Dual-mode:
 *  - zipAssoc($keys, $values): array
 *  - zipAssoc($keys): callable(iterable $values): array
 *
 * @param array<int, int|string> $keys
 * @param iterable<mixed> $values
 * @phpstan-param iterable<mixed>|null $values
 * @return array<int|string, mixed>|callable(iterable<mixed>): array<int|string, mixed>
 * @phpstan-return array<int|string, mixed>|callable(iterable<mixed>): array<int|string, mixed>
 */
function zipAssoc(array $keys, iterable|null $values = null): array|callable
{
    if ($values === null) {
        return static fn (iterable $vals): array => zipAssoc_impl($keys, $vals);
    }

    return zipAssoc_impl($keys, $values);
}

/**
 * @param array<int, int|string> $keys
 * @param iterable<mixed> $values
 * @return array<int|string, mixed>
 * @internal
 */
function zipAssoc_impl(array $keys, iterable $values): array
{
    $out = [];
    $i   = 0;
    foreach ($values as $value) {
        if (!\array_key_exists($i, $keys)) {
            break;
        }
        $out[$keys[$i]] = $value;
        $i++;
    }

    return $out;
}

/**
 * Split [key, value] pairs into two arrays [keys, values].
 *
 * @param iterable<mixed> $pairs
 * @return array{0: array<int, int|string>, 1: array<int, mixed>}
 */
function unzipAssoc(iterable $pairs): array
{
    $keys = [];
    $vals = [];
    foreach ($pairs as $pair) {
        if (\is_array($pair) && \array_key_exists(0, $pair) && \array_key_exists(1, $pair)) {
            /** @var int|string $k */
            $k      = $pair[0];
            $keys[] = $k;
            $vals[] = $pair[1];
        }
    }

    return [$keys, $vals];
}
