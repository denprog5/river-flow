<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\aperture;
use function Denprog\RiverFlow\Pipes\dropLast;
use function Denprog\RiverFlow\Pipes\takeLast;
use function Denprog\RiverFlow\Pipes\toArray;

use Generator;
use InvalidArgumentException;

describe('dropLast, takeLast, aperture', function (): void {
    it('dropLast drops the last N elements and preserves keys', function (): void {
        $input = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $out   = toArray(dropLast($input, 2));
        expect($out)->toBe(['a' => 1, 'b' => 2]);

        $out0 = toArray(dropLast($input, 0));
        expect($out0)->toBe($input);

        $outNeg = toArray(dropLast($input, -5));
        expect($outNeg)->toBe($input);
    });

    it('dropLast is lazy with lookahead (yields after buffering N+1)', function (): void {
        $iterations = 0;
        $sourceData = [10, 20, 30, 40, 50];
        $source     = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $v) {
                $iterations++;
                yield $v;
            }
        })();

        $stream = dropLast($source, 2);
        expect($iterations)->toBe(0);

        $first = null;
        foreach ($stream as $v) {
            $first = $v;
            break;
        }

        expect($first)->toBe(10);
        expect($iterations)->toBe(3); // first yield requires reading N+1=3 items
    });

    it('takeLast returns only the last N elements and preserves keys', function (): void {
        $input = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $out   = toArray(takeLast($input, 2));
        expect($out)->toBe(['c' => 3, 'd' => 4]);

        $out0 = toArray(takeLast($input, 0));
        expect($out0)->toBe([]);

        $outNeg = toArray(takeLast($input, -3));
        expect($outNeg)->toBe([]);
    });

    it('takeLast consumes the input before yielding (single pass, generator-safe)', function (): void {
        $iterations = 0;
        $sourceData = [1, 2, 3, 4, 5];
        $source     = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $v) {
                $iterations++;
                yield $v;
            }
        })();

        $stream = takeLast($source, 2);
        expect($iterations)->toBe(0);

        $first = null;
        foreach ($stream as $v) {
            $first = $v;
            break;
        }

        expect($first)->toBe(4);
        expect($iterations)->toBe(5); // entire input consumed to compute last N
    });

    it('aperture produces sliding windows of fixed size; keys discarded', function (): void {
        $input = ['x' => 1, 'y' => 2, 'z' => 3, 'w' => 4];
        $win2  = toArray(aperture($input, 2));
        expect($win2)->toBe([[1, 2], [2, 3], [3, 4]]);

        $win1 = toArray(aperture([7, 8], 1));
        expect($win1)->toBe([[7], [8]]);
    });

    it('aperture throws on non-positive size', function (): void {
        expect(fn (): array => toArray(aperture([1, 2, 3], 0)))->toThrow(InvalidArgumentException::class);
        expect(fn (): array => toArray(aperture([1, 2, 3], -2)))->toThrow(InvalidArgumentException::class);
    });
});
