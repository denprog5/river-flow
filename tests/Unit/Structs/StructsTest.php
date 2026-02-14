<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Structs;

use function Denprog\RiverFlow\Structs\evolve;
use function Denprog\RiverFlow\Structs\getPath;
use function Denprog\RiverFlow\Structs\getPathOr;
use function Denprog\RiverFlow\Structs\omit;
use function Denprog\RiverFlow\Structs\pick;
use function Denprog\RiverFlow\Structs\setPath;
use function Denprog\RiverFlow\Structs\unzipAssoc;
use function Denprog\RiverFlow\Structs\updatePath;
use function Denprog\RiverFlow\Structs\zipAssoc;

use InvalidArgumentException;

describe('Structs: pick/omit', function (): void {
    it('pick selects only specified keys from arrays and objects (direct + curried)', function (): void {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        expect(pick(['a', 'c'], $data))->toBe(['a' => 1, 'c' => 3]);

        $p = pick(['b']);
        expect($p($data))->toBe(['b' => 2]);

        $obj = (object) ['x' => 10, 'y' => 20];
        expect(pick(['x', 'z'], $obj))->toBe(['x' => 10]);
    });

    it('omit removes specified keys from arrays and objects (direct + curried)', function (): void {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        expect(omit(['b'], $data))->toBe(['a' => 1, 'c' => 3]);

        $o = omit(['a', 'c']);
        expect($o($data))->toBe(['b' => 2]);

        $obj = (object) ['x' => 10, 'y' => 20, 'z' => 30];
        expect(omit(['y'], $obj))->toBe(['x' => 10, 'z' => 30]);
    });
});

describe('Structs: getPath/getPathOr', function (): void {
    it('getPath navigates arrays and objects returning null for missing', function (): void {
        $data = ['user' => ['name' => 'Ann', 'age' => 30], 'tags' => ['a', 'b']];
        expect(getPath(['user', 'name'], $data))->toBe('Ann');
        expect(getPath(['tags', 1], $data))->toBe('b');
        expect(getPath(['missing'], $data))->toBeNull();

        $obj = (object) ['user' => (object) ['name' => 'Bob']];
        expect(getPath(['user', 'name'], $obj))->toBe('Bob');

        $gp = getPath(['user', 'age']);
        expect($gp($data))->toBe(30);
    });

    it('getPath with empty path returns original input', function (): void {
        $arr = ['a' => 1];
        $obj = (object) ['a' => 1];

        expect(getPath([], $arr))->toBe($arr);
        expect(getPath([], $obj))->toBe($obj);
    });

    it('getPathOr returns default when missing (direct + curried)', function (): void {
        $data = ['x' => ['y' => 1]];
        expect(getPathOr(['x', 'z'], 99, $data))->toBe(99);

        $gpo = getPathOr(['x', 'y'], 'no');
        expect($gpo($data))->toBe(1);
        expect($gpo(['x' => []]))->toBe('no');
    });

    it('getPathOr returns default when resolved value is null', function (): void {
        $data = ['x' => ['y' => null]];

        expect(getPathOr(['x', 'y'], 'fallback', $data))->toBe('fallback');
    });
});

describe('Structs: setPath/updatePath (immutable arrays)', function (): void {
    it('setPath creates nested arrays and sets value immutably', function (): void {
        $data = [];
        $new  = setPath(['a', 'b', 'c'], 42, $data);
        expect($data)->toBe([]); // original untouched
        expect($new)->toBe(['a' => ['b' => ['c' => 42]]]);

        $sp = setPath(['x', 0], 'hi');
        expect($sp(['x' => []]))->toBe(['x' => ['hi']]);
    });

    it('updatePath updates via callback; missing receives null; path must not be empty', function (): void {
        $data = ['a' => ['b' => 1]];
        $new  = updatePath(['a', 'b'], fn ($cur): int|float => ($cur ?? 0) + 1, $data);
        expect($new)->toBe(['a' => ['b' => 2]]);
        expect($data)->toBe(['a' => ['b' => 1]]);

        $nu = updatePath(['a', 'c'], fn ($cur): int|float => ($cur ?? 10) * 2, $data);
        expect($nu)->toBe(['a' => ['b' => 1, 'c' => 20]]);

        expect(fn (): callable|array => setPath([], 1, []))->toThrow(InvalidArgumentException::class);
        expect(fn (): array|callable => updatePath([], fn ($x) => $x, []))->toThrow(InvalidArgumentException::class);
    });
});

describe('Structs: evolve', function (): void {
    it('evolve applies functions to existing keys only (direct + curried)', function (): void {
        $data = ['count' => 1, 'name' => 'Ann'];
        $spec = [
            'count' => fn (int $n): int => $n + 1,
            'name'  => strtoupper(...),
            'skip'  => fn ($x): string => 'no',
        ];

        $out = evolve($spec, $data);
        expect($out)->toBe(['count' => 2, 'name' => 'ANN']);

        $ev = evolve($spec);
        expect($ev(['name' => 'Bob']))->toBe(['name' => 'BOB']);
    });
});

describe('Structs: zipAssoc/unzipAssoc', function (): void {
    it('zipAssoc pairs keys with values and stops at shortest (direct + curried)', function (): void {
        $keys = ['a', 'b', 'c'];
        $vals = [1, 2];
        expect(zipAssoc($keys, $vals))->toBe(['a' => 1, 'b' => 2]);

        $z = zipAssoc(['x', 'y']);
        expect($z([10, 20, 30]))->toBe(['x' => 10, 'y' => 20]);
    });

    it('unzipAssoc splits 2-tuples; ignores malformed pairs', function (): void {
        $pairs     = [['k1', 1], ['k2', 2], ['bad'], ['k3', 3]];
        [$ks, $vs] = unzipAssoc($pairs);
        expect($ks)->toBe(['k1', 'k2', 'k3']);
        expect($vs)->toBe([1, 2, 3]);
    });
});
