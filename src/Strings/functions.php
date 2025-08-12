<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Strings;

use InvalidArgumentException;
use Stringable;

use function trim as php_trim;

/**
 * Trim characters from both ends of a string.
 * Direct mode: trim($data, $characters = default): string
 * For pipe-friendly usage without PipeOps, use trimWith($characters) which returns a callable.
 */
function trim(string $data, string $characters = " \t\n\r\0\x0B"): string
{
    // Call global trim to avoid recursion into this function
    return php_trim($data, $characters);
}

/**
 * Pipe-friendly version of trim: returns a callable expecting the data.
 * Example: " -- Hello -- " |> trimWith(" -")
 */
function trimWith(string $characters = " \t\n\r\0\x0B"): callable
{
    return static fn (string $data): string => trim($data, $characters);
}

/**
 * Split string into lines. Handles CRLF/CR/LF.
 * Direct:  lines($data): array
 * Curried: lines(): callable(string $data): array
 *
 * @return array<int, string>|callable(string): array<int, string>
 */
function lines(?string $data = null): array|callable
{
    if ($data === null) {
        return static fn (string $d): array => lines_impl($d);
    }

    return lines_impl($data);
}

/** @internal
 * @return array<int, string>
 */
function lines_impl(string $data): array
{
    $parts = preg_split('/\R/u', $data);
    if ($parts === false) {
        // Fallback if PCRE fails for some reason
        $normalized = str_replace(["\r\n", "\r"], "\n", $data);

        return explode("\n", $normalized);
    }

    return $parts;
}

/**
 * Replace a prefix if present.
 * Direct:  replacePrefix($data, $prefix, $replacement): string
 * Curried: replacePrefix($prefix, $replacement): callable(string $data): string
 */
function replacePrefix(string $data_or_prefix, string $prefix_or_replacement, ?string $replacement = null): string|callable
{
    if ($replacement === null) {
        // Curried usage: replacePrefix($prefix, $replacement)
        $prefix = $data_or_prefix;
        $repl   = $prefix_or_replacement;

        return static fn (string $data): string => replacePrefix_impl($data, $prefix, $repl);
    }

    $data   = $data_or_prefix;
    $prefix = $prefix_or_replacement;

    return replacePrefix_impl($data, $prefix, $replacement);
}

/** @internal */
function replacePrefix_impl(string $data, string $prefix, string $replacement): string
{
    if ($prefix === '') {
        return $replacement . $data;
    }

    if (str_starts_with($data, $prefix)) {
        return $replacement . substr($data, \strlen($prefix));
    }

    return $data;
}

/**
 * Convert string to lowercase (UTF-8 aware if mbstring is available).
 * Direct:  toLowerCase($data): string
 * Curried: toLowerCase(): callable(string $data): string
 */
function toLowerCase(?string $data = null): string|callable
{
    if ($data === null) {
        return static fn (string $d): string => toLowerCase_impl($d);
    }

    return toLowerCase_impl($data);
}

/** @internal */
function toLowerCase_impl(string $data): string
{
    if (\function_exists('mb_strtolower')) {
        return mb_strtolower($data, 'UTF-8');
    }

    return strtolower($data);
}

/**
 * Convert string to uppercase (UTF-8 aware if mbstring is available).
 * Direct:  toUpperCase($data): string
 * Curried: toUpperCase(): callable(string $data): string
 */
function toUpperCase(?string $data = null): string|callable
{
    if ($data === null) {
        return static fn (string $d): string => toUpperCase_impl($d);
    }

    return toUpperCase_impl($data);
}

/** @internal */
function toUpperCase_impl(string $data): string
{
    if (\function_exists('mb_strtoupper')) {
        return mb_strtoupper($data, 'UTF-8');
    }

    return strtoupper($data);
}

/**
 * Get string length (UTF-8 aware if mbstring is available).
 * Direct:  length($data): int
 * Curried: length(): callable(string $data): int
 */
function length(?string $data = null): int|callable
{
    if ($data === null) {
        return static fn (string $d): int => length_impl($d);
    }

    return length_impl($data);
}

/** @internal */
function length_impl(string $data): int
{
    if (\function_exists('mb_strlen')) {
        return mb_strlen($data, 'UTF-8');
    }

    return \strlen($data);
}

/**
 * Join elements into a string, casting each to string.
 * Elements must be scalar or Stringable.
 * Analogous to implode().
 * Direct:  join(iterable $data, string $separator = ''): string
 * Curried: join(string $separator): callable(iterable $data): string
 *
 * @param iterable<mixed, int|float|string|bool|Stringable>|string $data_or_separator
 */
function join(iterable|string $data_or_separator, string $separator = ''): string|callable
{
    if (!is_iterable($data_or_separator)) {
        // Curried usage: join($separator)
        $sep = $data_or_separator;

        return static function (iterable $data) use ($sep): string {
            /** @var iterable<mixed, int|float|string|bool|Stringable> $data */
            return join_impl($data, $sep);
        };
    }

    return join_impl($data_or_separator, $separator);
}

/** @internal
 * @param iterable<mixed, int|float|string|bool|Stringable> $data
 */
function join_impl(iterable $data, string $separator = ''): string
{
    $parts = [];
    foreach ($data as $v) {
        if (\is_string($v)) {
            $parts[] = $v;
        } elseif (\is_scalar($v)) {
            $parts[] = (string) $v;
        } elseif ($v instanceof Stringable) {
            $parts[] = (string) $v;
        } else {
            throw new InvalidArgumentException('join() expects scalar or Stringable elements');
        }
    }

    return implode($separator, $parts);
}

/**
 * Split a string by a delimiter. Similar to explode() with adjusted limit semantics.
 * Limit of 0 is treated as 1. Negative limit drops that many elements from the end.
 * Direct:  split($data, $delimiter, $limit = PHP_INT_MAX): array
 * Curried: split($delimiter, $limit = PHP_INT_MAX): callable(string $data): array
 *
 * @return array<int, string>|callable(string): array<int, string>
 */
function split(string $data_or_delimiter, mixed $delimiter_or_limit = null, ?int $limit = PHP_INT_MAX): array|callable
{
    // Curried usage when second arg is omitted or is an int (limit)
    if ($delimiter_or_limit === null || \is_int($delimiter_or_limit)) {
        $delim = $data_or_delimiter;
        $lim   = \is_int($delimiter_or_limit) ? $delimiter_or_limit : PHP_INT_MAX;
        if ($delim === '') {
            throw new InvalidArgumentException('split() delimiter cannot be empty');
        }

        return static fn (string $data): array => split_impl($data, $delim, $lim);
    }

    // Direct path
    $data = $data_or_delimiter;
    if (!\is_string($delimiter_or_limit)) {
        throw new InvalidArgumentException('split() delimiter must be string');
    }
    $delimiter = $delimiter_or_limit;
    $lim       = $limit ?? PHP_INT_MAX;

    return split_impl($data, $delimiter, $lim);
}

/** @internal
 * @return array<int, string>
 */
function split_impl(string $data, string $delimiter, int $lim = PHP_INT_MAX): array
{
    if ($delimiter === '') {
        throw new InvalidArgumentException('split() delimiter cannot be empty');
    }

    if ($lim === 0) {
        $lim = 1;
    }

    if ($lim > 0) {
        return explode($delimiter, $data, $lim);
    }

    // Negative limit: return all but the last -$lim elements
    $parts = explode($delimiter, $data);
    $drop  = -$lim;
    $count = \count($parts);
    if ($drop >= $count) {
        return [];
    }

    return \array_slice($parts, 0, $count - $drop);
}
