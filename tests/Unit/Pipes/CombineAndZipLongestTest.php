<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use ArrayIterator;

use function Denprog\RiverFlow\Pipes\append;
use function Denprog\RiverFlow\Pipes\concat;
use function Denprog\RiverFlow\Pipes\concatWith;
use function Denprog\RiverFlow\Pipes\interleave;
use function Denprog\RiverFlow\Pipes\interleaveWith;
use function Denprog\RiverFlow\Pipes\prepend;
use function Denprog\RiverFlow\Pipes\toList;
use function Denprog\RiverFlow\Pipes\transpose;
use function Denprog\RiverFlow\Pipes\zip;
use function Denprog\RiverFlow\Pipes\zipLongest;
use function Denprog\RiverFlow\Pipes\zipLongestWith;

use Generator;

describe('concat/append/prepend/interleave/zipLongest', function (): void {
    it('concat concatenates iterables and discards keys', function (): void {
        $out = concat(['a' => 1, 'b' => 2], ['x' => 3], [4]) |> toList();
        expect($out)->toBe([1, 2, 3, 4]);
    });

    it('concatWith supports currying in pipelines', function (): void {
        $out = [1, 2] |> concatWith(['a', 'b']) |> toList();
        expect($out)->toBe([1, 2, 'a', 'b']);
    });

    it('append supports direct and curried forms', function (): void {
        expect(append([1, 2], 3, 4) |> toList())->toBe([1, 2, 3, 4]);

        $append34 = append(3, 4);
        expect($append34([1, 2]) |> toList())->toBe([1, 2, 3, 4]);
    });

    it('prepend supports direct and curried forms', function (): void {
        expect(prepend([1, 2], 3, 4) |> toList())->toBe([3, 4, 1, 2]);

        $prepend34 = prepend(3, 4);
        expect($prepend34([1, 2]) |> toList())->toBe([3, 4, 1, 2]);
    });

    it('interleave and interleaveWith stop at the shortest input', function (): void {
        expect(interleave([1, 2, 3], ['a', 'b']) |> toList())->toBe([1, 'a', 2, 'b']);

        $interleaveWithLetters = interleaveWith(['a', 'b']);
        expect($interleaveWithLetters([1, 2, 3]) |> toList())->toBe([1, 'a', 2, 'b']);
    });

    it('zipLongest and zipLongestWith fill missing values up to the longest input', function (): void {
        expect(zipLongest([1, 2, 3], 'x', ['a']) |> toList())->toBe([
            [1, 'a'],
            [2, 'x'],
            [3, 'x'],
        ]);

        $zipLongestWithFill = zipLongestWith('x', ['a']);
        expect($zipLongestWithFill([1, 2]) |> toList())->toBe([
            [1, 'a'],
            [2, 'x'],
        ]);
    });

    it('rewinds ArrayIterator inputs for concat/interleave/zipLongest', function (): void {
        $iter = new ArrayIterator([10, 20, 30]);
        $iter->next();
        expect(concat($iter) |> toList())->toBe([10, 20, 30]);

        $iterA = new ArrayIterator([1, 2, 3]);
        $iterA->next();
        expect(interleave($iterA, ['a', 'b']) |> toList())->toBe([1, 'a', 2, 'b']);

        $iterB = new ArrayIterator([1, 2, 3]);
        $iterB->next();
        expect(zipLongest($iterB, 'x', ['a']) |> toList())->toBe([
            [1, 'a'],
            [2, 'x'],
            [3, 'x'],
        ]);
    });

    it('works with already-started generators without forcing rewind', function (): void {
        $genConcat = (static function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        })();
        $genConcat->rewind();
        $genConcat->next(); // now at 2
        expect(concat($genConcat, ['z']) |> toList())->toBe([2, 3, 'z']);

        $genInterleave = (static function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        })();
        $genInterleave->rewind();
        $genInterleave->next(); // now at 2
        expect(interleave($genInterleave, ['a', 'b', 'c']) |> toList())->toBe([2, 'a', 3, 'b']);

        $genZip = (static function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        })();
        $genZip->rewind();
        $genZip->next(); // now at 2
        expect(zip($genZip, ['a', 'b', 'c']) |> toList())->toBe([
            [2, 'a'],
            [3, 'b'],
        ]);

        $genZipLongest = (static function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        })();
        $genZipLongest->rewind();
        $genZipLongest->next(); // now at 2
        expect(zipLongest($genZipLongest, 'x', ['a']) |> toList())->toBe([
            [2, 'a'],
            [3, 'x'],
        ]);
    });

    it('transpose handles iterator rows consistently', function (): void {
        $row = (static function (): Generator {
            yield 'r1c1';
            yield 'r1c2';
            yield 'r1c3';
        })();
        $row->rewind();
        $row->next(); // now at r1c2

        expect(transpose([$row, ['r2c1', 'r2c2', 'r2c3']]))->toBe([
            ['r1c2', 'r2c1'],
            ['r1c3', 'r2c2'],
        ]);
    });
});
