<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use ArrayIterator;

use function Denprog\RiverFlow\Pipes\chunk;
use function Denprog\RiverFlow\Pipes\max;
use function Denprog\RiverFlow\Pipes\min;
use function Denprog\RiverFlow\Pipes\partition;
use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Pipes\zip;
use function Denprog\RiverFlow\Pipes\zipWith;

use Generator;
use InvalidArgumentException;

describe('partition, zip, chunk, min, max', function (): void {
    it('partitions with keys preserved and returns [pass, fail]', function (): void {
        $input         = ['a' => 1, 'b' => 3, 'c' => 2, 'd' => 4];
        [$pass, $fail] = partition($input, fn (int $v): bool => $v % 2 === 0);
        expect($pass)->toBe(['c' => 2, 'd' => 4]);
        expect($fail)->toBe(['a' => 1, 'b' => 3]);

        [$p2, $f2] = partition([], fn ($v): true => true);
        expect($p2)->toBe([]);
        expect($f2)->toBe([]);
    });

    it('zips multiple iterables and stops at the shortest', function (): void {
        $a = [1, 2, 3];
        $b = (function (): Generator {
            yield 10;
            yield 20;
        })();
        $c = new ArrayIterator([100, 200, 300, 400]);
        $z = toArray(zip($a, $b, $c));
        expect($z)->toBe([
            [1, 10, 100],
            [2, 20, 200],
        ]);
    });

    it('zips a single iterable producing 1-length rows', function (): void {
        $rows = toArray(zip([7, 8]));
        expect($rows)->toBe([[7], [8]]);
    });

    it('zipWith returns a callable and zips in pipelines; stops at shortest; keys discarded', function (): void {
        $a = [1, 2, 3];
        $b = ['a', 'b'];
        $c = (function (): Generator {
            yield 'X';
            yield 'Y';
            yield 'Z';
        })();
        $fn   = zipWith($b, $c);
        $rows = toArray($fn($a));
        expect($rows)->toBe([
            [1, 'a', 'X'],
            [2, 'b', 'Y'],
        ]);
    });

    it('zipWith works with Traversable and arrays; pairing with single other iterable', function (): void {
        $data  = ['x' => 7, 'y' => 8];
        $other = new ArrayIterator([70, 80, 90]);
        $rows  = toArray(zipWith($other)($data));
        expect($rows)->toBe([[7, 70], [8, 80]]);
    });

    it('zipWith with no other iterables behaves like zip(data) producing 1-length rows', function (): void {
        $rows = toArray(zipWith()([9, 10]));
        expect($rows)->toBe([[9], [10]]);
    });

    it('zip is lazy relative to the longest iterable (does not over-consume beyond shortest)', function (): void {
        $count = 0;
        $long  = (function () use (&$count): Generator {
            foreach (['L1', 'L2', 'L3', 'L4'] as $v) {
                $count++;
                yield $v;
            }
        })();
        $short = [10, 20];
        $rows  = toArray(zip($short, $long));
        expect($rows)->toBe([[10, 'L1'], [20, 'L2']]);
        // Generator is prefetched on rewind and then advanced per row, so count should be 3 here
        expect($count)->toBe(3);
    });

    it('zipWith is lazy relative to the longest other iterable', function (): void {
        $count = 0;
        $other = (function () use (&$count): Generator {
            foreach (['X', 'Y', 'Z'] as $v) {
                $count++;
                yield $v;
            }
        })();
        $data = [1, 2];
        $rows = toArray(zipWith($other)($data));
        expect($rows)->toBe([[1, 'X'], [2, 'Y']]);
        expect($count)->toBe(3);
    });

    it('chunks into fixed sizes, last chunk may be smaller; keys discarded', function (): void {
        $input  = ['x' => 1, 'y' => 2, 'z' => 3, 'w' => 4, 'v' => 5];
        $chunks = toArray(chunk($input, 2));
        expect($chunks)->toBe([[1, 2], [3, 4], [5]]);
    });

    it('chunk throws on non-positive size', function (): void {
        expect(fn (): array => toArray(chunk([1, 2, 3], 0)))->toThrow(InvalidArgumentException::class);
        expect(fn (): array => toArray(chunk([1, 2, 3], -5)))->toThrow(InvalidArgumentException::class);
    });

    it('min and max return null for empty input', function (): void {
        expect(min([]))->toBeNull();
        expect(max([]))->toBeNull();
    });

    it('min and max work for ints', function (): void {
        $data = [5, 2, 9, -1, 3];
        expect(min($data))->toBe(-1);
        expect(max($data))->toBe(9);
    });

    it('min and max work for strings (lexicographical)', function (): void {
        $data = ['pear', 'apple', 'banana'];
        expect(min($data))->toBe('apple');
        expect(max($data))->toBe('pear');
    });
});
