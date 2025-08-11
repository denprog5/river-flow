<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Pipes;

use Generator;

/**
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>       $data
 * @param  callable(TValue, TKey): bool $predicate
 * @return Generator<TKey, TValue>
 */
function filter(iterable $data, callable $predicate): Generator
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            yield $key => $value;
        }
    }
}

/**
 * @template TKey of array-key
 * @template TValue
 * @template TNewValue
 * @param  iterable<TKey, TValue>            $data
 * @param  callable(TValue, TKey): TNewValue $transformer
 * @return Generator<TKey, TNewValue>
 */
function map(iterable $data, callable $transformer): Generator
{
    foreach ($data as $key => $value) {
        yield $key => $transformer($value, $key);
    }
}

/**
 * @template TKey of array-key
 * @template TValue
 * @template TCarry
 * @param  iterable<TKey, TValue>                      $data
 * @param  callable(TCarry|null, TValue, TKey): TCarry $reducer
 * @param  TCarry|null                                 $initial
 * @return TCarry|null
 */
function reduce(iterable $data, callable $reducer, mixed $initial = null): mixed
{
    $carry = $initial;
    foreach ($data as $key => $value) {
        $carry = $reducer($carry, $value, $key);
    }

    return $carry;
}

/**
 * Sum numeric values in an iterable.
 * Non-numeric values are ignored except:
 *  - true => 1
 *  - false/null => 0
 *
 * @param iterable<int|float|string|bool|null> $data
 */
function sum(iterable $data): int|float
{
    $total = 0;

    foreach ($data as $value) {
        if ($value === true) {
            $total += 1;
            continue;
        }
        if ($value === false) {
            // add 0, ignore
            continue;
        }
        if ($value === null) {
            // add 0, ignore
            continue;
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
 * @param  iterable<TKey, TValue>  $data
 * @return Generator<TKey, TValue>
 */
function take(iterable $data, int $count): Generator
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
 * @param  iterable<TKey, TValue> $data
 * @return Generator<TKey, mixed>
 */
function pluck(iterable $data, string|int $key, mixed $default = null): Generator
{
    $prop = (string) $key;
    foreach ($data as $k => $item) {
        if (\is_array($item)) {
            yield $k => (\array_key_exists($key, $item) ? $item[$key] : $default);
        } else {
            // Only consider public properties to avoid fatal errors
            $public = get_object_vars($item);
            yield $k => (\array_key_exists($prop, $public) ? $public[$prop] : $default);
        }
    }
}

/**
 * Convert iterable to a numerically indexed array (list).
 *
 * @template T
 * @param  iterable<T>   $data
 * @return array<int, T>
 */
function toList(iterable $data): array
{
    $out = [];
    foreach ($data as $value) {
        $out[] = $value;
    }

    return $out;
}

/**
 * Convert iterable to an array preserving keys.
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue> $data
 * @return array<TKey, TValue>
 */
function toArray(iterable $data): array
{
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
 * @param  iterable<TKey, TValue>       $data
 * @param  callable(TValue, TKey): bool $predicate
 * @return Generator<TKey, TValue>
 */
function reject(iterable $data, callable $predicate): Generator
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === false) {
            yield $key => $value;
        }
    }
}

/**
 * Sort items by a comparable value derived from each element. Returns an array with original keys preserved.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TSort of (int|float|string)
 * @param  iterable<TKey, TValue>        $data
 * @param  callable(TValue, TKey): TSort $getComparable
 * @return array<TKey, TValue>
 */
function sortBy(iterable $data, callable $getComparable): array
{
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
 * @param  iterable<array-key, T> $data
 * @return Generator<int, T>
 */
function values(iterable $data): Generator
{
    foreach ($data as $value) {
        yield $value;
    }
}

/**
 * Yield keys lazily.
 *
 * @template TKey of array-key
 * @param  iterable<TKey, mixed> $data
 * @return Generator<int, TKey>
 */
function keys(iterable $data): Generator
{
    foreach ($data as $key => $unused) {
        yield $key;
    }
}

/**
 * Get the first element of an iterable, or $default if empty.
 *
 * @template T
 * @param  iterable<array-key, T> $data
 * @param  T|null                 $default
 * @return T|null
 */
function first(iterable $data, mixed $default = null): mixed
{
    foreach ($data as $value) {
        return $value;
    }

    return $default;
}

/**
 * Get the last element of an iterable, or $default if empty.
 * Eager by necessity.
 *
 * @template T
 * @param  iterable<array-key, T> $data
 * @param  T|null                 $default
 * @return T|null
 */
function last(iterable $data, mixed $default = null): mixed
{
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
 * @param  iterable<TKey, T>       $data
 * @param  callable(T, TKey): bool $predicate
 * @param  T|null                  $default
 * @return T|null
 */
function find(iterable $data, callable $predicate, mixed $default = null): mixed
{
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
function count(iterable $data): int
{
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
function isEmpty(iterable $data): bool
{
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
 * @param iterable<TKey, TValue> $data
 */
function contains(iterable $data, mixed $needle): bool
{
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
 * @param iterable<TKey, TValue> $data
 * @return array<TKey, TValue>
 */
function sort(iterable $data): array
{
    $arr = toArray($data);
    uasort($arr, static fn (int|float|string $a, int|float|string $b): int => $a <=> $b);

    return $arr;
}

/**
 * Group items by a key derived from each element. Eager.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TGroupKey of array-key
 * @param iterable<TKey, TValue>              $data
 * @param callable(TValue, TKey): TGroupKey   $grouper
 * @return array<TGroupKey, array<TKey, TValue>>
 */
function groupBy(iterable $data, callable $grouper): array
{
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
 * Re-key items by a key derived from each element. Eager. Later keys overwrite earlier ones.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TNewKey of array-key
 * @param iterable<TKey, TValue>             $data
 * @param callable(TValue, TKey): TNewKey    $keyer
 * @return array<TNewKey, TValue>
 */
function keyBy(iterable $data, callable $keyer): array
{
    $out = [];
    foreach ($data as $key => $value) {
        $out[$keyer($value, $key)] = $value;
    }

    return $out;
}

/**
 * Average of numeric values. Non-numeric values are handled like in sum().
 * Returns 0.0 when iterable is empty.
 * Eager.
 *
 * @param iterable<int|float|string|bool|null> $data
 */
function average(iterable $data): float
{
    $total = sum($data);
    $n = count($data);
    if ($n === 0) {
        return 0.0;
    }

    return (float) ($total / $n);
}

/**
 * Whether all elements satisfy the predicate. Lazy short-circuit, eager result.
 * For empty iterables returns true.
 *
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue>           $data
 * @param callable(TValue, TKey): bool     $predicate
 */
function every(iterable $data, callable $predicate): bool
{
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
 * @param iterable<TKey, TValue>           $data
 * @param callable(TValue, TKey): bool     $predicate
 */
function some(iterable $data, callable $predicate): bool
{
    foreach ($data as $key => $value) {
        if ($predicate($value, $key) === true) {
            return true;
        }
    }

    return false;
}
