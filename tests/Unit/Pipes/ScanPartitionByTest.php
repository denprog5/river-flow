<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use TypeError;
use function Denprog\RiverFlow\Pipes\partitionBy;
use function Denprog\RiverFlow\Pipes\scan;
use function Denprog\RiverFlow\Pipes\scanRight;
use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Pipes\toList;

use Generator;
use InvalidArgumentException;

describe('scan, scanRight, partitionBy', function (): void {
    it('scan accumulates left-to-right and preserves keys', function (): void {
        $acc = toArray(scan(['a' => 1, 'b' => 2, 'c' => 3], fn (?int $carry, int $v): int => ($carry ?? 0) + $v, 0));
        expect($acc)->toBe(['a' => 1, 'b' => 3, 'c' => 6]);

        $curried = scan(fn (?int $c, int $v): int => ($c ?? 0) + $v, 0);
        $list    = toList($curried([1, 2, 3]));
        expect($list)->toBe([1, 3, 6]);

        // empty input yields nothing
        expect(toList(scan([], fn ($c, $v) => $c, null)))->toBe([]);
    });

    it('scanRight accumulates from the right but yields in original order; preserves keys', function (): void {
        $suffixSums = toList(scanRight([1, 2, 3], fn (?int $c, int $v): int => ($c ?? 0) + $v, 0));
        expect($suffixSums)->toBe([6, 5, 3]);

        $assoc = toArray(scanRight(['a' => 1, 'b' => 2, 'c' => 3], fn (?int $c, int $v): int => ($c ?? 0) + $v, 0));
        expect($assoc)->toBe(['a' => 6, 'b' => 5, 'c' => 3]);

        $curried = scanRight(fn (?int $c, int $v): int => ($c ?? 0) + $v, 0);
        $out     = toArray($curried(['x' => 5, 'y' => 7]));
        expect($out)->toBe(['x' => 12, 'y' => 7]);

        // empty input yields nothing
        expect(toList(scanRight([], fn ($c, $v) => $c, null)))->toBe([]);
    });

    it('partitionBy groups contiguous items by discriminator value; inner keys preserved', function (): void {
        $words  = ['ant', 'apple', 'bear', 'bob', 'cat'];
        $chunks = toList(partitionBy(fn (string $s): string => $s[0])($words));
        expect($chunks)->toHaveCount(3);
        expect($chunks[0])->toBe([0 => 'ant', 1 => 'apple']);
        expect($chunks[1])->toBe([2 => 'bear', 3 => 'bob']);
        expect($chunks[2])->toBe([4 => 'cat']);

        $assoc = ['a' => 1, 'b' => 1, 'c' => 2, 'd' => 2, 'e' => 1];
        $byVal = toList(partitionBy(fn (int $v): int => $v)($assoc));
        expect($byVal)->toBe([
            ['a' => 1, 'b' => 1],
            ['c' => 2, 'd' => 2],
            ['e' => 1],
        ]);

        // direct call form
        $chunks2 = toList(partitionBy($words, fn (string $s): string => $s[0]));
        expect($chunks2)->toBe($chunks);

        // empty input -> no chunks
        $none = toList(partitionBy([], fn (): int => 1));
        expect($none)->toBe([]);

        // supports generators and preserves their keys via numeric sequence
        $gen = (function (): Generator {
            yield 10;
            yield 10;
            yield 20;
        })();
        $gChunks = toList(partitionBy($gen, fn (int $v): int => $v));
        expect($gChunks)->toBe([[0 => 10, 1 => 10], [2 => 20]]);
    });

    it('throws on invalid direct-call arguments for scan/scanRight/partitionBy', function (): void {
        // scan: first arg not matching (iterable|callable) triggers TypeError at signature level
        expect(fn (): array => toList(scan('not_iterable', fn ($c, $v) => $c, null)))->toThrow(TypeError::class);
        // scanRight: same behavior
        expect(fn (): array => toList(scanRight('not_iterable', fn ($c, $v) => $c, null)))->toThrow(TypeError::class);
        // partitionBy: first not iterable in direct invocation -> InvalidArgumentException by our runtime check
        expect(fn (): array => toList(partitionBy('strlen', fn (): null => null)))->toThrow(InvalidArgumentException::class);
    });
});
