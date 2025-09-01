<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Pipes\uniqBy;

use Generator;

describe('uniqBy function', function (): void {
    $users = [
        'u1' => ['id' => 1, 'name' => 'Alice',   'city' => 'London'],
        'u2' => ['id' => 2, 'name' => 'Bob',     'city' => 'Paris'],
        'u3' => ['id' => 1, 'name' => 'Alicia',  'city' => 'London'], // duplicate by id
        'u4' => ['id' => 3, 'name' => 'Charlie', 'city' => 'London'],
        'u5' => ['id' => 2, 'name' => 'Bobby',   'city' => 'Berlin'], // duplicate by id
    ];

    $usersGeneratorFactory = static fn (): Generator => (static fn () => yield from $users)();

    dataset('uniqByData', [
        'uniq users by id' => [
            $users,
            fn (array $user) => $user['id'],
            [
                'u1' => ['id' => 1, 'name' => 'Alice',   'city' => 'London'],
                'u2' => ['id' => 2, 'name' => 'Bob',     'city' => 'Paris'],
                'u4' => ['id' => 3, 'name' => 'Charlie', 'city' => 'London'],
            ],
        ],
        'uniq users by city (generator)' => [
            $usersGeneratorFactory(),
            fn (array $user) => $user['city'],
            [
                'u1' => ['id' => 1, 'name' => 'Alice',   'city' => 'London'],
                'u2' => ['id' => 2, 'name' => 'Bob',     'city' => 'Paris'],
                'u5' => ['id' => 2, 'name' => 'Bobby',   'city' => 'Berlin'],
            ],
        ],
        'uniq numbers by their integer value' => [
            [1, 2, '2', 3, 1.0, 4, '4.0', 'item'],
            fn ($val): int|string => is_numeric($val) ? (int) $val : (string) $val,
            [0                    => 1, 1 => 2, 3 => 3, 5 => 4, 7 => 'item'],
        ],
        'empty collection for uniqBy' => [[], fn ($x) => $x, []],
        'uniqBy object identifier'    => [
            [ (object)['group' => 'A', 'val' => 1],
              (object)['group' => 'B', 'val' => 2],
              (object)['group' => 'A', 'val' => 3] ],
            fn (object $item) => $item->group,
            [ 0 => (object)['group' => 'A', 'val' => 1],
              1 => (object)['group' => 'B', 'val' => 2] ],
        ],
    ]);

    /**
     * @template TKey of array-key
     * @template TValue
     */
    it('returns unique items based on the identifier callback result', function (iterable $inputIterable, callable $identifier, array $expectedArray): void {
        /** @var iterable<TKey, TValue> $inputIterable */
        $generator = uniqBy($inputIterable, $identifier);
        expect($generator)->toBeInstanceOf(Generator::class);
        expect(toArray($generator))->toEqual($expectedArray);
    })->with('uniqByData');

    it('is lazy', function (): void {
        $iterations = 0;
        $sourceData = [
            ['id' => 1, 'val' => 'a'], ['id' => 2, 'val' => 'b'], ['id' => 1, 'val' => 'c'],
            ['id' => 3, 'val' => 'd'], ['id' => 2, 'val' => 'e'],
        ];
        $source = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $item) {
                $iterations++;
                yield $item;
            }
        })();

        $uniqueById = uniqBy($source, fn (array $item): int => $item['id']);
        expect($iterations)->toBe(0);

        $result = [];
        $count  = 0;
        foreach ($uniqueById as $unique) {
            $result[] = $unique;
            $count++;
            if ($count >= 2) {
                break;
            }
        }

        expect($result)->toHaveCount(2);
        expect($iterations)->toBe(2);

        // Continue without rewinding
        $uniqueById->next();
        while ($uniqueById->valid()) {
            $result[] = $uniqueById->current();
            $uniqueById->next();
        }

        expect($result)->toHaveCount(3);
        expect($iterations)->toBe(\count($sourceData));
    });

    it('supports flexible argument order and currying', function (): void {
        $data = [
            'u1' => ['id' => 1, 'name' => 'A'],
            'u2' => ['id' => 1, 'name' => 'B'],
            'u3' => ['id' => 2, 'name' => 'C'],
        ];

        // Flexible order: uniqBy($identifier, $data)
        $out1 = toArray(uniqBy(fn (array $row): int => $row['id'], $data));
        expect($out1)->toBe([
            'u1' => ['id' => 1, 'name' => 'A'],
            'u3' => ['id' => 2, 'name' => 'C'],
        ]);

        // Curried form: uniqBy($identifier)($data)
        $fn   = uniqBy(fn (array $row): int => $row['id']);
        $out2 = toArray($fn($data));
        expect($out2)->toBe($out1);
    });

    it('skips items whose identifiers are unhashable (e.g., resources)', function (): void {
        $data = [
            'a' => ['id' => 1, 'v' => 'x'],
            'b' => ['id' => 1, 'v' => 'y'],
            'c' => ['id' => 2, 'v' => 'z'],
        ];

        // Return a resource for id=1 (unhashable) and a scalar for id=2 (hashable)
        $handle     = fopen('php://temp', 'r');
        $identifier = function (array $row) use ($handle): mixed {
            if ($row['id'] === 1) {
                return $handle; // resources are unhashable and should be skipped
            }

            return $row['id'];
        };

        $out = toArray(uniqBy($data, $identifier));
        // Both 'a' and 'b' are skipped due to unhashable identifiers; only 'c' remains
        expect($out)->toBe([
            'c' => ['id' => 2, 'v' => 'z'],
        ]);

        fclose($handle);
    });
});
