<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\drop;
use function Denprog\RiverFlow\Pipes\dropWhile;
use function Denprog\RiverFlow\Pipes\takeWhile;
use function Denprog\RiverFlow\Pipes\toArray;

use Generator;

describe('takeWhile, drop, dropWhile', function (): void {
    it('takeWhile yields until predicate becomes false and preserves keys', function (): void {
        $input = [1 => 10, 2 => 20, 3 => 30, 4 => 15];
        $out   = toArray(takeWhile($input, fn (int $v, int $k): bool => $v < 25));
        expect($out)->toBe([1 => 10, 2 => 20]);

        $out2 = toArray(takeWhile([0 => 5, 1 => 10], fn (int $v): bool => $v > 5));
        expect($out2)->toBe([]);
    });

    it('drop skips first N elements and preserves keys', function (): void {
        $input = ['a' => 1, 'b' => 2, 'c' => 3];
        $out   = toArray(drop($input, 2));
        expect($out)->toBe(['c' => 3]);

        $out2 = toArray(drop($input, 0));
        expect($out2)->toBe($input);

        $out3 = toArray(drop($input, -5));
        expect($out3)->toBe($input);
    });

    it('dropWhile skips while predicate true, then yields rest preserving keys', function (): void {
        $input = [0 => 2, 1 => 4, 2 => 6, 3 => 5, 4 => 8];
        $out   = toArray(dropWhile($input, fn (int $v): bool => $v % 2 === 0));
        expect($out)->toBe([3 => 5, 4 => 8]);
    });

    it('dropWhile is lazy (basic check)', function (): void {
        $iterations = 0;
        $sourceData = [2, 4, 6, 5, 8];
        $source     = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $v) {
                $iterations++;
                yield $v;
            }
        })();
        $stream = dropWhile($source, fn (int $v): bool => $v % 2 === 0);
        expect($iterations)->toBe(0);

        $first = null;
        foreach ($stream as $v) {
            $first = $v;
            break;
        }
        expect($first)->toBe(5);
        expect($iterations)->toBe(4); // three dropped + one yielded
    });
});
