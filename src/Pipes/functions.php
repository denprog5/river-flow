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
 * @param  iterable<mixed, T> $data
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
 * @param  iterable<TKey, TValue> $data
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
 * @param  iterable<TKey, TValue>                $data
 * @param  callable(TValue, TKey): TGroupKey     $grouper
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
 * @param  iterable<TKey, TValue>          $data
 * @param  callable(TValue, TKey): TNewKey $keyer
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
 * @param iterable<array-key, int|float|string|bool|null> $data
 */
function average(iterable $data): float
{
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

    if ($n === 0) {
        return 0.0;
    }

    return $total / $n;
}

/**
 * Whether all elements satisfy the predicate. Lazy short-circuit, eager result.
 * For empty iterables returns true.
 *
 * @template TKey of array-key
 * @template TValue
 * @param iterable<TKey, TValue>       $data
 * @param callable(TValue, TKey): bool $predicate
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
 * @param iterable<TKey, TValue>       $data
 * @param callable(TValue, TKey): bool $predicate
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

/**
 * Lazily return unique items based on the value itself. Keys of first occurrence are preserved.
 *
 * Scalars/bool/null compared by value and type; objects by spl_object_hash; arrays by serialize().
 * When a value cannot be hashed reliably (e.g., arrays containing closures), the element is skipped.
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>  $data
 * @return Generator<TKey, TValue>
 */
function uniq(iterable $data): Generator
{
    $seen = [];

    foreach ($data as $key => $value) {
        [$ok, $hash] = __hash_identifier($value);
        if (!$ok) {
            continue; // skip unhashable
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
 * Identifier hashing rules follow uniq().
 * Items with unhashable identifiers are skipped.
 *
 * @template TKey of array-key
 * @template TValue
 * @template TIdentifier
 * @param  iterable<TKey, TValue>              $data
 * @param  callable(TValue, TKey): TIdentifier $identifier
 * @return Generator<TKey, TValue>
 */
function uniqBy(iterable $data, callable $identifier): Generator
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
 * @param  iterable<mixed, mixed> $data
 * @return Generator<int, mixed>
 */
function flatten(iterable $data, int $depth = 1): Generator
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
            foreach (flatten($item, $depth - 1) as $v) {
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
 * @param  iterable<TKey, TValue>                                         $data
 * @param  callable(TValue, TKey): (iterable<mixed, TNewValue>|TNewValue) $transformer
 * @return Generator<int, TNewValue>
 */
function flatMap(iterable $data, callable $transformer): Generator
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
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>       $data
 * @param  callable(TValue, TKey): bool $predicate
 * @return Generator<TKey, TValue>
 */
function takeWhile(iterable $data, callable $predicate): Generator
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
 * @param  iterable<TKey, TValue>  $data
 * @return Generator<TKey, TValue>
 */
function drop(iterable $data, int $count): Generator
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
 *
 * @template TKey of array-key
 * @template TValue
 * @param  iterable<TKey, TValue>       $data
 * @param  callable(TValue, TKey): bool $predicate
 * @return Generator<TKey, TValue>
 */
function dropWhile(iterable $data, callable $predicate): Generator
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
 * @param  iterable<TKey, TValue>                                $data
 * @param  callable(TValue, TKey): bool                          $predicate
 * @return array{0: array<TKey, TValue>, 1: array<TKey, TValue>}
 */
function partition(iterable $data, callable $predicate): array
{
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
 * @param  iterable<mixed, mixed>            ...$iterables
 * @return Generator<int, array<int, mixed>>
 */
function zip(iterable ...$iterables): Generator
{
    $iters = [];
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
 * @param  iterable<array-key, mixed>        $data
 * @return Generator<int, array<int, mixed>>
 */
function chunk(iterable $data, int $size): Generator
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
 * Eager.
 *
 * @template T of (int|float|string)
 * @param  iterable<array-key, T> $data
 * @return T|null
 */
function min(iterable $data): int|float|string|null
{
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
 * Eager.
 *
 * @template T of (int|float|string)
 * @param  iterable<array-key, T> $data
 * @return T|null
 */
function max(iterable $data): int|float|string|null
{
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
