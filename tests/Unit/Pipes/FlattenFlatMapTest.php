<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use ArrayIterator;

use function Denprog\RiverFlow\Pipes\flatMap;
use function Denprog\RiverFlow\Pipes\flatten;
use function Denprog\RiverFlow\Pipes\toArray;

use Generator;

describe('flatten and flatMap', function () {
    it('flattens arrays to specified depth and discards keys', function () {
        $input = [1, [2, [3]], 4];

        expect(toArray(flatten($input, 0)))->toBe([1, [2, [3]], 4]);
        expect(toArray(flatten($input, 1)))->toBe([1, 2, [3], 4]);
        expect(toArray(flatten($input, 2)))->toBe([1, 2, 3, 4]);
    });

    it('flattens iterables within iterables including Traversable', function () {
        $input = new ArrayIterator([
            10,
            new ArrayIterator([20, 30]),
            40,
        ]);

        expect(toArray(flatten($input, 1)))->toBe([10, 20, 30, 40]);
    });

    it('flattens nested arrays with string keys and discards them', function () {
        $input = [ 'a' => 1, 'b' => ['x' => 2, 'y' => 3] ];
        expect(toArray(flatten($input, 1)))->toBe([1, 2, 3]);
    });

    it('flatMap maps and flattens by one level when callback returns iterables', function () {
        $input = [1, 2, 3];
        $out   = toArray(flatMap($input, fn (int $v) => [$v, $v * 2]));
        expect($out)->toBe([1, 2, 2, 4, 3, 6]);
    });

    it('flatMap handles callbacks returning generators or scalars', function () {
        $input     = [1, 2];
        $genMapper = function (int $v): Generator {
            yield $v;
            yield $v + 10;
        };
        $out1 = toArray(flatMap($input, $genMapper));
        expect($out1)->toBe([1, 11, 2, 12]);

        $out2 = toArray(flatMap($input, fn (int $v) => $v * 3));
        expect($out2)->toBe([3, 6]);
    });
});
