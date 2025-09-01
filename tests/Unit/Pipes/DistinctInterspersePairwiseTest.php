<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\{distinctUntilChanged, intersperse, pairwise, toArray, toList};

use Generator;

describe('distinctUntilChanged', function (): void {
    it('skips consecutive duplicates and preserves first keys', function (): void {
        $input = ['a' => 1, 'b' => 1, 'c' => 2, 'd' => 2, 'e' => 1];
        $out   = toArray(distinctUntilChanged($input));
        expect($out)->toBe(['a' => 1, 'c' => 2, 'e' => 1]);
    });

    it('uses selector and preserves keys of first in each run', function (): void {
        $arr = ['ant', 'apple', 'bear', 'bob', 'cat'];
        $out = toArray(distinctUntilChanged($arr, fn (string $s): string => $s[0]));
        expect($out)->toBe([0 => 'ant', 2 => 'bear', 4 => 'cat']);
    });

    it('supports currying', function (): void {
        $curried = distinctUntilChanged(fn ($v): mixed => \is_string($v) ? $v[0] : $v);
        $out     = toArray($curried(['aa', 'ab', 'b', 'ba']));
        expect($out)->toBe([0 => 'aa', 2 => 'b']);
    });

    it('is lazy (basic)', function (): void {
        $iterations = 0;
        $sourceData = [1, 1, 2];
        $source     = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $v) {
                $iterations++;
                yield $v;
            }
        })();

        $stream = distinctUntilChanged($source);
        expect($iterations)->toBe(0);

        $first = null;
        foreach ($stream as $v) {
            $first = $v;
            break;
        }
        expect($first)->toBe(1);
        expect($iterations)->toBe(1);
    });

    it('empty input yields empty', function (): void {
        $out = toList(distinctUntilChanged([]));
        expect($out)->toBe([]);
    });
});

describe('intersperse', function (): void {
    it('inserts separator between elements and discards keys', function (): void {
        $out = toList(intersperse([1, 2, 3], 0));
        expect($out)->toBe([1, 0, 2, 0, 3]);
    });

    it('works with strings and currying', function (): void {
        $curried = intersperse('|');
        $out     = toList($curried(['a', 'b']));
        expect($out)->toBe(['a', '|', 'b']);
    });

    it('empty input yields empty', function (): void {
        $out = toList(intersperse([], 'x'));
        expect($out)->toBe([]);
    });
});

describe('pairwise', function (): void {
    it('yields consecutive pairs and discards keys', function (): void {
        $out = toList(pairwise([1, 2, 3, 4]));
        expect($out)->toBe([[1, 2], [2, 3], [3, 4]]);
    });

    it('supports currying and handles short inputs', function (): void {
        $pw = pairwise();
        expect(toList($pw([42])))->toBe([]);
        expect(toList($pw([])))->toBe([]);
    });
});
