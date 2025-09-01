<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\{repeat, take, times};

use Generator;
use InvalidArgumentException;

describe('repeat (lazy)', function (): void {
    it('repeats value finite times', function (): void {
        $gen   = repeat('x', 3);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe(['x', 'x', 'x']);
    });

    it('returns empty when count is 0', function (): void {
        $gen   = repeat(42, 0);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([]);
    });

    it('supports infinite repetition when count is null (use take to limit)', function (): void {
        $gen     = repeat(7, null);
        $limited = take($gen, 5);
        $array   = iterator_to_array($limited, false);
        expect($array)->toBe([7, 7, 7, 7, 7]);
    });

    it('throws on negative count', function (): void {
        expect(fn (): Generator => repeat('x', -1))->toThrow(InvalidArgumentException::class);
    });
});

describe('times (lazy)', function (): void {
    it('yields indices 0..count-1', function (): void {
        $gen   = times(4);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([0, 1, 2, 3]);
    });

    it('maps indices with producer callback', function (): void {
        $gen   = times(5, fn (int $i): int => $i * 2);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([0, 2, 4, 6, 8]);
    });

    it('returns empty for zero', function (): void {
        $gen   = times(0);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([]);
    });

    it('throws on negative count', function (): void {
        expect(fn (): Generator => times(-3))->toThrow(InvalidArgumentException::class);
    });
});
