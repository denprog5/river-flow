<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Pipes;

use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorIterator;
use Throwable;

/**
 * filter can be used as:
 *  - filter($data, $predicate): Generator
 *  - filter($predicate): callable(iterable $data): Generator
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): bool                               $data_or_predicate
 * @param  (callable(TValue, TKey): bool)|null                                               $predicate
 * @return Generator<TKey, TValue>|callable(iterable<TKey, TValue>): Generator<TKey, TValue>
 */
function filter(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => filter_gen($data, $pred);
    }

    $data = $data_or_predicate; // iterable

    return filter_gen($data, $predicate);
}

/** @internal */
function filter_gen(iterable $data, callable $predicate): Generator
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            yield $key => $value;
        }
    }
}

/**
 * map can be used as:
 *  - map($data, $transformer): Generator
 *  - map($transformer): callable(iterable $data): Generator
 *
 * @template TKey of array-key
 * @template TValue
 * @template TNewValue
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): TNewValue                                $data_or_transformer
 * @param  (callable(TValue, TKey): TNewValue)|null                                                $transformer
 * @return Generator<TKey, TNewValue>|callable(iterable<TKey, TValue>): Generator<TKey, TNewValue>
 */
function map(iterable|callable $data_or_transformer, ?callable $transformer = null): Generator|callable
{
    if (\is_callable($data_or_transformer) && $transformer === null) {
        $xf = $data_or_transformer;

        return static fn (iterable $data): Generator => map_gen($data, $xf);
    }

    $data = $data_or_transformer; // iterable

    return map_gen($data, $transformer);
}

/** @internal */
function map_gen(iterable $data, callable $transformer): Generator
{
    foreach ($data as $key => $value) {
        yield $key => $transformer($value, $key);
    }
}

/**
 * reduce supports currying:
 *  - reduce($data, $reducer, $initial = null): mixed
 *  - reduce($reducer, $initial = null): callable(iterable $data): mixed
 */
function reduce(iterable|callable $data_or_reducer, mixed $reducer_or_initial = null, mixed $initial = null): mixed
{
    // Currying path: reduce($reducer, $initial?) -> callable(iterable $data): mixed
    if (\is_callable($data_or_reducer)) {
        $r    = $data_or_reducer;
        $init = $reducer_or_initial;

        return static fn (iterable $data): mixed => reduce($data, $r, $init);
    }

    // Direct path: reduce($data, $reducer, $initial)
    $data    = $data_or_reducer; // iterable
    $reducer = $reducer_or_initial;
    if (!\is_callable($reducer)) {
        throw new InvalidArgumentException('reduce(): reducer must be callable');
    }

    $carry = $initial;
    foreach ($data as $key => $value) {
        $carry = $reducer($carry, $value, $key);
    }

    return $carry;
}

/**
 * Sum numeric values. Currying supported via sum() -> callable.
 *
 * @param iterable<int|float|string|bool|null>|null $data
 */
function sum(?iterable $data = null): int|float|callable
{
    if ($data === null) {
        return static fn (iterable $d): int|float => sum($d);
    }

    $total = 0;
    foreach ($data as $value) {
        if ($value === true) {
            $total += 1;
            continue;
        }
        if ($value === false || $value === null) {
            continue; // add 0
        }
        if (\is_int($value) || \is_float($value)) {
            $total += $value;
        } elseif (is_numeric($value)) {
            $total += $value + 0; // numeric string to number
        }
    }

    return $total;
}

/**
 * @template TKey of array-key
 * @template TValue
 * @return Generator<TKey, TValue>|callable(iterable<TKey, TValue>): Generator<TKey, TValue>
 */
function take(iterable|int $data_or_count, ?int $count = null): Generator|callable
{
    if (!is_iterable($data_or_count)) {
        $n = $data_or_count;

        return static fn (iterable $data): Generator => take_gen($data, $n);
    }

    $data = $data_or_count;
    $n    = (int) $count;

    return take_gen($data, $n);
}

