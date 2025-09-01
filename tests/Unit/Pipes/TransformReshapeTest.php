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

use InvalidArgumentException;

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
            public int $id      = 2;
            public string $name = 'bob';
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

    it('groupBy supports flexible order and currying', function (): void {
        $data    = ['k1' => 'ant', 'k2' => 'apple', 'k3' => 'bee'];
        $grouper = fn (string $s): string => $s[0];

        // Flexible order: groupBy($grouper, $data)
        $out1 = groupBy($grouper, $data);
        expect($out1)->toBe([
            'a' => ['k1' => 'ant', 'k2' => 'apple'],
            'b' => ['k3' => 'bee'],
        ]);

        // Curried: groupBy($grouper)($data)
        $fn   = groupBy($grouper);
        $out2 = $fn($data);
        expect($out2)->toBe($out1);
    });

    it('groupBy throws if grouper does not return array-key', function (): void {
        $data = ['a' => 'ant', 'b' => 'bee'];
        expect(fn (): callable|array => groupBy($data, fn (string $s): array => []))
            ->toThrow(InvalidArgumentException::class);
    });

    it('keyBy supports flexible order and currying', function (): void {
        $rows = [
            ['id' => 'x', 'v' => 1],
            ['id' => 'y', 'v' => 2],
        ];
        $keyer = fn (array $r): string => $r['id'];

        // Flexible order: keyBy($keyer, $data)
        $out1 = keyBy($keyer, $rows);
        expect($out1)->toBe([
            'x' => ['id' => 'x', 'v' => 1],
            'y' => ['id' => 'y', 'v' => 2],
        ]);

        // Curried: keyBy($keyer)($data)
        $fn   = keyBy($keyer);
        $out2 = $fn($rows);
        expect($out2)->toBe($out1);
    });

    it('keyBy throws if keyer does not return array-key', function (): void {
        $rows = [
            ['id' => 'x', 'v' => 1],
        ];
        expect(fn (): callable|array => keyBy($rows, fn (array $r): array => []))
            ->toThrow(InvalidArgumentException::class);
    });

    it('take is lazy and pulls only needed items', function (): void {
        $iterations = 0;
        $source     = (function () use (&$iterations) {
            foreach ([10, 20, 30] as $v) {
                $iterations++;
                yield $v;
            }
        })();

        $stream = take($source, 2);
        expect($iterations)->toBe(0);

        // Start iteration but stop after first item
        $stream->valid(); // advances to first yield
        expect($iterations)->toBe(1);
        expect($stream->key())->toBe(0);
        expect($stream->current())->toBe(10);

        // Advance to the next item and verify it
        $stream->next();
        expect($stream->valid())->toBeTrue();
        expect($stream->key())->toBe(1);
        expect($stream->current())->toBe(20);

        // Move past the last allowed item and ensure stream ends
        $stream->next();
        expect($stream->valid())->toBeFalse();
        expect($iterations)->toBe(2);
    });
});
