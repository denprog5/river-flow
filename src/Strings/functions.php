<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Strings;

use InvalidArgumentException;
use Stringable;

use function trim as php_trim;

/**
 * Trim characters from both ends of a string.
 * Direct:  trim($data, $characters = " \t\n\r\0\x0B"): string
 * Curried: trim(): callable(string $data): string
 */
function trim(?string $data = null, string $characters = " \t\n\r\0\x0B"): string|callable
{
    if ($data === null) {
        // Pipe-friendly: return a callable expecting the data
        return static fn (string $d): string => trim_impl($d, $characters);
    }

    return trim_impl($data, $characters);
}

/** @internal */
function trim_impl(string $data, string $characters = " \t\n\r\0\x0B"): string
{
    // Call global trim to avoid recursion into this function
    return php_trim($data, $characters);
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
 * Check if a string contains the given substring (case-sensitive).
 * Direct:  includes($data, $needle): bool
 * Curried: includes($needle): callable(string $data): bool
 */
function includes(string $data_or_needle, ?string $needle = null): bool|callable
{
    if ($needle === null) {
        $n = $data_or_needle;

        return static fn (string $data): bool => includes_impl($data, $n);
    }

    $data = $data_or_needle;

    return includes_impl($data, $needle);
}

/** @internal */
function includes_impl(string $data, string $needle): bool
{
    // Follow PHP's str_contains semantics: empty needle is true
    return $needle === '' || str_contains($data, $needle);
}

/**
 * Check if a string starts with the given prefix (case-sensitive).
 * Direct:  startsWith($data, $prefix): bool
 * Curried: startsWith($prefix): callable(string $data): bool
 */
function startsWith(string $data_or_prefix, ?string $prefix = null): bool|callable
{
    if ($prefix === null) {
        $p = $data_or_prefix;

        return static fn (string $data): bool => startsWith_impl($data, $p);
    }

    $data = $data_or_prefix;

    return startsWith_impl($data, $prefix);
}

/** @internal */
function startsWith_impl(string $data, string $prefix): bool
{
    // Empty prefix is considered true
    return $prefix === '' || str_starts_with($data, $prefix);
}

/**
 * Check if a string ends with the given suffix (case-sensitive).
 * Direct:  endsWith($data, $suffix): bool
 * Curried: endsWith($suffix): callable(string $data): bool
 */
function endsWith(string $data_or_suffix, ?string $suffix = null): bool|callable
{
    if ($suffix === null) {
        $s = $data_or_suffix;

        return static fn (string $data): bool => endsWith_impl($data, $s);
    }

    $data = $data_or_suffix;

    return endsWith_impl($data, $suffix);
}

/** @internal */
function endsWith_impl(string $data, string $suffix): bool
{
    // Empty suffix is considered true
    return $suffix === '' || str_ends_with($data, $suffix);
}

/**
 * Replace all occurrences of $search with $replacement (case-sensitive).
 * Direct:  replace($data, $search, $replacement): string
 * Curried: replace($search, $replacement): callable(string $data): string
 */
function replace(string $data_or_search, ?string $search_or_replacement = null, ?string $replacement = null): string|callable
{
    if ($replacement === null) {
        // Curried path
        $search = $data_or_search;
        $repl   = $search_or_replacement ?? '';

        return static fn (string $data): string => replace_impl($data, $search, $repl);
    }

    // Direct path
    $data   = $data_or_search;
    $search = $search_or_replacement ?? '';

    return replace_impl($data, $search, $replacement);
}

/** @internal */
function replace_impl(string $data, string $search, string $replacement): string
{
    return str_replace($search, $replacement, $data);
}

/**
 * Slice a string by start (inclusive) and end (exclusive), supporting negative indices.
 * Direct:  slice($data, $start, ?$end = null): string
 * Curried: slice($start, ?$end = null): callable(string $data): string
 */
function slice(string|int $data_or_start, ?int $start = null, ?int $end = null): string|callable
{
    if (!\is_string($data_or_start)) {
        // Curried path: slice($start, ?$end)
        $s = $data_or_start;
        $e = $start;

        return static fn (string $data): string => slice_impl($data, $s, $e);
    }

    // Direct path: slice($data, $start, ?$end)
    $data = $data_or_start;
    $s    = $start ?? 0;

    return slice_impl($data, $s, $end);
}

/** @internal */
function slice_impl(string $data, int $start, ?int $end = null): string
{
    $len = length_impl($data);

    // Normalize start
    $begin = $start >= 0 ? $start : max(0, $len + $start);

    // Normalize end (exclusive)
    if ($end === null) {
        $to = $len;
    } elseif ($end >= 0) {
        $to = min($len, $end);
    } else {
        $to = max(0, $len + $end);
    }

    if ($begin >= $to) {
        return '';
    }

    $length = $to - $begin;

    if (\function_exists('mb_substr')) {
        return mb_substr($data, $begin, $length, 'UTF-8');
    }

    return substr($data, $begin, $length);
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

/**
 * Test a string against a PCRE pattern. Safe wrapper over preg_match.
 * Adds 'u' (UTF-8) modifier when missing.
 * Direct:  testRegex($data, $pattern, $flags = 0): bool
 * Curried: testRegex($pattern, $flags = 0): callable(string $data): bool
 *
 * @phpstan-param 0|256|512|768 $flags
 */
function testRegex(string $data_or_pattern, ?string $pattern = null, int $flags = 0): bool|callable
{
    if ($pattern === null) {
        $pat = $data_or_pattern;

        return static fn (string $data): bool => testRegex_impl($data, $pat, $flags);
    }

    $data = $data_or_pattern;

    return testRegex_impl($data, $pattern, $flags);
}

/** @internal
 * @phpstan-param 0|256|512|768 $flags
 */
function testRegex_impl(string $data, string $pattern, int $flags = 0): bool
{
    $pat = ensure_unicode_modifier($pattern);
    $err = null;

    // ReDoS protection: temporarily limit backtracking
    $prevBacktrackLimit = \ini_get('pcre.backtrack_limit');
    ini_set('pcre.backtrack_limit', '100000');

    set_error_handler(static function (int $severity, string $message) use (&$err): bool {
        $err = $message;

        return true; // swallow warning
    });

    try {
        $res = preg_match($pat, $data, $m, $flags);
    } finally {
        restore_error_handler();
        ini_set('pcre.backtrack_limit', $prevBacktrackLimit !== false ? $prevBacktrackLimit : '1000000');
    }

    if ($res === false || $err !== null) {
        $msg = $err ?? (\function_exists('preg_last_error_msg') ? preg_last_error_msg() : 'PCRE error');
        throw new InvalidArgumentException('testRegex(): ' . $msg);
    }

    return $res === 1;
}

/**
 * Match all occurrences of a PCRE pattern. Safe wrapper over preg_match_all.
 * Adds 'u' (UTF-8) modifier when missing.
 * Direct:  matchRegex($data, $pattern, $flags = 0): array
 * Curried: matchRegex($pattern, $flags = 0): callable(string $data): array
 *
 * @phpstan-param 0|256|512|768 $flags
 * @return array<int|string, mixed>|callable(string): array<int|string, mixed>
 */
function matchRegex(string $data_or_pattern, ?string $pattern = null, int $flags = 0): array|callable
{
    if ($pattern === null) {
        $pat = $data_or_pattern;

        return static fn (string $data): array => matchRegex_impl($data, $pat, $flags);
    }

    $data = $data_or_pattern;

    return matchRegex_impl($data, $pattern, $flags);
}

/** @internal
 * @phpstan-param 0|256|512|768 $flags
 * @return array<int|string, mixed>
 */
function matchRegex_impl(string $data, string $pattern, int $flags = 0): array
{
    $pat     = ensure_unicode_modifier($pattern);
    $matches = [];
    $err     = null;

    // ReDoS protection: temporarily limit backtracking
    $prevBacktrackLimit = \ini_get('pcre.backtrack_limit');
    ini_set('pcre.backtrack_limit', '100000');

    set_error_handler(static function (int $severity, string $message) use (&$err): bool {
        $err = $message;

        return true; // swallow warning
    });

    try {
        $res = preg_match_all($pat, $data, $matches, $flags);
    } finally {
        restore_error_handler();
        ini_set('pcre.backtrack_limit', $prevBacktrackLimit !== false ? $prevBacktrackLimit : '1000000');
    }

    if ($res === false || $err !== null) {
        $msg = $err ?? (\function_exists('preg_last_error_msg') ? preg_last_error_msg() : 'PCRE error');
        throw new InvalidArgumentException('matchRegex(): ' . $msg);
    }

    return $matches;
}

/**
 * Left pad a string to a target length.
 * Direct:  padStart($data, $len, $padChar = ' '): string
 * Curried: padStart($len, $padChar = ' '): callable(string $data): string
 */
function padStart(string|int $data_or_len, int|string|null $len_or_padChar = null, ?string $padChar = ' '): string|callable
{
    if (!\is_string($data_or_len)) {
        // Curried form: padStart($len, $padChar)
        $l  = $data_or_len;
        $pc = \is_string($len_or_padChar) ? $len_or_padChar : ($padChar ?? ' ');

        return static fn (string $data): string => padStart_impl($data, $l, $pc);
    }

    // Direct form: padStart($data, $len, $padChar)
    $data = $data_or_len;
    $l    = (($len_or_padChar !== null && !\is_string($len_or_padChar)) ? $len_or_padChar : 0);

    return padStart_impl($data, $l, $padChar ?? ' ');
}

/** @internal */
function padStart_impl(string $data, int $len, string $padChar = ' '): string
{
    if ($len <= 0) {
        return $data;
    }
    if ($padChar === '') {
        throw new InvalidArgumentException('padStart(): padChar cannot be empty');
    }

    return str_pad($data, $len, $padChar, STR_PAD_LEFT);
}

/**
 * Right pad a string to a target length.
 * Direct:  padEnd($data, $len, $padChar = ' '): string
 * Curried: padEnd($len, $padChar = ' '): callable(string $data): string
 */
function padEnd(string|int $data_or_len, int|string|null $len_or_padChar = null, ?string $padChar = ' '): string|callable
{
    if (!\is_string($data_or_len)) {
        // Curried form: padEnd($len, $padChar)
        $l  = $data_or_len;
        $pc = \is_string($len_or_padChar) ? $len_or_padChar : ($padChar ?? ' ');

        return static fn (string $data): string => padEnd_impl($data, $l, $pc);
    }

    // Direct form: padEnd($data, $len, $padChar)
    $data = $data_or_len;
    $l    = (($len_or_padChar !== null && !\is_string($len_or_padChar)) ? $len_or_padChar : 0);

    return padEnd_impl($data, $l, $padChar ?? ' ');
}

/** @internal */
function padEnd_impl(string $data, int $len, string $padChar = ' '): string
{
    if ($len <= 0) {
        return $data;
    }
    if ($padChar === '') {
        throw new InvalidArgumentException('padEnd(): padChar cannot be empty');
    }

    return str_pad($data, $len, $padChar, STR_PAD_RIGHT);
}

/** @internal */
function ensure_unicode_modifier(string $pattern): string
{
    if ($pattern === '') {
        return $pattern;
    }
    $delim = $pattern[0];
    $last  = strrpos($pattern, $delim);
    if ($last !== false && $last > 0) {
        $mods = substr($pattern, $last + 1);
        if (!str_contains($mods, 'u')) {
            return $pattern . 'u';
        }
    }

    return $pattern;
}