/** @internal */
function take_gen(iterable $data, int $count): Generator
{
    if ($count <= 0) {
        return;
    }

    $taken = 0;
    foreach ($data as $key => $value) {
        yield $key => $value;
        $taken++;
        if ($taken >= $count) {
            break;
        }
    }
}

/**
 * @template TKey of array-key
 * @template TValue of array|object
 * @return Generator<TKey, mixed>|callable(iterable<TKey, TValue>): Generator<TKey, mixed>
 */
function pluck(iterable|string|int $data_or_key, mixed $key_or_default = null, mixed $default = null): Generator|callable
{
    if (!is_iterable($data_or_key)) {
        $key = $data_or_key;
        $def = $key_or_default;

        return static fn (iterable $data): Generator => pluck_gen($data, $key, $def);
    }

    $data = $data_or_key;
    $key  = $key_or_default;

    return pluck_gen($data, $key, $default);
}

/** @internal */
function pluck_gen(iterable $data, string|int $key, mixed $default = null): Generator
{
    $prop = (string) $key;
    foreach ($data as $k => $item) {
        if (\is_array($item)) {
            yield $k => (\array_key_exists($key, $item) ? $item[$key] : $default);
        } else {
            $public = get_object_vars($item);
            yield $k => (\array_key_exists($prop, $public) ? $public[$prop] : $default);
        }
    }
}

/**
 * toList($data): array, or toList(): callable
 */
function toList(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static fn (iterable $d): array => toList($d);
    }

    $out = [];
    foreach ($data as $value) {
        $out[] = $value;
    }

    return $out;
}

/**
 * toArray($data): array, or toArray(): callable
 */
function toArray(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static fn (iterable $d): array => toArray($d);
    }

    $out = [];
    foreach ($data as $k => $v) {
        $out[$k] = $v;
    }

    return $out;
}

/**
 * Reject elements for which predicate returns true.
 *
 * @template TKey of array-key
 * @template TValue
 * @param  callable(TValue, TKey): bool $predicate
 * @return Generator<TKey, TValue>
 */
function reject(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => reject_gen($data, $pred);
    }

    $data = $data_or_predicate; // iterable

    return reject_gen($data, $predicate);
}

/** @internal */
function reject_gen(iterable $data, callable $predicate): Generator
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === false) {
            yield $key => $value;
        }
    }
}

/**
 * sortBy can be used as:
 *  - sortBy($data, $getComparable): array
 *  - sortBy($getComparable, $data): array  (flexible order)
 *  - sortBy($getComparable): callable(iterable $data): array
 *
 * @template TKey of array-key
 * @template TValue
 * @template TSort of (int|float|string)
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): TSort                      $data_or_getComparable
 * @param  (callable(TValue, TKey): TSort)|iterable<TKey, TValue>|null               $maybe_data
 * @return array<TKey, TValue>|callable(iterable<TKey, TValue>): array<TKey, TValue>
 */
function sortBy(iterable|callable $data_or_getComparable, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_getComparable) && $maybe_data === null) {
        $xf = $data_or_getComparable;

        return static fn (iterable $data): array => sortBy($data, $xf);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_getComparable) && $maybe_data !== null) {
        $xf   = $data_or_getComparable;
        $data = $maybe_data; // iterable

        return sortBy($data, $xf);
    }

    // Normal order
    $data          = $data_or_getComparable; // iterable
    $getComparable = $maybe_data;            // callable

    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[$key] = [$value, $getComparable($value, $key)];
    }

    uasort($pairs, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

    return array_map(fn (array $pair): mixed => $pair[0], $pairs);
}

/**
 * Yield values (discard keys) lazily.
 *
 * @template T
 * @param  iterable<mixed, T>                                                $data
 * @return Generator<int, T>|callable(iterable<mixed, T>): Generator<int, T>
 */
function values(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => values_gen($d);
    }

    return values_gen($data);
}

/** @internal */
function values_gen(iterable $data): Generator
{
    foreach ($data as $value) {
        yield $value;
    }
}

/**
 * Yield keys lazily.
 *
 * @template TKey of array-key
 * @param  iterable<TKey, mixed>                                                      $data
 * @return Generator<int, TKey>|callable(iterable<TKey, mixed>): Generator<int, TKey>
 */
