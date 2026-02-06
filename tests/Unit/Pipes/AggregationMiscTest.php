<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\average;
use function Denprog\RiverFlow\Pipes\contains;
use function Denprog\RiverFlow\Pipes\count as rf_count;
use function Denprog\RiverFlow\Pipes\every;
use function Denprog\RiverFlow\Pipes\find;
use function Denprog\RiverFlow\Pipes\first;
use function Denprog\RiverFlow\Pipes\isEmpty;
use function Denprog\RiverFlow\Pipes\last;
use function Denprog\RiverFlow\Pipes\reduce;
use function Denprog\RiverFlow\Pipes\some;
use function Denprog\RiverFlow\Pipes\sum;

use Generator;

describe('aggregation and misc (reduce, sum, average, first, last, find, count, isEmpty, contains, every, some)', function (): void {
    it('reduce reduces with initial and null-initial carries', function (): void {
        $nums = [1, 2, 3, 4];
        $sum  = reduce($nums, fn (?int $carry, int $v): int => ($carry ?? 0) + $v, 0);
        expect($sum)->toBe(10);

        $concat = reduce(['a', 'b', 'c'], fn (?string $carry, string $v): string => ($carry ?? '') . $v);
        expect($concat)->toBe('abc');
    });

    it('sum handles ints, floats, numeric strings, bools, null; ignores non-numeric strings', function (): void {
        $data = [1, 2.5, '3', true, false, null, 'x'];
        expect(sum($data))->toBe(1 + 2.5 + 3 + 1);
    });

    it('average returns 0.0 for empty and computes mean correctly for arrays', function (): void {
        expect(average([]))->toBe(0.0);
        expect(average([2, 4, 6]))->toBe(4.0);
        expect(average([1, '3', true, null]))->toBe((1 + 3 + 1) / 4);
    });

    it('average works with generators (single pass expectation)', function (): void {
        $gen = (function (): Generator {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })();
        expect(average($gen))->toBe(2.5);
    });

    it('first and last return defaults on empty', function (): void {
        expect(first([], 'd'))->toBe('d');
        expect(last([], 'z'))->toBe('z');

        $arr = ['a' => 10, 'b' => 20];
        expect(first($arr))->toBe(10);
        expect(last($arr))->toBe(20);
    });

    it('find returns first matching element or default', function (): void {
        $arr  = [1, 3, 6, 8];
        $even = find($arr, fn (int $v): bool => $v % 2 === 0);
        expect($even)->toBe(6);

        $none = find($arr, fn (int $v): bool => $v > 100, 'nope');
        expect($none)->toBe('nope');
    });

    it('count works for arrays and generators', function (): void {
        expect(rf_count([1, 2, 3]))->toBe(3);

        $gen = (function (): Generator {
            yield 1;
            yield 2;
        })();
        expect(rf_count($gen))->toBe(2);
    });

    it('isEmpty and contains behave as documented', function (): void {
        expect(isEmpty([]))->toBeTrue();
        $gen = (function (): Generator {
            yield from [];
        })(); // empty generator
        expect(isEmpty($gen))->toBeTrue();

        expect(contains([1, '1', 2], '1'))->toBeTrue();
        expect(contains([1, '1', 2], 1))->toBeTrue();
        expect(contains([1, 2], '1'))->toBeFalse(); // strict
    });

    it('every returns true for empty; some returns false for empty; otherwise short-circuits', function (): void {
        expect(every([], fn (): bool => false))->toBeTrue();
        expect(some([], fn (): bool => true))->toBeFalse();

        $arr = [2, 4, 6];
        expect(every($arr, fn (int $v): bool => $v % 2 === 0))->toBeTrue();
        expect(some($arr, fn (int $v): bool => $v > 5))->toBeTrue();
        expect(some($arr, fn (int $v): bool => $v > 10))->toBeFalse();
    });

    it('contains supports currying form', function (): void {
        $fn = contains('x');
        expect($fn(['a', 'x', 'b']))->toBeTrue();
        expect($fn(['a', 'b']))->toBeFalse();
    });
});
