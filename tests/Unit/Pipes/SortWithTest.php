<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use Generator;
use InvalidArgumentException;

use function Denprog\RiverFlow\Pipes\sortWith;
use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Utils\ascend;
use function Denprog\RiverFlow\Utils\descend;

describe('sortWith', function (): void {
    it('sorts by a single comparator (ascend by age); preserves keys', function (): void {
        $people = [
            'u3' => ['name' => 'Cara', 'age' => 40],
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
        ];

        $byAge = ascend(fn (array $p) => $p['age']);
        $sorted = sortWith($people, $byAge);

        expect($sorted)->toBe([
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
            'u3' => ['name' => 'Cara',  'age' => 40],
        ]);
    });

    it('supports multiple comparators (tie-breaker)', function (): void {
        $rows = [
            'k1' => ['name' => 'Bob'],
            'k2' => ['name' => 'Al'],
            'k3' => ['name' => 'Ann'],
            'k4' => ['name' => 'Bo'],
        ];

        $byLen  = ascend(fn (array $r) => \strlen($r['name']));
        $byName = ascend(fn (array $r) => $r['name']);

        $sorted = sortWith($rows, $byLen, $byName);

        expect($sorted)->toBe([
            'k2' => ['name' => 'Al'],
            'k4' => ['name' => 'Bo'],
            'k3' => ['name' => 'Ann'],
            'k1' => ['name' => 'Bob'],
        ]);
    });

    it('supports currying: sortWith(comparators...) returns callable', function (): void {
        $people = [
            'u3' => ['name' => 'Cara', 'age' => 40],
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
        ];

        $byAge = ascend(fn (array $p) => $p['age']);
        $fn    = sortWith($byAge);
        $sorted = $fn($people);

        expect($sorted)->toBe([
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
            'u3' => ['name' => 'Cara',  'age' => 40],
        ]);
    });

    it('works with generators (iterable) and preserves keys', function (): void {
        $gen = (function (): Generator {
            yield 'b' => 2;
            yield 'c' => 3;
            yield 'a' => 1;
        })();

        $byVal = ascend(fn (int $v) => $v);
        $sorted = sortWith($gen, $byVal);
        expect($sorted)->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
    });

    it('throws in direct invocation when no comparator provided', function (): void {
        $data = ['b' => 2, 'a' => 1];
        expect(fn () => sortWith($data))->toThrow(InvalidArgumentException::class);
    });

    it('can use descend for reverse order', function (): void {
        $assoc = ['a' => 1, 'b' => 3, 'c' => 2];
        $byValDesc = descend(fn (int $v) => $v);
        $sorted = sortWith($assoc, $byValDesc);
        expect($sorted)->toBe(['b' => 3, 'c' => 2, 'a' => 1]);
    });
});
