<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\PipeOps;

use Generator;
use Denprog\RiverFlow\Pipes as P;

// Pipe-friendly helpers (curried). Each function returns a callable that accepts the data
// as its single required parameter. This enables clean PHP 8.5 pipe usage without placeholders.
// Example: [1,2,3] |> filter(fn($x)=>$x>1) |> map(fn($x)=>$x*2) |> toList();

// Transform
function filter(callable $predicate): callable
{
    return static fn (iterable $data): Generator|callable => P\filter($data, $predicate);
}
function reject(callable $predicate): callable
{
    return static fn (iterable $data): Generator|callable => P\reject($data, $predicate);
}
function map(callable $transformer): callable
{
    return static fn (iterable $data): Generator|callable => P\map($data, $transformer);
}
function pluck(string|int $key, mixed $default = null): callable
{
    return static fn (iterable $data): Generator|callable => P\pluck($data, $key, $default);
}

// Aggregation / Terminal
function reduce(callable $reducer, mixed $initial = null): callable
{
    return static fn (iterable $data): mixed => P\reduce($data, $reducer, $initial);
}
function sum(): callable
{
    return static fn (iterable $data): int|float|callable => P\sum($data);
}
function average(): callable
{
    return static fn (iterable $data): float|callable => P\average($data);
}
function first(mixed $default = null): callable
{
    return static fn (iterable $data): mixed => P\first($data, $default);
}
function last(mixed $default = null): callable
{
    return static fn (iterable $data): mixed => P\last($data, $default);
}
function find(callable $predicate, mixed $default = null): callable
{
    return static fn (iterable $data): mixed => P\find($data, $predicate, $default);
}
function contains(mixed $needle): callable
{
    return static fn (iterable $data): bool|callable => P\contains($data, $needle);
}
function every(callable $predicate): callable
{
    return static fn (iterable $data): bool|callable => P\every($data, $predicate);
}
function some(callable $predicate): callable
{
    return static fn (iterable $data): bool|callable => P\some($data, $predicate);
}
function pipe_count(): callable
{
    return static fn (iterable $data): int|callable => P\count($data);
} // avoids colliding with built-in count()
function isEmpty(): callable
{
    return static fn (iterable $data): bool|callable => P\isEmpty($data);
}

// Conversions
function toList(): callable
{
    return static fn (iterable $data): callable|array => P\toList($data);
}
function toArray(): callable
{
    return static fn (iterable $data): callable|array => P\toArray($data);
}
function values(): callable
{
    return static fn (iterable $data): Generator|callable => P\values($data);
}
function keys(): callable
{
    return static fn (iterable $data): Generator|callable => P\keys($data);
}

// Reshaping / Ordering
function groupBy(callable $grouper): callable
{
    return static fn (iterable $data): callable|array => P\groupBy($data, $grouper);
}
function keyBy(callable $keyer): callable
{
    return static fn (iterable $data): callable|array => P\keyBy($data, $keyer);
}
function sortBy(callable $selector): callable
{
    return static fn (iterable $data): callable|array => P\sortBy($data, $selector);
}
function sort(): callable
{
    return static fn (iterable $data): callable|array => P\sort($data);
}

// Uniqueness
function uniq(): callable
{
    return static fn (iterable $data): Generator|callable => P\uniq($data);
}
function uniqBy(callable $identifier): callable
{
    return static fn (iterable $data): Generator|callable => P\uniqBy($data, $identifier);
}

// Combining / Windowing
function chunk(int $size): callable
{
    return static fn (iterable $data): Generator|callable => P\chunk($data, $size);
}
function partition(callable $predicate): callable
{
    return static fn (iterable $data): callable|array => P\partition($data, $predicate);
}
function zip(iterable ...$others): callable
{
    return static fn (iterable $data): Generator => P\zip($data, ...$others);
}

// Control flow
function take(int $count): callable
{
    return static fn (iterable $data): Generator|callable => P\take($data, $count);
}
function drop(int $count): callable
{
    return static fn (iterable $data): Generator|callable => P\drop($data, $count);
}

// Flattening / Mapping
function flatten(int $depth = 1): callable
{
    return static fn (iterable $data): Generator|callable => P\flatten($data, $depth);
}
function flatMap(callable $transformer): callable
{
    return static fn (iterable $data): Generator|callable => P\flatMap($data, $transformer);
}

// ===== Strings wrappers (pipe-friendly) =====
use Denprog\RiverFlow\Strings as S;

function trim(string $characters = " \t\n\r\0\x0B"): callable
{
    return static fn (string $data): string => S\trim($data, $characters);
}
function lines(): callable
{
    return static fn (string $data): array => S\lines($data);
}
function replacePrefix(string $prefix, string $replacement): callable
{
    return static fn (string $data): string => S\replacePrefix($data, $prefix, $replacement);
}
function toLowerCase(): callable
{
    return static fn (string $data): string => S\toLowerCase($data);
}
function toUpperCase(): callable
{
    return static fn (string $data): string => S\toUpperCase($data);
}
function length(): callable
{
    return static fn (string $data): int => S\length($data);
}
function join(string $separator = ''): callable
{
    return static fn (iterable $data): string => S\join($data, $separator);
}
function split(string $delimiter, int $limit = PHP_INT_MAX): callable
{
    return static fn (string $data): array => S\split($data, $delimiter, $limit);
}

// ===== Utils wrappers (pipe-friendly) =====
use Denprog\RiverFlow\Utils as U;

function identity(): callable
{
    return static fn (mixed $value): mixed => U\identity($value);
}
function tap(callable $callback): callable
{
    return static fn (mixed $value): mixed => U\tap($value, $callback);
}