function keys(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => keys_gen($d);
    }

    return keys_gen($data);
}

/** @internal */
function keys_gen(iterable $data): Generator
{
    foreach ($data as $key => $unused) {
        yield $key;
    }
}

/**
 * Get the first element of an iterable, or $default if empty.
 *
 * Dual-mode:
 *  - first($data, $default): mixed
 *  - first($default): callable(iterable $data): mixed
 */
function first(mixed $data_or_default = null, mixed $default = null): mixed
{
    if (!is_iterable($data_or_default)) {
        $def = $data_or_default;

        return static fn (iterable $data): mixed => first($data, $def);
    }

    $data = $data_or_default;
    foreach ($data as $value) {
        return $value;
    }

    return $default;
}

/**
 * Get the last element of an iterable, or $default if empty.
 * Eager by necessity.
 *
 * Dual-mode:
 *  - last($data, $default): mixed
 *  - last($default): callable(iterable $data): mixed
 */
function last(mixed $data_or_default = null, mixed $default = null): mixed
{
    if (!is_iterable($data_or_default)) {
        $def = $data_or_default;

        return static fn (iterable $data): mixed => last($data, $def);
    }

    $data  = $data_or_default;
    $found = false;
    $last  = null;
    foreach ($data as $value) {
        $last  = $value;
        $found = true;
    }

    return $found ? $last : $default;
}

/**
 * Find first element matching predicate, or $default if none found.
 *
 * @template TKey of array-key
 * @template T
 * @param  callable(T, TKey): bool $predicate
 * @param  T|null                  $default
 * @return T|null
 */
function find(iterable|callable $data_or_predicate, ?callable $predicate = null, mixed $default = null): mixed
{
    if (\is_callable($data_or_predicate)) {
        $pred = $data_or_predicate;
        $def  = $predicate; // allow find($pred, $default) -> callable

        return static fn (iterable $data): mixed => find($data, $pred, $def);
    }

    $data = $data_or_predicate;
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            return $value;
        }
    }

    return $default;
}

/**
 * Count elements in an iterable (eager).
 *
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue> $data
 */
function count(?iterable $data = null): int|callable
{
    if ($data === null) {
        return static fn (iterable $d): int => count($d);
    }

    if (\is_array($data)) {
        return \count($data);
    }
    $n = 0;
    foreach ($data as $_) {
        $n++;
    }

    return $n;
}

/**
 * Whether iterable has no elements.
 *
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue> $data
 */
function isEmpty(?iterable $data = null): bool|callable
{
    if ($data === null) {
        return static fn (iterable $d): bool => isEmpty($d);
    }

    foreach ($data as $_) {
        return false;
    }

    return true;
}

/**
 * Check whether iterable contains a value (strict comparison).
 *
 * @template TKey of array-key
 * @template TValue
 */
function contains(mixed $data_or_needle, mixed $needle = null): bool|callable
{
    if (!is_iterable($data_or_needle)) {
        $nd = $data_or_needle;

        return static fn (iterable $data): bool => contains($data, $nd);
    }

    $data = $data_or_needle;
    foreach ($data as $value) {
        if ($value === $needle) {
            return true;
        }
    }

    return false;
}

/**
 * Sort values using natural ascending order, preserving original keys.
 * Eager.
 *
 * @template TKey of array-key
 * @template TValue of (int|float|string)
 * @param  iterable<TKey, TValue> $data
 * @return array<TKey, TValue>
 */
function sort(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static fn (iterable $d): array => sort($d);
    }

    $arr = toArray($data);
    uasort($arr, static fn (int|float|string $a, int|float|string $b): int => $a <=> $b);

    return $arr;
}

/**
 * groupBy can be used as:
 *  - groupBy($data, $grouper): array
 *  - groupBy($grouper, $data): array (flexible order)
 *  - groupBy($grouper): callable(iterable $data): array
 * Eager.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TGroupKey of array-key
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): TGroupKey                                                      $data_or_grouper
 * @param  (callable(TValue, TKey): TGroupKey)|iterable<TKey, TValue>|null                                               $maybe_data
 * @return array<TGroupKey, array<TKey, TValue>>|callable(iterable<TKey, TValue>): array<TGroupKey, array<TKey, TValue>>
 */
