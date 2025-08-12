<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\PipeOps;

use Generator;
use Stringable;
use Denprog\RiverFlow\Pipes as P;

// Pipe-friendly helpers (curried). Each function returns a callable that accepts the data
// as its single required parameter. This enables clean PHP 8.5 pipe usage without placeholders.
// Example: [1,2,3] |> filter(fn($x)=>$x>1) |> map(fn($x)=>$x*2) |> toList();

// Transform
function filter(callable $predicate): callable
{
    return static function (iterable $data) use ($predicate): Generator {
        /** @var Generator<mixed, mixed> $g */
        $g = P\filter($data, $predicate);
        return $g;
    };
}
function reject(callable $predicate): callable
{
    return static function (iterable $data) use ($predicate): Generator {
        /** @var Generator<mixed, mixed> $g */
        $g = P\reject($data, $predicate);
        return $g;
    };
}
function map(callable $transformer): callable
{
    return static function (iterable $data) use ($transformer): Generator {
        /** @var Generator<mixed, mixed> $g */
        $g = P\map($data, $transformer);
        return $g;
    };
}
function pluck(string|int $key, mixed $default = null): callable
{
    return static function (iterable $data) use ($key, $default): Generator {
        /** @var iterable<mixed, array|object> $data */
        /** @var Generator<mixed, mixed> $g */
        $g = P\pluck($data, $key, $default);
        return $g;
    };
}

// Aggregation / Terminal
function reduce(callable $reducer, mixed $initial = null): callable
{
    return static fn (iterable $data): mixed => P\reduce($data, $reducer, $initial);
}
function sum(): callable
{
    return static function (iterable $data): int|float {
        /** @var iterable<bool|float|int|string|null> $data */
        /** @var int|float $n */
        $n = P\sum($data);
        return $n;
    };
}
function average(): callable
{
    return static function (iterable $data): float {
        /** @var iterable<bool|float|int|string|null> $data */
        /** @var float $n */
        $n = P\average($data);
        return $n;
    };
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
    return static function (iterable $data) use ($needle): bool {
        /** @var bool $b */
        $b = P\contains($data, $needle);
        return $b;
    };
}
function every(callable $predicate): callable
{
    return static function (iterable $data) use ($predicate): bool {
        /** @var bool $b */
        $b = P\every($data, $predicate);
        return $b;
    };
}
function some(callable $predicate): callable
{
    return static function (iterable $data) use ($predicate): bool {
        /** @var bool $b */
        $b = P\some($data, $predicate);
        return $b;
    };
}
function pipe_count(): callable
{
    return static function (iterable $data): int {
        /** @var int $n */
        $n = P\count($data);
        return $n;
    };
} // avoids colliding with built-in count()
function isEmpty(): callable
{
    return static function (iterable $data): bool {
        /** @var bool $b */
        $b = P\isEmpty($data);
        return $b;
    };
}

// Conversions
function toList(): callable
{
    return static function (iterable $data): array {
        /** @var array<int, mixed> $arr */
        $arr = P\toList($data);
        return $arr;
    };
}
function toArray(): callable
{
    return static function (iterable $data): array {
        /** @var array<int|string, mixed> $arr */
        $arr = P\toArray($data);
        return $arr;
    };
}
function values(): callable
{
    return static function (iterable $data): Generator {
        /** @var Generator<int, mixed, mixed, mixed> $g */
        $g = P\values($data);
        return $g;
    };
}
function keys(): callable
{
    return static function (iterable $data): Generator {
        /** @var Generator<int, mixed, mixed, mixed> $g */
        $g = P\keys($data);
        return $g;
    };
}

