<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\split;

use InvalidArgumentException;

describe('Strings split', function () {
    it('splits string by delimiter with positive limit', function () {
        $s = 'a|b|c|d';
        expect(split($s, '|', 2))->toBe(['a', 'b|c|d']);
        expect(split($s, '|', 4))->toBe(['a', 'b', 'c', 'd']);
    });

    it('treats limit 0 as 1', function () {
        $s = 'x,y,z';
        expect(split($s, ',', 0))->toBe(['x,y,z']);
    });

    it('handles negative limit by dropping that many elements from the end', function () {
        $s = 'a|b|c|d';
        expect(split($s, '|', -1))->toBe(['a', 'b', 'c']);
        expect(split($s, '|', -2))->toBe(['a', 'b']);
        expect(split($s, '|', -10))->toBe([]); // drop >= count => empty
    });

    it('returns single element when delimiter not present (positive limit)', function () {
        expect(split('abc', ',', 3))->toBe(['abc']);
    });

    it('returns empty when delimiter not present and negative limit drops all', function () {
        expect(split('abc', ',', -1))->toBe([]);
    });

    it('throws on empty delimiter', function () {
        expect(fn () => split('abc', ''))->toThrow(InvalidArgumentException::class);
    });
});