function groupBy(iterable|callable $data_or_grouper, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_grouper) && $maybe_data === null) {
        $g = $data_or_grouper;

        return static fn (iterable $data): array => groupBy($data, $g);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_grouper) && $maybe_data !== null) {
        $g    = $data_or_grouper;
        $data = $maybe_data; // iterable

        return groupBy($data, $g);
    }

    // Normal order
    $data    = $data_or_grouper; // iterable
    $grouper = $maybe_data;      // callable

    $out = [];
    foreach ($data as $key => $value) {
        $group = $grouper($value, $key);
        if (!\array_key_exists($group, $out)) {
            $out[$group] = [];
        }
        $out[$group][$key] = $value;
    }

    return $out;
}

/**
 * keyBy can be used as:
 *  - keyBy($data, $keyer): array
 *  - keyBy($keyer, $data): array (flexible order)
 *  - keyBy($keyer): callable(iterable $data): array
 * Eager. Later keys overwrite earlier ones.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TNewKey of array-key
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): TNewKey                          $data_or_keyer
 * @param  (callable(TValue, TKey): TNewKey)|iterable<TKey, TValue>|null                   $maybe_data
 * @return array<TNewKey, TValue>|callable(iterable<TKey, TValue>): array<TNewKey, TValue>
 */
function keyBy(iterable|callable $data_or_keyer, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_keyer) && $maybe_data === null) {
        $k = $data_or_keyer;

        return static fn (iterable $data): array => keyBy($data, $k);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_keyer) && $maybe_data !== null) {
        $k    = $data_or_keyer;
        $data = $maybe_data; // iterable

        return keyBy($data, $k);
    }

    // Normal order
    $data  = $data_or_keyer; // iterable
    $keyer = $maybe_data;    // callable

    $out = [];
    foreach ($data as $key => $value) {
        $out[$keyer($value, $key)] = $value;
    }

    return $out;
}

/**
 * Average of numeric values. Non-numeric values are handled like in sum().
 * Returns 0.0 when iterable is empty.
 * Eager. Supports currying for pipe usage.
 *
 * @param  iterable<array-key, int|float|string|bool|null>|null                   $data
 * @return float|callable(iterable<array-key, int|float|string|bool|null>): float
 */
function average(?iterable $data = null): float|callable
{
    if ($data === null) {
        return static fn (iterable $d): float => average($d);
    }

    $total = 0.0;
    $n     = 0;
    foreach ($data as $value) {
        $n++;
        if ($value === true) {
            $total += 1;
            continue;
        }
        if ($value === false || $value === null) {
            continue; // add 0
        }
        if (\is_int($value) || \is_float($value)) {
            $total += $value;
        } elseif (is_numeric($value)) {
            $total += $value + 0; // numeric string to number
        }
    }

    return $n === 0 ? 0.0 : $total / $n;
}

/**
 * Whether all elements satisfy the predicate. Lazy short-circuit, eager result.
 * For empty iterables returns true.
 *
 * @template TKey of array-key
 * @template TValue
 * @param callable(TValue, TKey): bool $predicate
 */
function every(iterable|callable $data_or_predicate, ?callable $predicate = null): bool|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): bool => every($data, $pred);
    }

    $data = $data_or_predicate;
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) !== true) {
            return false;
        }
    }

    return true;
}

/**
 * Whether any element satisfies the predicate. Lazy short-circuit, eager result.
 * For empty iterables returns false.
 *
 * @template TKey of array-key
 * @template TValue
 * @param callable(TValue, TKey): bool $predicate
 */
function some(iterable|callable $data_or_predicate, ?callable $predicate = null): bool|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): bool => some($data, $pred);
    }

    $data = $data_or_predicate;
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            return true;
        }
    }

    return false;
}

