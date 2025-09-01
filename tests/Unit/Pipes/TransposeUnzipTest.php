<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use ArrayIterator;

use function Denprog\RiverFlow\Pipes\transpose;
use function Denprog\RiverFlow\Pipes\unzip;

use Generator;

describe('transpose and unzip', function (): void {
    it('transposes a square matrix', function (): void {
        $rows = [
            [1, 2],
            [3, 4],
        ];
        $t = transpose($rows);
        expect($t)->toBe([[1, 3], [2, 4]]);
    });

    it('aligns to the shortest row and discards extra elements', function (): void {
        $rows = [
            ['r1c1', 'r1c2', 'r1c3'],
            ['r2c1', 'r2c2'],
        ];
        $t = transpose($rows);
        expect($t)->toBe([
            ['r1c1', 'r2c1'],
            ['r1c2', 'r2c2'],
        ]);
    });

    it('discards keys of inner rows', function (): void {
        $rows = [
            ['a' => 1, 'b' => 2],
            ['x' => 3, 'y' => 4],
        ];
        $t = transpose($rows);
        expect($t)->toBe([[1, 3], [2, 4]]);
    });

    it('supports rows from Generators and Traversable; aligns to shortest', function (): void {
        $gen = (function (): Generator {
            yield 10;
            yield 20;
            yield 30;
        })();
        $iter = new ArrayIterator([100, 200]);
        $rows = [
            $gen,
            $iter,
            ['x' => 1000, 'y' => 2000, 'z' => 3000],
        ];
        $t = transpose($rows);
        expect($t)->toBe([
            [10, 100, 1000],
            [20, 200, 2000],
        ]);
    });

    it('empty input yields empty array', function (): void {
        expect(transpose([]))->toBe([]);
    });

    it('unzips pairs into two lists', function (): void {
        $pairs = [[1, 'a'], [2, 'b'], [3, 'c']];
        $unz   = unzip($pairs);
        expect($unz)->toBe([[1, 2, 3], ['a', 'b', 'c']]);
    });

    it('unzip aligns to the shortest row across tuples', function (): void {
        $rows = [
            [1, 'a', 'X'],
            [2, 'b'],
        ];
        $unz = unzip($rows);
        expect($unz)->toBe([[1, 2], ['a', 'b']]);
    });

    it('unzip discards keys from inner rows', function (): void {
        $rows = [
            ['k1' => 1, 'k2' => 'a'],
            ['k1' => 2, 'k2' => 'b'],
        ];
        $unz = unzip($rows);
        expect($unz)->toBe([[1, 2], ['a', 'b']]);
    });
});
