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
