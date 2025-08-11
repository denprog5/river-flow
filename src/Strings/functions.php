<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Strings;

use InvalidArgumentException;
use Stringable;
use function trim as php_trim;

/**
 * Trim characters from both ends of a string.
 */
function trim(string $data, string $characters = " \t\n\r\0\x0B"): string
{
    // Call global trim to avoid recursion into this function
    return php_trim($data, $characters);
}

/**
 * Split string into lines. Handles CRLF/CR/LF.
 *
 * @return array<int, string>
 */
function lines(string $data): array
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
 */
function replacePrefix(string $data, string $prefix, string $replacement): string
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
 */
function toLowerCase(string $data): string
{
    if (\function_exists('mb_strtolower')) {
        return mb_strtolower($data, 'UTF-8');
    }

    return strtolower($data);
}

/**
 * Convert string to uppercase (UTF-8 aware if mbstring is available).
 */
function toUpperCase(string $data): string
{
    if (\function_exists('mb_strtoupper')) {
        return mb_strtoupper($data, 'UTF-8');
    }

    return strtoupper($data);
}

/**
 * Get string length (UTF-8 aware if mbstring is available).
 */
function length(string $data): int
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
 *
 * @param iterable<array-key, int|float|string|bool|Stringable> $data
 */
function join(iterable $data, string $separator = ''): string
{
    if (\is_array($data)) {
        // Cast elements to string explicitly
        return implode($separator, array_map(static fn (int|float|string|bool|Stringable $v): string => (string) $v, $data));
    }

    $parts = [];
    foreach ($data as $v) {
        if (\is_scalar($v)) {
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
 *
 * @return array<int, string>
 */
function split(string $data, string $delimiter, int $limit = PHP_INT_MAX): array
{
    if ($delimiter === '') {
        throw new InvalidArgumentException('split() delimiter cannot be empty');
    }

    if ($limit === 0) {
        $limit = 1;
    }

    if ($limit > 0) {
        return explode($delimiter, $data, $limit);
    }

    // Negative limit: return all but the last -$limit elements
    $parts = explode($delimiter, $data);
    $drop  = -$limit;
    $count = \count($parts);
    if ($drop >= $count) {
        return [];
    }

    return \array_slice($parts, 0, $count - $drop);
}
