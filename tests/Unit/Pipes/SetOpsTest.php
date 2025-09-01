<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\difference;
use function Denprog\RiverFlow\Pipes\intersection;
use function Denprog\RiverFlow\Pipes\symmetricDifference;
use function Denprog\RiverFlow\Pipes\union;

use Generator;

describe('Set operations (union, intersection, difference, symmetricDifference)', function (): void {
    it('union: combines unique values preserving first occurrence keys (strict)', function (): void {
        $a = [0 => 1, 1 => 2, 2 => '2', 3 => 3, 'k' => 4, 'm' => '4.0', 7 => 4];
        $b = ['a' => 2, 'b' => 5, 'c' => '2', 'd' => 6, 'e' => '4.0', 'f' => 4];

        $expected = [
            0   => 1,
            1   => 2,
            2   => '2',
            3   => 3,
            'k' => 4,
            'm' => '4.0',
            'b' => 5,
            'd' => 6,
        ];

        expect(union($a, $b))->toBe($expected);
    });

    it('intersection: only values present in both, preserving left keys (strict)', function (): void {
        $a = [0 => 1, 1 => 2, 2 => '2', 3 => 3, 'k' => 4, 'm' => '4.0', 7 => 4];
        $b = ['a' => 2, 'x' => '2', 'y' => 4, 'z' => '4.0'];

        $expected = [
            1   => 2,
            2   => '2',
            'k' => 4,
            'm' => '4.0',
        ];

        expect(intersection($a, $b))->toBe($expected);
    });

    it('difference: elements in left not in right (strict), left duplicates collapsed to first', function (): void {
        $a = [0 => 1, 1 => 2, 2 => '2', 3 => 3, 'k' => 4, 'm' => '4.0', 7 => 4, 8 => 3];
        $b = ['a' => 2, 'x' => '2', 'y' => 4, 'z' => '4.0'];

        $expected = [
            0 => 1,
            3 => 3,
        ];

        expect(difference($a, $b))->toBe($expected);
    });

    it('symmetricDifference: values present in exactly one iterable, left-only first then right-only; keys preserved', function (): void {
        $a = [0 => 1, 1 => 2, 2 => '2', 3 => 3, 'k' => 4];
        $b = ['a' => 2, 'b' => 5, 'c' => '2', 'd' => 6, 'e' => 7];

        $expected = [
            0   => 1,
            3   => 3,
            'k' => 4,
            'b' => 5,
            'd' => 6,
            'e' => 7,
        ];

        expect(symmetricDifference($a, $b))->toBe($expected);
    });

    it('object identity: only the same instance intersects; different instances do not', function (): void {
        $objSame  = (object)['id' => 1];
        $objAlias = $objSame;
        $objDiff  = (object)['id' => 1];

        $a = ['a' => $objSame, 'b' => $objDiff];
        $b = ['x' => $objAlias, 'y' => (object)['id' => 1]]; // alias intersects, new instance does not

        $inter = intersection($a, $b);
        expect($inter)->toHaveKey('a');
        expect($inter['a'])->toBe($objSame);
        expect($inter)->not->toHaveKey('b');

        // difference: remove anything present in right (by identity)
        $diff = difference($a, $b);
        expect($diff)->toHaveKey('b');
        expect($diff)->not->toHaveKey('a');
    });

    it('unhashable values (e.g., arrays with closures) are skipped in all set ops', function (): void {
        $closure1 = fn (): string => 'x';
        $closure2 = fn (): string => 'y';
        $bad1     = ['a' => 1, 'c' => $closure1]; // not serializable
        $bad2     = ['b' => 2, 'c' => $closure2]; // not serializable
        $good     = ['a' => 1, 'b' => 2];         // serializable

        // union: only $good remains
        expect(union([$bad1, $good], [$bad2]))->toBe([1 => $good]);

        // intersection: empty, since unhashables are skipped and $good is not present in right
        expect(intersection([$bad1, $good], [$bad2]))->toBe([]);

        // difference: good remains if not in right
        expect(difference([$bad1, $good], [$bad2]))->toBe([1 => $good]);

        // symmetricDifference: good from left only
        expect(symmetricDifference([$bad1, $good], [$bad2]))->toBe([1 => $good]);
    });

    it('currying works for all set ops', function (): void {
        $a = [0 => 1, 1 => 2, 2 => '2'];
        $b = ['x' => 2, 'y' => 3];

        $u = union($b);
        $i = intersection($b);
        $d = difference($b);
        $s = symmetricDifference($b);

        expect($u($a))->toBe(union($a, $b));
        expect($i($a))->toBe(intersection($a, $b));
        expect($d($a))->toBe(difference($a, $b));
        expect($s($a))->toBe(symmetricDifference($a, $b));
    });

    it('works with generators and preserves left keys where specified', function (): void {
        $makeA = static function (): Generator {
            yield 'k1' => 10;
            yield 'k2' => 20;
            yield 'k3' => 30;
            yield 'k4' => 20;
        };
        $makeB = static function (): Generator {
            yield 'b1' => 15;
            yield 'b2' => 20;
            yield 'b3' => 35;
        };

        // Fresh generators per call (generators are single-pass)
        expect(intersection($makeA(), $makeB()))->toBe(['k2' => 20]);
        expect(difference($makeA(), $makeB()))->toBe(['k1' => 10, 'k3' => 30]);
        expect(symmetricDifference($makeA(), $makeB()))->toBe(['k1' => 10, 'k3' => 30, 'b1' => 15, 'b3' => 35]);

        // union with generators (single usage)
        $genA2 = (static function (): Generator {
            yield 'a' => 1;
            yield 'b' => 2;
        })();
        $genB2 = (static function (): Generator {
            yield 'b1' => 2;
            yield 'c' => 3;
        })();
        expect(union($genA2, $genB2))->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
    });
});
