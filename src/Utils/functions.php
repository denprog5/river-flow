<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Utils;

/**
 * Identity function: returns the value unchanged.
 *
 * @template T
 * @param  T $value
 * @return T
 */
function identity(mixed $value): mixed
{
    return $value;
}

/**
 * Tap into a value: call $callback with the value, then return the original value.
 * Useful for logging or side-effects in a pipeline.
 *
 * @template T
 * @param  T                 $value
 * @param  callable(T): void $callback
 * @return T
 */
function tap(mixed $value, callable $callback): mixed
{
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
    foreach ($functions as $fn) {
        /** @var callable(mixed): mixed $fn */
        $result = $fn($result);
    }

    return $result;
}