/**
 * Lazily return unique items based on the value itself. Keys of first occurrence are preserved.
 *
 * Scalars/bool/null compared by value and type; objects by spl_object_hash; arrays by serialize().
 * When a value cannot be hashed reliably (e.g., arrays containing closures), the element is skipped.
 *
 * Supports currying for pipe usage: uniq() -> callable(iterable): Generator
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>|null                                                       $data
 * @return Generator<TKey, TValue>|callable(iterable<TKey, TValue>): Generator<TKey, TValue>
 */
function uniq(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => uniq_gen($d);
    }

    return uniq_gen($data);
}

/** @internal */
function uniq_gen(iterable $data): Generator
{
    $seen = [];
    foreach ($data as $key => $value) {
        [$ok, $hash] = __hash_identifier($value);
        if (!$ok) {
            continue;
        }
        if (!isset($seen[$hash])) {
            $seen[$hash] = true;
            yield $key => $value;
        }
    }
}

/**
 * Lazily return unique items based on identifier($value, $key). Keys of first occurrence are preserved.
 *
 * Identifier hashing rules follow uniq(). Items with unhashable identifiers are skipped.
 *
 * Supports currying for pipe usage: uniqBy($identifier) -> callable(iterable): Generator
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>|callable(TValue, TKey): mixed                              $data_or_identifier
 * @param  (callable(TValue, TKey): mixed)|null                                              $identifier
 * @return Generator<TKey, TValue>|callable(iterable<TKey, TValue>): Generator<TKey, TValue>
 */
function uniqBy(iterable|callable $data_or_identifier, iterable|callable|null $identifier = null): Generator|callable
{
    if (\is_callable($data_or_identifier) && $identifier === null) {
        $ident = $data_or_identifier;

        return static fn (iterable $data): Generator => uniqBy_gen($data, $ident);
    }

    // Flexible order: uniqBy($identifier, $data)
    if (\is_callable($data_or_identifier) && $identifier !== null && is_iterable($identifier)) {
        $ident = $data_or_identifier;
        $data  = $identifier;

        return uniqBy_gen($data, $ident);
    }

    $data = $data_or_identifier; // iterable

    return uniqBy_gen($data, $identifier);
}

/** @internal */
function uniqBy_gen(iterable $data, callable $identifier): Generator
{
    $seen = [];
    foreach ($data as $key => $value) {
        $id          = $identifier($value, $key);
        [$ok, $hash] = __hash_identifier($id);
        if (!$ok) {
            continue;
        }
        if (!isset($seen[$hash])) {
            $seen[$hash] = true;
            yield $key => $value;
        }
    }
}

/**
 * Flatten nested iterables up to $depth levels. Keys are discarded.
 * Depth 0 returns a generator over the original elements (keys lost).
 *
 * Dual-mode:
 * - flatten($iterable, $depth = 1): Generator
 * - flatten($depth = 1): callable(iterable): Generator
 *
 * @param  iterable<mixed, mixed>|int|null                                               $data_or_depth
 * @return Generator<int, mixed>|callable(iterable<mixed, mixed>): Generator<int, mixed>
 */
function flatten(iterable|int|null $data_or_depth = null, ?int $depth = null): Generator|callable
{
    if (!is_iterable($data_or_depth)) {
        $d = $data_or_depth ?? 1;

        return static fn (iterable $data): Generator => flatten_gen($data, $d);
    }

    $data = $data_or_depth;
    $d    = $depth ?? 1;

    return flatten_gen($data, $d);
}

/** @internal */
function flatten_gen(iterable $data, int $depth): Generator
{
    if ($depth <= 0) {
        foreach (values($data) as $v) {
            // Re-yield to discard original keys and avoid collisions
            yield $v;
        }

        return;
    }

    foreach ($data as $item) {
        if (is_iterable($item)) {
            // Recurse deeper by one level; re-yield values to discard inner keys
            foreach (flatten_gen($item, $depth - 1) as $v) {
                yield $v;
            }
        } else {
            yield $item;
        }
    }
}

/**
 * Map each element to an iterable and flatten by one level (lazy). Keys are discarded.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TNewValue
 * @param  callable(TValue, TKey): (iterable<mixed, TNewValue>|TNewValue) $transformer
 * @return Generator<int, TNewValue>
 */
