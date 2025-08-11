<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Pipes;

use Generator;

/**
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue> $data
 * @param callable(TValue, TKey): bool $predicate
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
 * @param iterable<TKey, TValue> $data
 * @param callable(TValue, TKey): TNewValue $transformer
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
 * @param iterable<TKey, TValue> $data
 * @param callable(TCarry|null, TValue, TKey): TCarry $reducer
 * @param TCarry|null $initial
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

        if (is_int($value) || is_float($value)) {
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
 * @param iterable<TKey, TValue> $data
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
 * @param iterable<TKey, TValue> $data
 * @return Generator<TKey, mixed>
 */
function pluck(iterable $data, string|int $key, mixed $default = null): Generator
{
    $prop = (string) $key;
    foreach ($data as $k => $item) {
        if (is_array($item)) {
            yield $k => (array_key_exists($key, $item) ? $item[$key] : $default);
        } else {
            // Only consider public properties to avoid fatal errors
            $public = get_object_vars($item);
            yield $k => (array_key_exists($prop, $public) ? $public[$prop] : $default);
        }
    }
}

/**
 * Convert iterable to a numerically indexed array (list).
 *
 * @template T
 * @param iterable<T> $data
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
 * Reject elements for which predicate returns true.
 *
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue> $data
 * @param callable(TValue, TKey): bool $predicate
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
 * @param iterable<TKey, TValue> $data
 * @param callable(TValue, TKey): TSort $getComparable
 * @return array<TKey, TValue>
 */
function sortBy(iterable $data, callable $getComparable): array
{
    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[$key] = [$value, $getComparable($value, $key)];
    }

    uasort($pairs, static fn(array $a, array $b): int => $a[1] <=> $b[1]);

    return array_map(fn(array $pair): mixed => $pair[0], $pairs);
}