// Reshaping / Ordering
function groupBy(callable $grouper): callable
{
    return static function (iterable $data) use ($grouper): array {
        /** @var array<array-key, array<mixed, mixed>> $arr */
        $arr = P\groupBy($data, $grouper);
        return $arr;
    };
}
function keyBy(callable $keyer): callable
{
    return static function (iterable $data) use ($keyer): array {
        /** @var array<array-key, mixed> $arr */
        $arr = P\keyBy($data, $keyer);
        return $arr;
    };
}
function sortBy(callable $selector): callable
{
    return static function (iterable $data) use ($selector): array {
        /** @var array<int|string, mixed> $arr */
        $arr = P\sortBy($data, $selector);
        return $arr;
    };
}
function sort(): callable
{
    return static function (iterable $data): array {
        /** @var iterable<mixed, int|float|string> $data */
        /** @var array<int|string, int|float|string> $arr */
        $arr = P\sort($data);
        return $arr;
    };
}

// Uniqueness
function uniq(): callable
{
    return static function (iterable $data): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\uniq($data);
        return $g;
    };
}
function uniqBy(callable $identifier): callable
{
    return static function (iterable $data) use ($identifier): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\uniqBy($data, $identifier);
        return $g;
    };
}

// Combining / Windowing
function chunk(int $size): callable
{
    return static function (iterable $data) use ($size): Generator {
        /** @var iterable<int|string, mixed> $data */
        /** @var Generator<int, array<int, mixed>, mixed, mixed> $g */
        $g = P\chunk($data, $size);
        return $g;
    };
}
function partition(callable $predicate): callable
{
    return static function (iterable $data) use ($predicate): array {
        /** @var array{0: array<int|string, mixed>, 1: array<int|string, mixed>} $arr */
        $arr = P\partition($data, $predicate);
        return $arr;
    };
}
/**
 * @param iterable<mixed, mixed> ...$others
 */
function zip(iterable ...$others): callable
{
    return static function (iterable $data) use ($others): Generator {
        /** @var Generator<int, array<int, mixed>, mixed, mixed> $g */
        $g = P\zip($data, ...$others);
        return $g;
    };
}

// Control flow
function take(int $count): callable
{
    return static function (iterable $data) use ($count): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\take($data, $count);
        return $g;
    };
}
function drop(int $count): callable
{
    return static function (iterable $data) use ($count): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\drop($data, $count);
        return $g;
    };
}

// Flattening / Mapping
function flatten(int $depth = 1): callable
{
    return static function (iterable $data) use ($depth): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\flatten($data, $depth);
        return $g;
    };
}
function flatMap(callable $transformer): callable
{
    return static function (iterable $data) use ($transformer): Generator {
        /** @var Generator<mixed, mixed, mixed, mixed> $g */
        $g = P\flatMap($data, $transformer);
        return $g;
    };
}

// ===== Strings wrappers (pipe-friendly) =====
use Denprog\RiverFlow\Strings as S;

function trim(string $characters = " \t\n\r\0\x0B"): callable
{
    return static fn (string $data): string => S\trim($data, $characters);
}
function lines(): callable
{
    return static function (string $data): array {
        /** @var array<int, string> $arr */
        $arr = S\lines($data);
        return $arr;
    };
}
function replacePrefix(string $prefix, string $replacement): callable
{
    return static function (string $data) use ($prefix, $replacement): string {
        /** @var string $s */
        $s = S\replacePrefix($data, $prefix, $replacement);
        return $s;
    };
}
function toLowerCase(): callable
{
    return static function (string $data): string {
        /** @var string $s */
        $s = S\toLowerCase($data);
        return $s;
    };
}
function toUpperCase(): callable
{
    return static function (string $data): string {
        /** @var string $s */
        $s = S\toUpperCase($data);
        return $s;
    };
}
function length(): callable
{
    return static function (string $data): int {
        /** @var int $n */
        $n = S\length($data);
        return $n;
    };
}
function join(string $separator = ''): callable
{
    return static function (iterable $data) use ($separator): string {
        /** @var iterable<mixed, int|float|string|bool|Stringable> $data */
        /** @var string $s */
        $s = S\join($data, $separator);
        return $s;
    };
}
function split(string $delimiter, int $limit = PHP_INT_MAX): callable
{
    return static function (string $data) use ($delimiter, $limit): array {
        /** @var array<int, string> $arr */
        $arr = S\split($data, $delimiter, $limit);
        return $arr;
    };
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