function flatMap(iterable|callable $data_or_transformer, ?callable $transformer = null): Generator|callable
{
    if (\is_callable($data_or_transformer) && $transformer === null) {
        $xf = $data_or_transformer;

        return static fn (iterable $data): Generator => flatMap_gen($data, $xf);
    }

    $data = $data_or_transformer; // iterable

    return flatMap_gen($data, $transformer);
}

/** @internal */
function flatMap_gen(iterable $data, callable $transformer): Generator
{
    foreach ($data as $key => $value) {
        $result = $transformer($value, $key);
        if (is_iterable($result)) {
            foreach ($result as $v) {
                yield $v;
            }
        } else {
            yield $result;
        }
    }
}

/**
 * Take elements while predicate returns true (lazy).
 * Supports currying.
 */
function takeWhile(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => takeWhile_gen($data, $pred);
    }

    $data = $data_or_predicate;

    return takeWhile_gen($data, $predicate);
}

/** @internal */
function takeWhile_gen(iterable $data, callable $predicate): Generator
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) !== true) {
            break;
        }
        yield $key => $value;
    }
}

/**
 * Skip the first $count elements, yielding the rest (lazy).
 *
 * @template TKey of array-key
 * @template TValue
 * @return Generator<TKey, TValue>|callable(iterable<TKey, TValue>): Generator<TKey, TValue>
 */
function drop(iterable|int $data_or_count, ?int $count = null): Generator|callable
{
    if (!is_iterable($data_or_count)) {
        $n = $data_or_count;

        return static fn (iterable $data): Generator => drop_gen($data, $n);
    }

    $data = $data_or_count;
    $n    = (int) $count;

    return drop_gen($data, $n);
}

/** @internal */
function drop_gen(iterable $data, int $count): Generator
{
    if ($count <= 0) {
        yield from $data;

        return;
    }

    $skipped = 0;
    foreach ($data as $key => $value) {
        if ($skipped < $count) {
            $skipped++;
            continue;
        }
        yield $key => $value;
    }
}

/**
 * Skip elements while predicate is true, then yield the rest (lazy).
 * Supports currying.
 */
function dropWhile(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => dropWhile_gen($data, $pred);
    }

    $data = $data_or_predicate;

    return dropWhile_gen($data, $predicate);
}

/** @internal */
function dropWhile_gen(iterable $data, callable $predicate): Generator
{
    $dropping = true;
    foreach ($data as $key => $value) {
        if ($dropping) {
            if ($predicate($value, $key) === true) {
                continue;
            }
            $dropping = false;
        }
        yield $key => $value;
    }
}

/**
 * Partition into two arrays: [pass, fail] according to predicate. Eager. Keys preserved.
 *
 * @template TKey of array-key
 * @template TValue
 * @param  callable(TValue, TKey): bool                          $predicate
 * @return array{0: array<TKey, TValue>, 1: array<TKey, TValue>}
 */
function partition(iterable|callable $data_or_predicate, ?callable $predicate = null): array|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): array => partition($data, $pred);
    }

    if (\is_callable($data_or_predicate) && $predicate !== null) {
        $pred = $data_or_predicate;
        $data = $predicate; // iterable

        return partition($data, $pred);
    }

    $data = $data_or_predicate;
    $pass = [];
    $fail = [];
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            $pass[$key] = $value;
        } else {
            $fail[$key] = $value;
        }
    }

    return [$pass, $fail];
}

/**
 * Zip multiple iterables together lazily. Stops at the shortest.
 * Yields numeric-indexed arrays of values, keys discarded.
 *
 * @param  iterable<mixed, mixed>            $data      The primary iterable (convenient for pipe usage)
 * @param  iterable<mixed, mixed>            ...$others Other iterables to zip with
 * @return Generator<int, array<int, mixed>>
 */
