<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Utils;

use InvalidArgumentException;
use Stringable;

/**
 * Identity function.
 * Direct:  identity($value): mixed
 * Curried: identity(): callable(mixed): mixed
 *
 * @template T
 * @param T ...$valueOrNothing
 * @return T|callable(mixed): mixed
 */
function identity(mixed ...$valueOrNothing): mixed
{
    if ($valueOrNothing === []) {
        return static fn (mixed $v): mixed => $v;
    }

    return $valueOrNothing[0];
}

/**
 * Tap into a value: call $callback with the value, then return the original value.
 * Useful for logging or side-effects in a pipeline.
 * Direct:  tap($value, $callback): mixed
 * Curried: tap($callback): callable(mixed $value): mixed
 *
 * @template T
 * @param T|callable(T): void $value_or_callback
 * @param callable(T): void|null $callback
 * @return T|callable(mixed): mixed
 */
function tap($value_or_callback, ?callable $callback = null): mixed
{
    if (\is_callable($value_or_callback) && $callback === null) {
        $cb = $value_or_callback;

        return static fn (mixed $value): mixed => tap($value, $cb);
    }

    $value = $value_or_callback;
    if ($callback === null) {
        throw new InvalidArgumentException('tap(): callback must not be null in direct invocation');
    }
    /** @phpstan-var T $value */
    $callback($value);

    return $value;
}

/**
 * Compose functions right-to-left.
 * compose(f, g, h)($x) === f(g(h($x))).
 * The innermost callable (right-most) may accept any number of arguments; the rest are treated as unary.
 *
 * @param callable(mixed ...$args): mixed ...$functions
 * @return callable(mixed ...$args): mixed
 */
function compose(callable ...$functions): callable
{
    if ($functions === []) {
        return static fn (mixed ...$args): mixed => $args[0] ?? null;
    }

    return static function (mixed ...$args) use ($functions): mixed {
        $result = null;
        $first  = true;
        // Execute right-to-left
        for ($i = \count($functions) - 1; $i >= 0; $i--) {
            $fn = $functions[$i];
            if ($first) {
                $result = $fn(...$args);
                $first  = false;
            } else {
                $result = $fn($result);
            }
        }

        return $result;
    };
}

/**
 * Pipe a value through callables left-to-right.
 * pipe($value, f, g, h) === h(g(f($value))).
 *
 * @param callable(mixed): mixed ...$functions
 */
function pipe(mixed $value, callable ...$functions): mixed
{
    $result = $value;
    foreach ($functions as $function) {
        $result = $function($result);
    }

    return $result;
}

/**
 * Logical negation of a predicate.
 * complement(pred)(...args) === !pred(...args)
 */
function complement(callable $pred): callable
{
    return static fn (mixed ...$args): bool => $pred(...$args) !== true;
}

/**
 * Logical AND of two predicates.
 */
function both(callable $a, callable $b): callable
{
    return static fn (mixed ...$args): bool => ($a(...$args) === true) && ($b(...$args) === true);
}

/**
 * Logical OR of two predicates.
 */
function either(callable $a, callable $b): callable
{
    return static fn (mixed ...$args): bool => ($a(...$args) === true) || ($b(...$args) === true);
}

/**
 * allPass([p1, p2, ...]) returns a predicate that is true if all predicates are true.
 *
 * @param array<int, callable> $predicates
 */
function allPass(array $predicates): callable
{
    foreach ($predicates as $p) {
        if (!\is_callable($p)) {
            throw new InvalidArgumentException('allPass(): all items must be callable');
        }
    }

    return static function (mixed ...$args) use ($predicates): bool {
        foreach ($predicates as $predicate) {
            if ($predicate(...$args) !== true) {
                return false;
            }
        }

        return true;
    };
}

/**
 * anyPass([p1, p2, ...]) returns a predicate that is true if any predicate is true.
 *
 * @param array<int, callable> $predicates
 */
function anyPass(array $predicates): callable
{
    foreach ($predicates as $p) {
        if (!\is_callable($p)) {
            throw new InvalidArgumentException('anyPass(): all items must be callable');
        }
    }

    return static fn (mixed ...$args): bool => array_any($predicates, fn ($p): bool => $p(...$args) === true);
}

/**
 * when(pred, fn) -> transformer
 * Applies fn to value if pred(value) is true, else returns value unchanged.
 */
function when(callable $pred, callable $fn): callable
{
    return static function (mixed $value, mixed ...$rest) use ($pred, $fn): mixed {
        if ($pred($value, ...$rest) === true) {
            return $fn($value, ...$rest);
        }

        return $value;
    };
}

/**
 * unless(pred, fn) -> transformer
 * Applies fn to value if pred(value) is false, else returns value unchanged.
 */
function unless(callable $pred, callable $fn): callable
{
    return static fn (mixed $value, mixed ...$rest): mixed => ($pred($value, ...$rest) === true)
        ? $value
        : $fn($value, ...$rest);
}

