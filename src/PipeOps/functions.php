<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\PipeOps;

use Denprog\RiverFlow\Pipes as P;

// Pipe-friendly helpers (curried). Each function returns a callable that accepts the data
// as its single required parameter. This enables clean PHP 8.5 pipe usage without placeholders.
// Example: [1,2,3] |> filter(fn($x)=>$x>1) |> map(fn($x)=>$x*2) |> toList();

// Transform
function filter(callable $predicate): callable { return static fn(iterable $data) => P\filter($data, $predicate); }
function reject(callable $predicate): callable { return static fn(iterable $data) => P\reject($data, $predicate); }
function map(callable $transformer): callable { return static fn(iterable $data) => P\map($data, $transformer); }
function pluck(string|int $key, mixed $default = null): callable { return static fn(iterable $data) => P\pluck($data, $key, $default); }

// Aggregation / Terminal
function reduce(callable $reducer, mixed $initial = null): callable { return static fn(iterable $data) => P\reduce($data, $reducer, $initial); }
function sum(): callable { return static fn(iterable $data) => P\sum($data); }
function average(): callable { return static fn(iterable $data) => P\average($data); }
function first(mixed $default = null): callable { return static fn(iterable $data) => P\first($data, $default); }
function last(mixed $default = null): callable { return static fn(iterable $data) => P\last($data, $default); }
function find(callable $predicate, mixed $default = null): callable { return static fn(iterable $data) => P\find($data, $predicate, $default); }
function contains(mixed $needle): callable { return static fn(iterable $data) => P\contains($data, $needle); }
function every(callable $predicate): callable { return static fn(iterable $data) => P\every($data, $predicate); }
function some(callable $predicate): callable { return static fn(iterable $data) => P\some($data, $predicate); }
function pipe_count(): callable { return static fn(iterable $data) => P\count($data); } // avoids colliding with built-in count()
function isEmpty(): callable { return static fn(iterable $data) => P\isEmpty($data); }

// Conversions
function toList(): callable { return static fn(iterable $data) => P\toList($data); }
function toArray(): callable { return static fn(iterable $data) => P\toArray($data); }
function values(): callable { return static fn(iterable $data) => P\values($data); }
function keys(): callable { return static fn(iterable $data) => P\keys($data); }

// Reshaping / Ordering
function groupBy(callable $grouper): callable { return static fn(iterable $data) => P\groupBy($data, $grouper); }
function keyBy(callable $keyer): callable { return static fn(iterable $data) => P\keyBy($data, $keyer); }
function sortBy(callable $selector): callable { return static fn(iterable $data) => P\sortBy($data, $selector); }
function sort(): callable { return static fn(iterable $data) => P\sort($data); }

// Uniqueness
function uniq(): callable { return static fn(iterable $data) => P\uniq($data); }
function uniqBy(callable $identifier): callable { return static fn(iterable $data) => P\uniqBy($data, $identifier); }

// Combining / Windowing
function chunk(int $size): callable { return static fn(iterable $data) => P\chunk($data, $size); }
function partition(callable $predicate): callable { return static fn(iterable $data) => P\partition($data, $predicate); }
function zip(iterable ...$others): callable { return static fn(iterable $data) => P\zip($data, ...$others); }

// Control flow
function take(int $count): callable { return static fn(iterable $data) => P\take($data, $count); }
function drop(int $count): callable { return static fn(iterable $data) => P\drop($data, $count); }

// Flattening / Mapping
function flatten(int $depth = 1): callable { return static fn(iterable $data) => P\flatten($data, $depth); }
function flatMap(callable $transformer): callable { return static fn(iterable $data) => P\flatMap($data, $transformer); }
