<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\init;
use function Denprog\RiverFlow\Pipes\tail;

use Generator;

describe('tail and init (lazy)', function (): void {
    it('tail returns all but the first, preserving keys (array)', function (): void {
        $arr = ['a' => 10, 'b' => 20, 'c' => 30];
        $out = iterator_to_array(tail($arr));
        expect($out)->toBe(['b' => 20, 'c' => 30]);
    });

    it('init returns all but the last, preserving keys (array)', function (): void {
        $arr = [5 => 'x', 7 => 'y', 2 => 'z'];
        $out = iterator_to_array(init($arr));
        expect($out)->toBe([5 => 'x', 7 => 'y']);
    });

    it('tail works with generators and is lazy', function (): void {
        $count = 0;
        $gen   = (function () use (&$count): Generator {
            $count++;
            yield 'k1' => 1;
            $count++;
            yield 'k2' => 2;
            $count++;
            yield 'k3' => 3;
        })();

        $tailGen = tail($gen);
        // Generator has not executed yet
        expect($count)->toBe(0);

        $out = iterator_to_array($tailGen);
        expect($out)->toBe(['k2' => 2, 'k3' => 3]);
        expect($count)->toBe(3);
    });

    it('init works with generators and is lazy', function (): void {
        $count = 0;
        $gen   = (function () use (&$count): Generator {
            $count++;
            yield 'k1' => 1;
            $count++;
            yield 'k2' => 2;
            $count++;
            yield 'k3' => 3;
        })();

        $initGen = init($gen);
        expect($count)->toBe(0);

        $out = iterator_to_array($initGen);
        expect($out)->toBe(['k1' => 1, 'k2' => 2]);
        expect($count)->toBe(3);
    });

    it('edge cases: empty and single-element', function (): void {
        expect(iterator_to_array(tail([])))->toBe([]);
        expect(iterator_to_array(init([])))->toBe([]);

        $one = ['x' => 42];
        expect(iterator_to_array(tail($one)))->toBe([]);
        expect(iterator_to_array(init($one)))->toBe([]);
    });

    it('supports currying', function (): void {
        $t = tail();
        $i = init();

        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        expect(iterator_to_array($t($arr)))->toBe(['b' => 2, 'c' => 3]);
        expect(iterator_to_array($i($arr)))->toBe(['a' => 1, 'b' => 2]);
    });
});
