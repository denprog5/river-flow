<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\toArray;
use function Denprog\RiverFlow\Pipes\uniq;

use Generator;

describe('uniq function (strict, lazy)', function (): void {
    dataset('uniqSimpleData', [
        'empty array'                             => [[], []],
        'numbers with duplicates strict detailed' => [
            [0 => 1, 1 => 2, 2 => '2', 3 => 3, 4 => 1, 5 => 4, 6 => '4.0', 7 => 4, 8 => '2'],
            // Expect: 1 (0), 2 (1), '2' (2), 3 (3), 4 (5), '4.0' (6)
            [0 => 1, 1 => 2, 2 => '2', 3 => 3, 5 => 4, 6 => '4.0'],
        ],
        'strings with duplicates strict' => [
            ['a' => 'apple', 'b' => 'banana', 'c' => 'apple', 'd' => 'orange', 'e' => 'Banana'],
            ['a' => 'apple', 'b' => 'banana', 'd' => 'orange', 'e' => 'Banana'],
        ],
        'null and bool values' => [
            [null, true, false, null, true, 0, '0', false, ''],
            [0 => null, 1 => true, 2 => false, 5 => 0, 6 => '0', 8 => ''],
        ],
        'generator with strict duplicates' => [
            (static function (): Generator {
                yield 'k1' => 100;
                yield 'k2' => 200;
                yield 'k3' => 100;
                yield 'k4' => '100';
            })(),
            ['k1' => 100, 'k2' => 200, 'k4' => '100'],
        ],
    ]);

    it('returns only unique values preserving first occurrence keys (strict comparison)', function (iterable $inputIterable, array $expectedArray): void {
        $generator = uniq($inputIterable);
        expect($generator)->toBeInstanceOf(Generator::class);
        expect(toArray($generator))->toBe($expectedArray);
    })->with('uniqSimpleData');

    it('is lazy and iterates source only as needed', function (): void {
        $iterations = 0;
        $sourceData = [1, 2, 2, 3, 3, 3, 1, 4]; // Unique: 1,2,3,4
        $source     = (function () use (&$iterations, $sourceData): Generator {
            foreach ($sourceData as $item) {
                $iterations++;
                yield $item;
            }
        })();

        $uniqueStream = uniq($source);
        expect($iterations)->toBe(0);

        $result = [];
        $count  = 0;
        foreach ($uniqueStream as $value) {
            $result[] = $value;
            $count++;
            if ($count >= 2) {
                break;
            }
        }

        expect($result)->toBe([1, 2]);
        // Minimal laziness: only two source iterations are needed to produce two unique values
        expect($iterations)->toBe(2);

        // Continue consumption without rewinding the generator
        // After breaking the foreach, the generator still points to the last yielded value;
        // advance once before continuing to avoid duplicating it.
        $uniqueStream->next();
        while ($uniqueStream->valid()) {
            $result[] = $uniqueStream->current();
            $uniqueStream->next();
        }
        expect($result)->toBe([1, 2, 3, 4]);
        expect($iterations)->toBe(\count($sourceData));
    });

    it('handles arrays with non-serializable (closures) by skipping them', function (): void {
        $closure1 = fn (): string => 'closure1';
        $closure2 = fn (): string => 'closure2';
        $data     = [
            ['a' => 1, 'c' => $closure1], // not serializable
            ['a' => 1, 'b' => 2],         // serializable
            ['a' => 1, 'c' => $closure2], // not serializable
            ['a' => 1, 'b' => 2],         // duplicate of second by value
        ];
        $result = toArray(uniq($data));
        expect($result)->toBe([1 => ['a' => 1, 'b' => 2]]);
    });

    it('objects are unique by identity (spl_object_hash)', function (): void {
        $obj1      = (object)['id' => 1];
        $obj1Alias = $obj1; // same instance
        $obj2      = (object)['id' => 1]; // different instance
        $obj3      = (object)['id' => 2];
        $input     = [$obj1, $obj2, $obj1Alias, $obj3];
        $expected  = [0 => $obj1, 1 => $obj2, 3 => $obj3];
        expect(toArray(uniq($input)))->toEqual($expected);
    });
});
