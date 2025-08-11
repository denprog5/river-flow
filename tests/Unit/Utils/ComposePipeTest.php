<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Utils;

use function Denprog\RiverFlow\Utils\compose;
use function Denprog\RiverFlow\Utils\pipe;

describe('Utils compose and pipe', function (): void {
    it('compose applies functions right-to-left and supports multiple args for the innermost function', function (): void {
        $sum = fn (int $a, int $b): int => $a + $b; // innermost (right-most)
        $inc = fn (int $x): int => $x + 1;
        $dbl = fn (int $x): int => $x * 2;

        $fn  = compose($dbl, $inc, $sum); // dbl(inc(sum(a,b)))
        $res = $fn(3, 4); // sum=7 -> inc=8 -> dbl=16
        expect($res)->toBe(16);
    });

    it('compose with no functions returns identity', function (): void {
        $id = compose();
        expect($id(123))->toBe(123);
        expect($id('x'))->toBe('x');
    });

    it('pipe applies functions left-to-right starting from initial value', function (): void {
        $res = pipe(
            5,
            fn (int $x): int => $x + 3, // 8
            fn (int $x): int => $x * 2, // 16
            'strval',                   // "16"
        );
        expect($res)->toBe('16');
    });

    it('pipe with no functions returns the original value', function (): void {
        expect(pipe('hello'))->toBe('hello');
        expect(pipe(0))->toBe(0);
    });
});
