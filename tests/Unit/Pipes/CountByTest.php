<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\countBy;

use Generator;
use InvalidArgumentException;

describe('countBy function', function (): void {
    it('counts elements by classifier (array input)', function (): void {
        $data = ['apple','apricot','banana','blueberry','avocado'];
        $out  = countBy($data, fn (string $s): string => $s[0]);
        expect($out)->toBe(['a' => 3, 'b' => 2]);
    });

    it('supports generator input', function (): void {
        $src = ['ant','ape','bear','bob','cat'];
        $gen = (static function () use ($src): Generator {
            foreach ($src as $v) {
                yield $v;
            }
        })();

        $out = countBy($gen, fn (string $s): string => $s[0]);
        expect($out)->toBe(['a' => 2, 'b' => 2, 'c' => 1]);
    });

    it('supports flexible order (classifier, data)', function (): void {
        $data = ['a1','a2','b1'];
        $out  = countBy(fn (string $s): string => $s[0], $data);
        expect($out)->toBe(['a' => 2, 'b' => 1]);
    });

    it('supports currying', function (): void {
        $data = ['x','y','yx','yy'];
        $fn   = countBy(fn (string $s): string => $s[0]);
        $out  = $fn($data);
        expect($out)->toBe(['x' => 1, 'y' => 3]);
    });

    it('returns empty array for empty input', function (): void {
        $out = countBy([], fn ($x): mixed => $x);
        expect($out)->toBe([]);
    });

    it('throws when classifier does not return array-key', function (): void {
        $data = [1, 2, 3];
        $bad  = (fn (int $n): object => (object)['k' => $n]);
        expect(fn (): callable|array => countBy($data, $bad))->toThrow(InvalidArgumentException::class);
    });
});
