<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\range;

use Generator;
use InvalidArgumentException;

describe('range (lazy)', function (): void {
    it('produces ascending integers end-exclusive', function (): void {
        $gen   = range(0, 5);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([0, 1, 2, 3, 4]);
    });

    it('supports custom positive step', function (): void {
        $gen   = range(0, 6, 2);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([0, 2, 4]);
    });

    it('supports negative step and descending order', function (): void {
        $gen   = range(5, 0, -2);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([5, 3, 1]);
    });

    it('returns empty when start equals end', function (): void {
        $gen   = range(3, 3);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([]);
    });

    it('throws when step is 0', function (): void {
        expect(fn (): Generator => range(0, 5, 0))->toThrow(InvalidArgumentException::class);
    });

    it('throws when positive step but start > end', function (): void {
        expect(fn (): Generator => range(5, 0, 1))->toThrow(InvalidArgumentException::class);
    });

    it('throws when negative step but start < end', function (): void {
        expect(fn (): Generator => range(0, 5, -1))->toThrow(InvalidArgumentException::class);
    });

    it('works with floats', function (): void {
        $gen   = range(0.0, 1.0, 0.25);
        $array = iterator_to_array($gen, false);
        expect($array)->toBe([0.0, 0.25, 0.5, 0.75]);
    });
});
