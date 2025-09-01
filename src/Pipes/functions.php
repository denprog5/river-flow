<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Pipes;

use SplDoublyLinkedList;
use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorIterator;
use Throwable;
use Traversable;

/**
 * filter can be used as:
 *  - filter($data, $predicate): Generator
 *  - filter($predicate): callable(iterable $data): Generator
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                               $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                                               $predicate
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function filter(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => /** @var iterable<mixed, mixed> $data */
            filter_gen($data, $pred);
    }

    if (!is_iterable($data_or_predicate)) {
        throw new InvalidArgumentException('filter(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('filter(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    $data = $data_or_predicate; // iterable

    return filter_gen($data, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>       $data
 * @param  callable(mixed, mixed): bool $predicate
 * @return Generator<mixed, mixed>
 */
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): mixed                              $data_or_transformer
 * @param  (callable(mixed, mixed): mixed)|null                                              $transformer
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function map(iterable|callable $data_or_transformer, ?callable $transformer = null): Generator|callable
{
    if (\is_callable($data_or_transformer) && $transformer === null) {
        $xf = $data_or_transformer;

        return static fn (iterable $data): Generator => /** @var iterable<mixed, mixed> $data */
            map_gen($data, $xf);
    }

    if (!is_iterable($data_or_transformer)) {
        throw new InvalidArgumentException('map(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($transformer)) {
        throw new InvalidArgumentException('map(): transformer must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    $data = $data_or_transformer; // iterable

    return map_gen($data, $transformer);
}

/** @internal
 * @param  iterable<mixed, mixed>        $data
 * @param  callable(mixed, mixed): mixed $transformer
 * @return Generator<mixed, mixed>
 */
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
 *
 * @param iterable<mixed, mixed>|callable(mixed, mixed, mixed): mixed $data_or_reducer
 * @param (callable(mixed, mixed, mixed): mixed)|mixed|null           $reducer_or_initial
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
 * @param iterable<bool|float|int|string|null>|null $data
 */
function sum(?iterable $data = null): int|float|callable
{
    if ($data === null) {
        return static function (iterable $d): int|float {
            /** @var iterable<bool|float|int|string|null> $d */
            return sum_impl($d);
        };
    }

    /** @var iterable<bool|float|int|string|null> $data */
    return sum_impl($data);
}

/** @internal
 * @param iterable<bool|float|int|string|null> $data
 */
function sum_impl(iterable $data): int|float
{
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
 * @param  iterable<mixed, mixed>|int                                                        $data_or_count
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
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

/** @internal
 * @param  iterable<mixed, mixed>  $data
 * @return Generator<mixed, mixed>
 */
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
 * Pluck property/array key.
 * Dual-mode.
 *
 * @param  iterable<mixed, array<mixed, mixed>|object>|string|int                                                 $data_or_key
 * @return Generator<mixed, mixed>|callable(iterable<mixed, array<mixed, mixed>|object>): Generator<mixed, mixed>
 */
function pluck(iterable|string|int $data_or_key, mixed $key_or_default = null, mixed $default = null): Generator|callable
{
    if (!is_iterable($data_or_key)) {
        /** @var string|int $key */
        $key = $data_or_key;
        $def = $key_or_default;

        return static function (iterable $data) use ($key, $def): Generator {
            /** @var iterable<mixed, array<mixed, mixed>|object> $data */
            return pluck_gen($data, $key, $def);
        };
    }

    $data = $data_or_key;
    $key  = $key_or_default;
    // Runtime guard retained for direct path
    if (!\is_string($key) && !\is_int($key)) {
        throw new InvalidArgumentException('pluck(): key must be string|int');
    }

    return pluck_gen($data, $key, $default);
}

/** @internal
 * @param  iterable<mixed, array<mixed, mixed>|object> $data
 * @return Generator<mixed, mixed>
 */
function pluck_gen(iterable $data, string|int $key, mixed $default = null): Generator
{
    $prop = (string) $key;
    foreach ($data as $k => $item) {
        if (\is_array($item)) {
            yield $k => (\array_key_exists($key, $item) ? $item[$key] : $default);
        } else {
            /** @var object $item */
            $public = get_object_vars($item);
            yield $k => (\array_key_exists($prop, $public) ? $public[$prop] : $default);
        }
    }
}

/**
 * toList($data): array, or toList(): callable
 *
 * @param  iterable<mixed, mixed>|null                                           $data
 * @return array<int, mixed>|callable(iterable<mixed, mixed>): array<int, mixed>
 */
function toList(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static fn (iterable $d): array => /** @var iterable<mixed, mixed> $d */
            toList_impl($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return toList_impl($data);
}

/** @internal
 * @param  iterable<mixed, mixed> $data
 * @return array<int, mixed>
 */
function toList_impl(iterable $data): array
{
    $out = [];
    foreach ($data as $value) {
        $out[] = $value;
    }

    return $out;
}

/**
 * toArray($data): array, or toArray(): callable
 *
 * @param  iterable<mixed, mixed>|null                                                         $data
 * @return array<int|string, mixed>|callable(iterable<mixed, mixed>): array<int|string, mixed>
 */
function toArray(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static fn (iterable $d): array => /** @var iterable<mixed, mixed> $d */
            toArray_impl($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return toArray_impl($data);
}

/** @internal
 * @param  iterable<mixed, mixed>   $data
 * @return array<int|string, mixed>
 */
function toArray_impl(iterable $data): array
{
    $out = [];
    foreach ($data as $k => $v) {
        $out[$k] = $v;
    }

    return $out;
}

/**
 * Reject elements for which predicate returns true.
 * Dual-mode.
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                               $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                                               $predicate
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function reject(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): Generator => /** @var iterable<mixed, mixed> $data */
            reject_gen($data, $pred);
    }

    if (!is_iterable($data_or_predicate)) {
        throw new InvalidArgumentException('reject(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('reject(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    $data = $data_or_predicate; // iterable

    return reject_gen($data, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>       $data
 * @param  callable(mixed, mixed): bool $predicate
 * @return Generator<mixed, mixed>
 */
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): (int|float|string)                   $data_or_getComparable
 * @param  (callable(mixed, mixed): (int|float|string))|iterable<mixed, mixed>|null            $maybe_data
 * @return array<int|string, mixed>|callable(iterable<mixed, mixed>): array<int|string, mixed>
 */
function sortBy(iterable|callable $data_or_getComparable, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_getComparable) && $maybe_data === null) {
        $xf = $data_or_getComparable;

        return static fn (iterable $data): array => /** @var iterable<mixed, mixed> $data */
            sortBy_impl($data, $xf);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_getComparable)) {
        $xf   = $data_or_getComparable;
        $data = $maybe_data; // expected iterable
        if (!($data instanceof Traversable) && !\is_array($data)) {
            throw new InvalidArgumentException('sortBy(): data must be iterable');
        }

        /** @var iterable<mixed, mixed> $data */
        return sortBy_impl($data, $xf);
    }

    // Normal order
    $data          = $data_or_getComparable; // iterable
    $getComparable = $maybe_data;            // callable

    // $data is known to be iterable here due to branching above
    if (!\is_callable($getComparable)) {
        throw new InvalidArgumentException('sortBy(): getComparable must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return sortBy_impl($data, $getComparable);
}

/** @internal
 * @param  iterable<mixed, mixed>                     $data
 * @param  callable(mixed, mixed): (int|float|string) $getComparable
 * @return array<int|string, mixed>
 */
function sortBy_impl(iterable $data, callable $getComparable): array
{
    $pairs = [];
    foreach ($data as $key => $value) {
        $pairs[$key] = [$value, $getComparable($value, $key)];
    }

    uasort($pairs, static fn (mixed $a, mixed $b): int => /** @var array{0:mixed,1:int|float|string} $a */
        /** @var array{0:mixed,1:int|float|string} $b */
        $a[1] <=> $b[1]);

    /** @var array<int|string, mixed> $out */
    $out = array_map(static fn (array $pair): mixed => $pair[0], $pairs);

    return $out;
}

/**
 * Yield values (discard keys) lazily.
 *
 * @param  iterable<mixed, mixed>|null                                                   $data
 * @return Generator<int, mixed>|callable(iterable<mixed, mixed>): Generator<int, mixed>
 */
function values(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => /** @var iterable<mixed, mixed> $d */
            values_gen($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return values_gen($data);
}

/** @internal
 * @param  iterable<mixed, mixed> $data
 * @return Generator<int, mixed>
 */
function values_gen(iterable $data): Generator
{
    foreach ($data as $value) {
        yield $value;
    }
}

/**
 * Yield keys lazily.
 *
 * @param  iterable<mixed, mixed>|null                                                   $data
 * @return Generator<int, mixed>|callable(iterable<mixed, mixed>): Generator<int, mixed>
 */
function keys(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => /** @var iterable<mixed, mixed> $d */
            keys_gen($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return keys_gen($data);
}

/** @internal
 * @param  iterable<mixed, mixed> $data
 * @return Generator<int, mixed>
 */
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
 * Find first value matching predicate or return $default.
 * Dual-mode.
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                 $predicate
 * @return mixed|callable(iterable<mixed, mixed>): mixed
 */
function find(iterable|callable $data_or_predicate, ?callable $predicate = null, mixed $default = null): mixed
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;
        $def  = $default; // allow find($pred, $default) -> callable

        return static fn (iterable $data): mixed => /** @var iterable<mixed, mixed> $data */
            find_impl($data, $pred, $def);
    }

    if (!is_iterable($data_or_predicate)) {
        throw new InvalidArgumentException('find(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('find(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    $data = $data_or_predicate;

    return find_impl($data, $predicate, $default);
}

/** @internal
 * @param iterable<mixed, mixed>       $data
 * @param callable(mixed, mixed): bool $predicate
 */
function find_impl(iterable $data, callable $predicate, mixed $default = null): mixed
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
 * @param  iterable<mixed, mixed>|null               $data
 * @return int|callable(iterable<mixed, mixed>): int
 */
function count(?iterable $data = null): int|callable
{
    if ($data === null) {
        return static fn (iterable $d): int => /** @var iterable<mixed, mixed> $d */
            count_impl($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return count_impl($data);
}

/** @internal
 * @param iterable<mixed, mixed> $data
 */
function count_impl(iterable $data): int
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
 * @param  iterable<mixed, mixed>|null                 $data
 * @return bool|callable(iterable<mixed, mixed>): bool
 */
function isEmpty(?iterable $data = null): bool|callable
{
    if ($data === null) {
        return static fn (iterable $d): bool => /** @var iterable<mixed, mixed> $d */
            isEmpty_impl($d);
    }

    /** @var iterable<mixed, mixed> $data */
    return isEmpty_impl($data);
}

/** @internal
 * @param iterable<mixed, mixed> $data
 */
function isEmpty_impl(iterable $data): bool
{
    foreach ($data as $_) {
        return false;
    }

    return true;
}

/**
 * Check whether iterable contains a value (strict comparison).
 * Dual-mode.
 *
 * @param  iterable<mixed, mixed>|mixed                $data_or_needle
 * @return bool|callable(iterable<mixed, mixed>): bool
 */
function contains(mixed $data_or_needle, mixed $needle = null): bool|callable
{
    if (!is_iterable($data_or_needle)) {
        $nd = $data_or_needle;

        return static fn (iterable $data): bool => /** @var iterable<mixed, mixed> $data */
            contains_impl($data, $nd);
    }

    /** @var iterable<mixed, mixed> $data */
    $data = $data_or_needle;

    return contains_impl($data, $needle);
}

/** @internal
 * @param iterable<mixed, mixed> $data
 */
function contains_impl(iterable $data, mixed $needle): bool
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
 * @param  iterable<mixed, int|float|string>|null                                                                               $data
 * @return array<int|string, int|float|string>|callable(iterable<mixed, int|float|string>): array<int|string, int|float|string>
 */
function sort(?iterable $data = null): array|callable
{
    if ($data === null) {
        return static function (iterable $d): array {
            /** @var iterable<mixed, int|float|string> $d */
            return sort_impl($d);
        };
    }

    /** @var iterable<mixed, int|float|string> $data */
    return sort_impl($data);
}

/** @internal
 * @param  iterable<mixed, int|float|string>   $data
 * @return array<int|string, int|float|string>
 */
function sort_impl(iterable $data): array
{
    $arr = toArray($data);
    /** @var array<int|string, int|float|string> $arr */
    uasort($arr, static fn (mixed $a, mixed $b): int => $a <=> $b);

    /** @var array<int|string, int|float|string> $arr */
    return $arr;
}

/**
 * groupBy can be used as:
 *  - groupBy($data, $grouper): array
 *  - groupBy($grouper, $data): array (flexible order)
 *  - groupBy($grouper): callable(iterable $data): array
 * Eager.
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): array-key                                                        $data_or_grouper
 * @param  (callable(mixed, mixed): array-key)|iterable<mixed, mixed>|null                                                 $maybe_data
 * @return array<int|string, array<mixed, mixed>>|callable(iterable<mixed, mixed>): array<int|string, array<mixed, mixed>>
 */
function groupBy(iterable|callable $data_or_grouper, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_grouper) && $maybe_data === null) {
        $g = $data_or_grouper;

        return static fn (iterable $data): array => /** @var iterable<mixed, mixed> $data */
            groupBy_impl($data, $g);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_grouper)) {
        $g    = $data_or_grouper;
        $data = $maybe_data; // expected iterable
        if (!($data instanceof Traversable) && !\is_array($data)) {
            throw new InvalidArgumentException('groupBy(): data must be iterable');
        }

        /** @var iterable<mixed, mixed> $data */
        return groupBy_impl($data, $g);
    }

    // Normal order
    $data    = $data_or_grouper; // iterable
    $grouper = $maybe_data;      // callable

    // $data is known to be iterable here due to branching above
    if (!\is_callable($grouper)) {
        throw new InvalidArgumentException('groupBy(): grouper must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return groupBy_impl($data, $grouper);
}

/** @internal
 * @param  iterable<mixed, mixed>                $data
 * @param  callable(mixed, mixed): mixed         $grouper
 * @return array<array-key, array<mixed, mixed>>
 */
function groupBy_impl(iterable $data, callable $grouper): array
{
    $out = [];
    foreach ($data as $key => $value) {
        $group = $grouper($value, $key);
        if (!\is_int($group) && !\is_string($group)) {
            throw new InvalidArgumentException('groupBy(): grouper must return array-key');
        }
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): array-key                          $data_or_keyer
 * @param  (callable(mixed, mixed): array-key)|iterable<mixed, mixed>|null                   $maybe_data
 * @return array<array-key, mixed>|callable(iterable<mixed, mixed>): array<array-key, mixed>
 */
function keyBy(iterable|callable $data_or_keyer, iterable|callable|null $maybe_data = null): array|callable
{
    // Curried
    if (\is_callable($data_or_keyer) && $maybe_data === null) {
        $k = $data_or_keyer;

        return static fn (iterable $data): array => /** @var iterable<mixed, mixed> $data */
            keyBy_impl($data, $k);
    }

    // Flexible order: (callable, iterable)
    if (\is_callable($data_or_keyer)) {
        $k    = $data_or_keyer;
        $data = $maybe_data; // expected iterable
        if (!($data instanceof Traversable) && !\is_array($data)) {
            throw new InvalidArgumentException('keyBy(): data must be iterable');
        }

        /** @var iterable<mixed, mixed> $data */
        return keyBy_impl($data, $k);
    }

    // Normal order
    $data  = $data_or_keyer; // iterable
    $keyer = $maybe_data;    // callable

    // $data is known to be iterable here due to branching above
    if (!\is_callable($keyer)) {
        throw new InvalidArgumentException('keyBy(): keyer must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return keyBy_impl($data, $keyer);
}

/** @internal
 * @param  iterable<mixed, mixed>        $data
 * @param  callable(mixed, mixed): mixed $keyer
 * @return array<array-key, mixed>
 */
function keyBy_impl(iterable $data, callable $keyer): array
{
    $out = [];
    foreach ($data as $key => $value) {
        $newKey = $keyer($value, $key);
        if (!\is_int($newKey) && !\is_string($newKey)) {
            throw new InvalidArgumentException('keyBy(): keyer must return array-key');
        }
        $out[$newKey] = $value;
    }

    return $out;
}

/**
 * Average of numeric values. Non-numeric values are handled like in sum().
 * Returns 0.0 when iterable is empty.
 * Eager. Supports currying for pipe usage.
 *
 * @param  iterable<mixed, bool|float|int|string|null>|null                   $data
 * @return float|callable(iterable<mixed, bool|float|int|string|null>): float
 */
function average(?iterable $data = null): float|callable
{
    if ($data === null) {
        return static function (iterable $d): float {
            /** @var iterable<mixed, bool|float|int|string|null> $d */
            return average_impl($d);
        };
    }

    /** @var iterable<mixed, bool|float|int|string|null> $data */
    return average_impl($data);
}

/** @internal
 * @param iterable<mixed, bool|float|int|string|null> $data
 */
function average_impl(iterable $data): float
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

    return $n === 0 ? 0.0 : $total / $n;
}

/**
 * Whether all elements satisfy the predicate. Lazy short-circuit, eager result.
 * For empty iterables returns true.
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                 $predicate
 * @return bool|callable(iterable<mixed, mixed>): bool
 */
function every(iterable|callable $data_or_predicate, ?callable $predicate = null): bool|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): bool => /** @var iterable<mixed, mixed> $data */
            every_impl($data, $pred);
    }

    $data = $data_or_predicate;
    if (!is_iterable($data)) {
        throw new InvalidArgumentException('every(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('every(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return every_impl($data, $predicate);
}

/** @internal
 * @param iterable<mixed, mixed>       $data
 * @param callable(mixed, mixed): bool $predicate
 */
function every_impl(iterable $data, callable $predicate): bool
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                 $predicate
 * @return bool|callable(iterable<mixed, mixed>): bool
 */
function some(iterable|callable $data_or_predicate, ?callable $predicate = null): bool|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        return static fn (iterable $data): bool => /** @var iterable<mixed, mixed> $data */
            some_impl($data, $pred);
    }

    $data = $data_or_predicate;
    if (!is_iterable($data)) {
        throw new InvalidArgumentException('some(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('some(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return some_impl($data, $predicate);
}

/** @internal
 * @param iterable<mixed, mixed>       $data
 * @param callable(mixed, mixed): bool $predicate
 */
function some_impl(iterable $data, callable $predicate): bool
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
 * Supports currying for pipe usage: uniq() -> callable(iterable): Generator
 *
 * @param  iterable<mixed, mixed>|null                                                       $data
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function uniq(?iterable $data = null): Generator|callable
{
    if ($data === null) {
        return static fn (iterable $d): Generator => uniq_gen($d);
    }

    return uniq_gen($data);
}

/** @internal
 * @param  iterable<mixed, mixed>  $data
 * @return Generator<mixed, mixed>
 */
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): mixed                              $data_or_identifier
 * @param  (callable(mixed, mixed): mixed)|iterable<mixed, mixed>|null                       $identifier
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function uniqBy(iterable|callable $data_or_identifier, iterable|callable|null $identifier = null): Generator|callable
{
    if (\is_callable($data_or_identifier) && $identifier === null) {
        $ident = $data_or_identifier;

        return static fn (iterable $data): Generator => uniqBy_gen($data, $ident);
    }

    // Flexible order: uniqBy($identifier, $data)
    if (\is_callable($data_or_identifier) && is_iterable($identifier)) {
        $ident = $data_or_identifier;
        $data  = $identifier;

        return uniqBy_gen($data, $ident);
    }

    // Direct order: uniqBy($data, $identifier)
    if (is_iterable($data_or_identifier) && \is_callable($identifier)) {
        return uniqBy_gen($data_or_identifier, $identifier);
    }

    throw new InvalidArgumentException('uniqBy(): expected (iterable, callable) or (callable, iterable)');
}

/** @internal
 * @param  iterable<mixed, mixed>        $data
 * @param  callable(mixed, mixed): mixed $identifier
 * @return Generator<mixed, mixed>
 */
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

/** @internal
 * @param  iterable<mixed, mixed> $data
 * @return Generator<int, mixed>
 */
function flatten_gen(iterable $data, int $depth): Generator
{
    if ($depth <= 0) {
        foreach (values_gen($data) as $v) {
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
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): (iterable<mixed, mixed>|mixed) $data_or_transformer
 * @param  (callable(mixed, mixed): (iterable<mixed, mixed>|mixed))|null                 $transformer
 * @return Generator<int, mixed>|callable(iterable<mixed, mixed>): Generator<int, mixed>
 */
function flatMap(iterable|callable $data_or_transformer, ?callable $transformer = null): Generator|callable
{
    if (\is_callable($data_or_transformer) && $transformer === null) {
        $xf = $data_or_transformer;

        /**
         * @param  iterable<int|string, mixed> $data
         * @return Generator<int, mixed>
         */
        return static fn (iterable $data): Generator => flatMap_gen($data, $xf);
    }

    $data = $data_or_transformer; // iterable
    if (!\is_callable($transformer)) {
        throw new InvalidArgumentException('flatMap(): transformer must be callable');
    }

    /** @var iterable<int|string, mixed> $data */
    return flatMap_gen($data, $transformer);
}

/** @internal
 * @param  iterable<mixed, mixed>                                 $data
 * @param  callable(mixed, mixed): (iterable<mixed, mixed>|mixed) $transformer
 * @return Generator<int, mixed>
 */
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
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                               $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                                               $predicate
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function takeWhile(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        /**
         * @param  iterable<int|string, mixed>  $data
         * @return Generator<int|string, mixed>
         */
        return static fn (iterable $data): Generator => takeWhile_gen($data, $pred);
    }

    $data = $data_or_predicate;
    if (!is_iterable($data)) {
        throw new InvalidArgumentException('takeWhile(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('takeWhile(): predicate must be callable');
    }

    /** @var iterable<int|string, mixed> $data */
    return takeWhile_gen($data, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>       $data
 * @param  callable(mixed, mixed): bool $predicate
 * @return Generator<mixed, mixed>
 */
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
 * @param  iterable<mixed, mixed>|int                                                        $data_or_count
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function drop(iterable|int $data_or_count, ?int $count = null): Generator|callable
{
    if (!is_iterable($data_or_count)) {
        $n = $data_or_count;

        /**
         * @param  iterable<int|string, mixed>  $data
         * @return Generator<int|string, mixed>
         */
        return static fn (iterable $data): Generator => drop_gen($data, $n);
    }

    $data = $data_or_count;
    $n    = (int) $count;

    /** @var iterable<mixed, mixed> $data */
    return drop_gen($data, $n);
}

/** @internal
 * @param  iterable<mixed, mixed>  $data
 * @return Generator<mixed, mixed>
 */
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
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                               $data_or_predicate
 * @param  callable(mixed, mixed): bool|null                                                 $predicate
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function dropWhile(iterable|callable $data_or_predicate, ?callable $predicate = null): Generator|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        /**
         * @param  iterable<int|string, mixed>  $data
         * @return Generator<int|string, mixed>
         */
        return static fn (iterable $data): Generator => dropWhile_gen($data, $pred);
    }

    $data = $data_or_predicate;
    if (!is_iterable($data)) {
        throw new InvalidArgumentException('dropWhile(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('dropWhile(): predicate must be callable');
    }

    /** @var iterable<mixed, mixed> $data */
    return dropWhile_gen($data, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>       $data
 * @param  callable(mixed, mixed): bool $predicate
 * @return Generator<mixed, mixed>
 */
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
 * Drop the last $count elements (lazy with lookahead). Preserves keys.
 *
 * Dual-mode:
 * - dropLast($data, $count): Generator
 * - dropLast($count): callable(iterable): Generator
 *
 * @param  iterable<mixed, mixed>|int                                                        $data_or_count
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function dropLast(iterable|int $data_or_count, ?int $count = null): Generator|callable
{
    if (!is_iterable($data_or_count)) {
        $n = $data_or_count;

        /**
         * @param  iterable<int|string, mixed>  $data
         * @return Generator<int|string, mixed>
         */
        return static fn (iterable $data): Generator => dropLast_gen($data, $n);
    }

    $data = $data_or_count;
    $n    = (int) $count;

    /** @var iterable<mixed, mixed> $data */
    return dropLast_gen($data, $n);
}

/** @internal
 * @param  iterable<mixed, mixed>  $data
 * @return Generator<mixed, mixed>
 */
function dropLast_gen(iterable $data, int $count): Generator
{
    if ($count <= 0) {
        // Nothing to drop
        yield from $data;

        return;
    }

    $q = new SplDoublyLinkedList();
    $q->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

    foreach ($data as $key => $value) {
        $q->push([$key, $value]);
        if ($q->count() > $count) {
            /** @var array{0: int|string, 1: mixed} $pair */
            $pair = $q->shift();
            yield $pair[0] => $pair[1];
        }
    }
}

/**
 * Take the last $count elements. Preserves keys. Buffers at most $count items.
 * Yields only after consuming the input (generator-safe, single pass).
 *
 * Dual-mode:
 * - takeLast($data, $count): Generator
 * - takeLast($count): callable(iterable): Generator
 *
 * @param  iterable<mixed, mixed>|int                                                        $data_or_count
 * @return Generator<mixed, mixed>|callable(iterable<mixed, mixed>): Generator<mixed, mixed>
 */
function takeLast(iterable|int $data_or_count, ?int $count = null): Generator|callable
{
    if (!is_iterable($data_or_count)) {
        $n = $data_or_count;

        /**
         * @param  iterable<int|string, mixed>  $data
         * @return Generator<int|string, mixed>
         */
        return static fn (iterable $data): Generator => takeLast_gen($data, $n);
    }

    $data = $data_or_count;
    $n    = (int) $count;

    /** @var iterable<mixed, mixed> $data */
    return takeLast_gen($data, $n);
}

/** @internal
 * @param  iterable<mixed, mixed>  $data
 * @return Generator<mixed, mixed>
 */
function takeLast_gen(iterable $data, int $count): Generator
{
    if ($count <= 0) {
        // Take nothing
        return;
    }

    $q = new SplDoublyLinkedList();
    $q->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

    foreach ($data as $key => $value) {
        $q->push([$key, $value]);
        if ($q->count() > $count) {
            $q->shift();
        }
    }

    foreach ($q as $pair) {
        /** @var array{0: int|string, 1: mixed} $pair */
        yield $pair[0] => $pair[1];
    }
}

/**
 * Partition into two arrays: [pass, fail] according to predicate. Eager. Keys preserved.
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                                                                                           $data_or_predicate
 * @param  callable(mixed, mixed): bool|null                                                                                                             $predicate
 * @return array{0: array<mixed, mixed>, 1: array<mixed, mixed>}|callable(iterable<mixed, mixed>): array{0: array<mixed, mixed>, 1: array<mixed, mixed>}
 */
function partition(iterable|callable $data_or_predicate, ?callable $predicate = null): array|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        /**
         * @param  iterable<mixed, mixed>                                          $data
         * @return array{0: array<int|string, mixed>, 1: array<int|string, mixed>}
         */
        return static fn (iterable $data): array => partition_impl($data, $pred);
    }

    $data = $data_or_predicate;
    if (!is_iterable($data)) {
        throw new InvalidArgumentException('partition(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('partition(): predicate must be callable');
    }

    return partition_impl($data, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>                                $data
 * @param  callable(mixed, mixed): bool                          $predicate
 * @return array{0: array<mixed, mixed>, 1: array<mixed, mixed>}
 */
function partition_impl(iterable $data, callable $predicate): array
{
    /** @var array<int|string, mixed> $pass */
    $pass = [];
    /** @var array<int|string, mixed> $fail */
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
 * Split into two lists at index $index. Eager. Keys discarded.
 *
 * Dual-mode:
 * - splitAt($data, $index): array{0: list, 1: list}
 * - splitAt($index): callable(iterable): array{0: list, 1: list}
 *
 * @param  iterable<mixed, mixed>|int                                                                                                            $data_or_index
 * @return array{0: array<int, mixed>, 1: array<int, mixed>}|callable(iterable<mixed, mixed>): array{0: array<int, mixed>, 1: array<int, mixed>}
 */
function splitAt(iterable|int $data_or_index, ?int $index = null): array|callable
{
    if (!is_iterable($data_or_index)) {
        $i = $data_or_index;

        /**
         * @param  iterable<mixed, mixed>                            $data
         * @return array{0: array<int, mixed>, 1: array<int, mixed>}
         */
        return static fn (iterable $data): array => splitAt_impl($data, $i);
    }

    $data = $data_or_index;
    $i    = (int) $index;

    return splitAt_impl($data, $i);
}

/** @internal
 * @param  iterable<mixed, mixed>                            $data
 * @return array{0: array<int, mixed>, 1: array<int, mixed>}
 */
function splitAt_impl(iterable $data, int $index): array
{
    if ($index <= 0) {
        /** @var array<int, mixed> $right */
        $right = toList_impl($data);

        return [[], $right];
    }

    /** @var array<int, mixed> $left */
    $left = [];
    /** @var array<int, mixed> $right */
    $right = [];
    $pos   = 0;
    foreach ($data as $value) {
        if ($pos < $index) {
            $left[] = $value;
        } else {
            $right[] = $value;
        }
        $pos++;
    }

    return [$left, $right];
}

/**
 * Split into two lists at the first element where predicate($value, $key) is true. Eager. Keys discarded.
 *
 * Dual-mode:
 * - splitWhen($data, $predicate): array{0: list, 1: list}
 * - splitWhen($predicate): callable(iterable): array{0: list, 1: list}
 *
 * @param  iterable<mixed, mixed>|callable(mixed, mixed): bool                                                                                   $data_or_predicate
 * @param  (callable(mixed, mixed): bool)|null                                                                                                   $predicate
 * @return array{0: array<int, mixed>, 1: array<int, mixed>}|callable(iterable<mixed, mixed>): array{0: array<int, mixed>, 1: array<int, mixed>}
 */
function splitWhen(iterable|callable $data_or_predicate, ?callable $predicate = null): array|callable
{
    if (\is_callable($data_or_predicate) && $predicate === null) {
        $pred = $data_or_predicate;

        /**
         * @param  iterable<mixed, mixed>                            $data
         * @return array{0: array<int, mixed>, 1: array<int, mixed>}
         */
        return static fn (iterable $data): array => splitWhen_impl($data, $pred);
    }

    if (!is_iterable($data_or_predicate)) {
        throw new InvalidArgumentException('splitWhen(): first argument must be iterable in direct invocation');
    }
    if (!\is_callable($predicate)) {
        throw new InvalidArgumentException('splitWhen(): predicate must be callable');
    }

    return splitWhen_impl($data_or_predicate, $predicate);
}

/** @internal
 * @param  iterable<mixed, mixed>                            $data
 * @param  callable(mixed, mixed): bool                      $predicate
 * @return array{0: array<int, mixed>, 1: array<int, mixed>}
 */
function splitWhen_impl(iterable $data, callable $predicate): array
{
    /** @var array<int, mixed> $before */
    $before = [];
    /** @var array<int, mixed> $after */
    $after   = [];
    $matched = false;

    foreach ($data as $key => $value) {
        if (!$matched && $predicate($value, $key) === true) {
            $matched = true;
            $after[] = $value;
            continue;
        }
        if ($matched) {
            $after[] = $value;
        } else {
            $before[] = $value;
        }
    }

    return [$before, $after];
}

/**
 * Pipe-friendly zip: returns a callable that zips with the provided iterables.
 * Example: [1,2,3] |> zipWith(['a','b']) |> toList()
 *
 * @param  iterable<mixed, mixed>                                              ...$others
 * @return callable(iterable<mixed, mixed>): Generator<int, array<int, mixed>>
 */
function zipWith(iterable ...$others): callable
{
    return static fn (iterable $data): Generator => zip($data, ...$others);
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
 * @param  iterable<array-key, mixed>|int                                                                            $data_or_size
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

/** @internal
 * @param  iterable<mixed, mixed>            $data
 * @return Generator<int, array<int, mixed>>
 */
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
 * Sliding window (aperture) of size $size. Windows are contiguous and of exact length $size.
 * Lazy; keys discarded; yields numeric-indexed arrays.
 *
 * Dual-mode:
 * - aperture($data, $size): Generator<int, array<int, mixed>>
 * - aperture($size): callable(iterable): Generator<int, array<int, mixed>>
 *
 * @param  iterable<array-key, mixed>|int                                                                            $data_or_size
 * @return Generator<int, array<int, mixed>>|callable(iterable<array-key, mixed>): Generator<int, array<int, mixed>>
 */
function aperture(iterable|int $data_or_size, ?int $size = null): Generator|callable
{
    if (!is_iterable($data_or_size)) {
        $sz = $data_or_size;

        return static fn (iterable $data): Generator => aperture_gen($data, $sz);
    }

    $data = $data_or_size;
    $sz   = (int) $size;

    return aperture_gen($data, $sz);
}

/** @internal
 * @param  iterable<mixed, mixed>            $data
 * @return Generator<int, array<int, mixed>>
 */
function aperture_gen(iterable $data, int $size): Generator
{
    if ($size <= 0) {
        throw new InvalidArgumentException('aperture() size must be >= 1');
    }

    $buf = new SplDoublyLinkedList();
    $buf->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);

    foreach ($data as $value) {
        $buf->push($value);
        if ($buf->count() > $size) {
            $buf->shift();
        }
        if ($buf->count() === $size) {
            $window = [];
            foreach ($buf as $v) {
                $window[] = $v;
            }
            yield $window;
        }
    }
}

/**
 * Minimum of comparable values. Returns null for empty input.
 * Eager. Supports currying: min() -> callable(iterable): mixed
 *
 * @param iterable<array-key, int|float|string>|null $data
 */
function min(?iterable $data = null): int|float|string|null|callable
{
    if ($data === null) {
        return static function (iterable $d): int|float|string|null {
            /** @var iterable<mixed, int|float|string> $d */
            return min_impl($d);
        };
    }

    /** @var iterable<array-key, int|float|string> $data */
    return min_impl($data);
}

/** @internal
 * @param iterable<mixed, int|float|string> $data
 */
function min_impl(iterable $data): int|float|string|null
{
    $found = false;
    /** @var int|float|string|null $min */
    $min = null;
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
 *
 * @param iterable<array-key, int|float|string>|null $data
 */
function max(?iterable $data = null): int|float|string|null|callable
{
    if ($data === null) {
        return static function (iterable $d): int|float|string|null {
            /** @var iterable<mixed, int|float|string> $d */
            return max_impl($d);
        };
    }

    /** @var iterable<array-key, int|float|string> $data */
    return max_impl($data);
}

/** @internal
 * @param iterable<mixed, int|float|string> $data
 */
function max_impl(iterable $data): int|float|string|null
{
    $found = false;
    /** @var int|float|string|null $max */
    $max = null;
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