function zip(iterable $data, iterable ...$others): Generator
{
    $iterables = [$data, ...$others];
    $iters     = [];
    foreach ($iterables as $it) {
        if (\is_array($it)) {
            $iters[] = new ArrayIterator($it);
        } elseif ($it instanceof Iterator) {
            $iters[] = $it;
        } else {
            // Remaining case: Traversable (e.g., IteratorAggregate)
            $iters[] = new IteratorIterator($it);
        }
    }

    // Rewind all
    foreach ($iters as $it) {
        $it->rewind();
    }

    while (true) {
        foreach ($iters as $it) {
            if (!$it->valid()) {
                return;
            }
        }

        $row = [];
        foreach ($iters as $it) {
            $row[] = $it->current();
        }
        yield $row;
        foreach ($iters as $iter) {
            $iter->next();
        }
    }
}

/**
 * Chunk values into arrays of size $size (last chunk may be smaller). Lazy. Keys discarded.
 *
 * @return Generator<int, array<int, mixed>>|callable(iterable<array-key, mixed>): Generator<int, array<int, mixed>>
 */
function chunk(iterable|int $data_or_size, ?int $size = null): Generator|callable
{
    if (!is_iterable($data_or_size)) {
        $sz = $data_or_size;

        return static fn (iterable $data): Generator => chunk_gen($data, $sz);
    }

    $data = $data_or_size;
    $sz   = (int) $size;

    return chunk_gen($data, $sz);
}

/** @internal */
function chunk_gen(iterable $data, int $size): Generator
{
    if ($size <= 0) {
        throw new InvalidArgumentException('chunk() size must be >= 1');
    }

    $buf = [];
    foreach ($data as $value) {
        $buf[] = $value;
        if (\count($buf) >= $size) {
            yield $buf;
            $buf = [];
        }
    }
    if ($buf !== []) {
        yield $buf;
    }
}

/**
 * Minimum of comparable values. Returns null for empty input.
 * Eager. Supports currying: min() -> callable(iterable): mixed
 */
function min(?iterable $data = null): int|float|string|null|callable
{
    if ($data === null) {
        return static fn (iterable $d): int|float|string|null => min($d);
    }

    $found = false;
    $min   = null;
    foreach ($data as $value) {
        if (!$found) {
            $min   = $value;
            $found = true;
        } elseif ($value < $min) {
            $min = $value;
        }
    }

    return $min;
}

/**
 * Maximum of comparable values. Returns null for empty input.
 * Eager. Supports currying: max() -> callable(iterable): mixed
 */
function max(?iterable $data = null): int|float|string|null|callable
{
    if ($data === null) {
        return static fn (iterable $d): int|float|string|null => max($d);
    }

    $found = false;
    $max   = null;
    foreach ($data as $value) {
        if (!$found) {
            $max   = $value;
            $found = true;
        } elseif ($value > $max) {
            $max = $value;
        }
    }

    return $max;
}

/**
 * Internal: normalize an identifier to a stable hash string.
 * Returns [true, hash] on success, [false, ''] if the value cannot be reliably hashed.
 *
 * @return array{0: bool, 1: string}
 */
function __hash_identifier(mixed $value): array
{
    if ($value === null) {
        return [true, 'n'];
    }
    if (\is_bool($value)) {
        return [true, 'b:' . ($value ? '1' : '0')];
    }
    if (\is_int($value)) {
        return [true, 'i:' . $value];
    }
    if (\is_float($value)) {
        if (is_nan($value)) {
            return [true, 'f:nan'];
        }
        if (is_infinite($value)) {
            return [true, 'f:' . ($value > 0 ? 'inf' : '-inf')];
        }

        return [true, 'f:' . rtrim(\sprintf('%.17F', $value), '0')];
    }
    if (\is_string($value)) {
        return [true, 's:' . $value];
    }
    if (\is_object($value)) {
        // Generators/Closures are not serializable but have object hashes
        return [true, 'o:' . spl_object_hash($value)];
    }
    if (\is_array($value)) {
        // Try serialize safely; skip if it fails
        try {
            return [true, 'a:' . serialize($value)];
        } catch (Throwable) {
            return [false, ''];
        }
    }

    // Resources, etc.
    return [false, ''];
}
