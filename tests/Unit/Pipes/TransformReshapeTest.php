<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\filter;
use function Denprog\RiverFlow\Pipes\groupBy;
use function Denprog\RiverFlow\Pipes\keyBy;
use function Denprog\RiverFlow\Pipes\keys;
use function Denprog\RiverFlow\Pipes\map;
use function Denprog\RiverFlow\Pipes\pluck;
use function Denprog\RiverFlow\Pipes\reject;
use function Denprog\RiverFlow\Pipes\sort;
use function Denprog\RiverFlow\Pipes\sortBy;
use function Denprog\RiverFlow\Pipes\take;
use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Pipes\toList;
use function Denprog\RiverFlow\Pipes\values;

describe('transform and reshaping (filter, reject, map, pluck, toList/toArray, values/keys, sortBy/sort, groupBy/keyBy, take)', function (): void {
    it('filter yields items where predicate true; preserves keys', function (): void {
        $in  = ['a' => 1, 'b' => 2, 'c' => 3];
        $out = toArray(filter($in, fn (int $v, string $k): bool => $v % 2 === 1));
        expect($out)->toBe(['a' => 1, 'c' => 3]);
    });

    it('reject is opposite of filter; preserves keys', function (): void {
        $in  = ['a' => 1, 'b' => 2, 'c' => 3];
        $out = toArray(reject($in, fn (int $v): bool => $v % 2 === 1));
        expect($out)->toBe(['b' => 2]);
    });

    it('map transforms values; preserves keys', function (): void {
        $in  = ['x' => 1, 'y' => 2];
        $out = toArray(map($in, fn (int $v, string $k): int => $v * 10));
        expect($out)->toBe(['x' => 10, 'y' => 20]);
    });

    it('pluck reads array keys or object public props; preserves original keys; uses default when missing', function (): void {
        $obj = new class () {
            public int $id         = 2;
            public string $name    = 'bob';
        };
        $in  = [10 => ['id' => 1], 20 => $obj, 30 => ['foo' => 'bar']];
        $ids = toArray(pluck($in, 'id', -1));
        expect($ids)->toBe([10 => 1, 20 => 2, 30 => -1]);
    });

    it('toList discards keys and returns numeric list; toArray preserves keys', function (): void {
        $assoc = ['a' => 1, 'b' => 2];
        expect(toList($assoc))->toBe([1, 2]);
        expect(toArray($assoc))->toBe(['a' => 1, 'b' => 2]);
    });

    it('values discards keys; keys yields keys (both lazy)', function (): void {
        $assoc = ['a' => 1, 'b' => 2];
        expect(toArray(values($assoc)))->toBe([1, 2]);
        expect(toArray(keys($assoc)))->toBe(['a', 'b']);
    });

    it('sortBy sorts by derived comparable; preserves original keys', function (): void {
        $people = [
            'u3' => ['name' => 'Cara', 'age' => 40],
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
        ];
        $sorted = sortBy($people, fn (array $p): int => $p['age']);
        expect($sorted)->toBe([
            'u1' => ['name' => 'Alice', 'age' => 30],
            'u2' => ['name' => 'Bob',   'age' => 35],
            'u3' => ['name' => 'Cara',  'age' => 40],
        ]);
    });

    it('sort sorts scalar values ascending; preserves keys', function (): void {
        $assoc  = ['b' => 2, 'c' => 3, 'a' => 1];
        $sorted = sort($assoc);
        expect($sorted)->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
    });

    it('groupBy groups by key; inner arrays preserve original keys', function (): void {
        $fruits  = ['k1' => 'apple', 'k2' => 'avocado', 'k3' => 'banana'];
        $grouped = groupBy($fruits, fn (string $v): string => $v[0]);
        expect($grouped)->toBe([
            'a' => ['k1' => 'apple', 'k2' => 'avocado'],
            'b' => ['k3' => 'banana'],
        ]);
    });

    it('keyBy re-keys by selector; last wins on collisions', function (): void {
        $items = [
            ['id' => 'a', 'v' => 1],
            ['id' => 'b', 'v' => 2],
            ['id' => 'a', 'v' => 3], // overwrites first
        ];
        $out = keyBy($items, fn (array $row, int $i): string => $row['id']);
        expect($out)->toBe([
            'a' => ['id' => 'a', 'v' => 3],
            'b' => ['id' => 'b', 'v' => 2],
        ]);
    });

    it('take yields up to N items; preserves keys; handles non-positive N', function (): void {
        $assoc = ['x' => 10, 'y' => 20, 'z' => 30];
        expect(toArray(take($assoc, 2)))->toBe(['x' => 10, 'y' => 20]);
        expect(toArray(take($assoc, 0)))->toBe([]);
        expect(toArray(take($assoc, -5)))->toBe([]);
    });
});
