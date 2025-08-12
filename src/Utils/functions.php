<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Utils;

use InvalidArgumentException;

/**
 * Identity function.
 * Direct:  identity($value): mixed
 * Curried: identity(): callable(mixed): mixed
 *
 * @template T
 * @param  T                        ...$valueOrNothing
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
 * @param  T|callable(T): void      $value_or_callback
 * @param  callable(T): void|null   $callback
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
 * @param  callable(mixed ...$args): mixed ...$functions
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
