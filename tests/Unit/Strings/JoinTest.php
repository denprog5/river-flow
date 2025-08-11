<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\join;

use InvalidArgumentException;
use Stringable;

describe('Strings join', function () {
    it('joins array elements with separator and casts scalars to string', function () {
        expect(join(['a', 'b', 'c'], ','))->toBe('a,b,c');
        expect(join([1, 2, 3], '-'))->toBe('1-2-3');
        expect(join([true, false, 0], ','))->toBe('1,,0'); // (string)true = '1', (string)false = ''
    });

    it('joins generator/iterable elements', function () {
        $gen = (function () {
            yield 1;
            yield 2;
            yield 3;
        })();
        expect(join($gen, ':'))->toBe('1:2:3');
    });

    it('supports Stringable values', function () {
        $s = new class () implements Stringable {
            public function __toString(): string
            {
                return 'X';
            }
        };
        expect(join([$s, $s], '+'))->toBe('X+X');

        $gen = (function () use ($s) {
            yield $s;
            yield 123;
            yield 'z';
        })();
        expect(join($gen, '|'))->toBe('X|123|z');
    });

    it('throws InvalidArgumentException for non-stringable elements in iterables', function () {
        $bad = (function () {
            yield new \stdClass();
        })();
        expect(fn () => join($bad, ','))->toThrow(InvalidArgumentException::class);
    });
});
