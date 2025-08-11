<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Utils;

use function Denprog\RiverFlow\Utils\identity;

use stdClass;

describe('Utils identity', function (): void {
    it('returns the same scalar value', function (): void {
        expect(identity(123))->toBe(123);
        expect(identity('abc'))->toBe('abc');
        expect(identity(true))->toBe(true);
        expect(identity(null))->toBeNull();
    });

    it('returns an equal array value', function (): void {
        $arr = ['a' => 1, 'b' => 2];
        $out = identity($arr);
        expect($out)->toBe($arr);
    });

    it('returns the identical object instance', function (): void {
        $obj      = new stdClass();
        $obj->foo = 'bar';
        $out      = identity($obj);
        expect($out)->toBe($obj);
    });
});
