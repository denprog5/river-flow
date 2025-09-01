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
use IteratorAggregate;
use Traversable;
use TypeError;

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

    it('zip discards keys from input iterables', function (): void {
        $a    = ['ka' => 1, 'kb' => 2];
        $b    = ['kx' => 'x', 'ky' => 'y'];
        $rows = toArray(zip($a, $b));
        expect($rows)->toBe([[1, 'x'], [2, 'y']]);
    });

    it('zip with an empty iterable yields empty result', function (): void {
        expect(toArray(zip([], [1, 2])))->toBe([]);
        expect(toArray(zip([1, 2], [])))->toBe([]);
    });

    it('zip rewinds Iterators before zipping (ArrayIterator advanced)', function (): void {
        $it = new ArrayIterator([1, 2]);
        $it->next(); // advance to second element
        $rows = toArray(zip($it, [10, 20]));
        // Should start from the beginning due to rewind
        expect($rows)->toBe([[1, 10], [2, 20]]);
    });

    it('zip supports IteratorAggregate and rewinds its inner iterator', function (): void {
        $inner = new ArrayIterator([100, 200]);
        $inner->next(); // advance
        $agg = new readonly class ($inner) implements IteratorAggregate {
            public function __construct(private ArrayIterator $inner)
            {
            }
            public function getIterator(): Traversable
            {
                return $this->inner; // zip wraps with IteratorIterator and rewinds
            }
        };

        $rows = toArray(zip($agg, ['A', 'B', 'C']));
        expect($rows)->toBe([[100, 'A'], [200, 'B']]);
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

    it('chunk supports currying form', function (): void {
        $fn     = chunk(2);
        $chunks = toArray($fn(['a' => 1, 'b' => 2, 'c' => 3]));
        expect($chunks)->toBe([[1, 2], [3]]);
    });

    it('chunk with size 1 yields singleton chunks', function (): void {
        $chunks = toArray(chunk([1, 2, 3], 1));
        expect($chunks)->toBe([[1], [2], [3]]);
    });

    it('chunk on empty input yields no chunks', function (): void {
        $chunks = toArray(chunk([], 3));
        expect($chunks)->toBe([]);
    });

    it('chunk currying throws on invalid size', function (): void {
        expect(fn (): array => toArray(chunk(0)([1, 2, 3])))->toThrow(InvalidArgumentException::class);
        expect(fn (): array => toArray(chunk(-2)([1, 2])))->toThrow(InvalidArgumentException::class);
    });

    it('partition throws on invalid direct-call arguments', function (): void {
        // first arg is callable (not iterable) in direct invocation => invalid
        expect(fn (): array => partition('strlen', fn (): bool => true))->toThrow(InvalidArgumentException::class);
        // predicate not callable in direct invocation -> TypeError due to parameter type (?callable)
        /** @phpstan-ignore-next-line intentionally passing invalid predicate */
        expect(fn (): array => partition([1, 2, 3], 'not_callable'))->toThrow(TypeError::class);
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

    it('min and max work with floats and mixed numeric strings (return original types)', function (): void {
        $data = [1, 2.5, '3.5', 0.5];
        expect(min($data))->toBe(0.5);
        // max should be the original '3.5' string because comparison coerces numerically but returns the original item
        expect(max($data))->toBe('3.5');

        $mixed = ['10', 2];
        expect(min($mixed))->toBe(2);
        expect(max($mixed))->toBe('10');
    });

    it('min and max support currying form', function (): void {
        $minFn = min();
        $maxFn = max();
        expect($minFn([5, 1, 7]))->toBe(1);
        expect($maxFn([5, 1, 7]))->toBe(7);
    });
});
