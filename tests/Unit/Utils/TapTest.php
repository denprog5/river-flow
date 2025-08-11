<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Utils;

use function Denprog\RiverFlow\Utils\tap;

use stdClass;

describe('Utils tap', function (): void {
    it('returns the original scalar value and invokes callback', function (): void {
        $called = 0;
        $seen   = null;
        $v      = 42;
        $out    = tap($v, function ($x) use (&$called, &$seen): void {
            $called++;
            $seen = $x;
        });
        expect($out)->toBe($v);
        expect($called)->toBe(1);
        expect($seen)->toBe(42);
    });

    it('returns the same object instance (identity) and invokes callback with it', function (): void {
        $obj      = new stdClass();
        $obj->n   = 1;
        $received = null;
        $out      = tap($obj, function ($x) use (&$received): void { $received = $x; });
        expect($out)->toBe($obj);           // identical instance
        expect($received)->toBe($obj);      // callback saw the same instance
    });

    it('does not modify arrays unless callback mutates them explicitly', function (): void {
        $arr = [1, 2, 3];
        $out = tap($arr, function ($x): void { /* no-op */ });
        expect($out)->toBe($arr);
    });
});