/**
 * ifElse(pred, onTrue, onFalse) -> transformer
 */
function ifElse(callable $pred, callable $onTrue, callable $onFalse): callable
{
    return static fn (mixed $value, mixed ...$rest): mixed => ($pred($value, ...$rest) === true)
        ? $onTrue($value, ...$rest)
        : $onFalse($value, ...$rest);
}

/**
 * cond([[pred, fn], ...]) -> transformer. Returns null if no predicate matches.
 *
 * @param array<int, array{0: callable, 1: callable}> $pairs
 */
function cond(array $pairs): callable
{
    foreach ($pairs as $pair) {
        if (!\is_array($pair) || !\array_key_exists(0, $pair) || !\array_key_exists(1, $pair) || !\is_callable($pair[0]) || !\is_callable($pair[1])) {
            throw new InvalidArgumentException('cond(): each pair must be [callable $pred, callable $fn]');
        }
    }

    return static function (mixed ...$args) use ($pairs): mixed {
        foreach ($pairs as [$pred, $fn]) {
            if ($pred(...$args) === true) {
                return $fn(...$args);
            }
        }

        return null;
    };
}

/**
 * converge(after, branches[]) -> callable
 * Computes each branch with the same arguments, then applies after(...results).
 *
 * @param array<int, callable> $branches
 */
function converge(callable $after, array $branches): callable
{
    foreach ($branches as $b) {
        if (!\is_callable($b)) {
            throw new InvalidArgumentException('converge(): branches must be callable');
        }
    }

    return static function (mixed ...$args) use ($after, $branches): mixed {
        $results = [];
        foreach ($branches as $branch) {
            $results[] = $branch(...$args);
        }

        return $after(...$results);
    };
}

/**
 * once(fn) -> callable that runs at most once; subsequent calls return cached result.
 */
function once(callable $fn): callable
{
    $called = false;
    $result = null;

    return static function (mixed ...$args) use (&$called, &$result, $fn): mixed {
        if ($called === false) {
            $result = $fn(...$args);
            $called = true;
        }

        return $result;
    };
}

/**
 * memoizeWith(keyFn, fn) -> callable with cache by keyFn(...args).
 * keyFn must return scalar|null or Stringable.
 */
function memoizeWith(callable $keyFn, callable $fn): callable
{
    /** @var array<string, mixed> $cache */
    $cache = [];

    return static function (mixed ...$args) use (&$cache, $keyFn, $fn): mixed {
        $key = $keyFn(...$args);

        if ($key === null) {
            $normalized = 'null';
        } elseif (\is_bool($key)) {
            $normalized = $key ? 'true' : 'false';
        } elseif (\is_int($key) || \is_float($key) || \is_string($key)) {
            $normalized = (string) $key;
        } elseif ($key instanceof Stringable) {
            $normalized = (string) $key;
        } else {
            throw new InvalidArgumentException('memoizeWith(): keyFn must return scalar, null or Stringable');
        }

        if (\array_key_exists($normalized, $cache)) {
            return $cache[$normalized];
        }

        $cache[$normalized] = $fn(...$args);

        return $cache[$normalized];
    };
}

/**
 * partial(fn, ...args) -> callable(...rest) => fn(...args, ...rest)
 */
function partial(callable $fn, mixed ...$args): callable
{
    return static fn (mixed ...$rest): mixed => $fn(...[...$args, ...$rest]);
}

/**
 * partialRight(fn, ...args) -> callable(...rest) => fn(...rest, ...args)
 */
function partialRight(callable $fn, mixed ...$args): callable
{
    return static fn (mixed ...$rest): mixed => $fn(...[...$rest, ...$args]);
}

/**
 * ascend(by) -> comparator($a, $b): int
 * by() must return scalar|null or Stringable.
 */
function ascend(callable $by): callable
{
    return static function (mixed $a, mixed $b) use ($by): int {
        $ka = $by($a);
        $kb = $by($b);
        $va = normalizeComparable($ka);
        $vb = normalizeComparable($kb);

        return $va <=> $vb;
    };
}

/**
 * descend(by) -> comparator($a, $b): int
 */
function descend(callable $by): callable
{
    $asc = ascend($by);

    return static fn (mixed $a, mixed $b): int => -$asc($a, $b);
}

/** @internal */
function normalizeComparable(mixed $v): int|float|string
{
    if ($v === null) {
        return '';
    }
    if (\is_int($v) || \is_float($v)) {
        return $v;
    }
    if (\is_bool($v)) {
        return $v ? 1 : 0;
    }
    if (\is_string($v)) {
        return $v;
    }
    if ($v instanceof Stringable) {
        return (string) $v;
    }

    throw new InvalidArgumentException('ascend/descend: by() must return scalar, null, or Stringable');
}
