<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Pipes;

use function Denprog\RiverFlow\Pipes\splitAt;
use function Denprog\RiverFlow\Pipes\splitWhen;

use InvalidArgumentException;

describe('splitAt and splitWhen', function (): void {
    it('splitAt splits at index and discards keys', function (): void {
        $input          = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        [$left, $right] = splitAt($input, 2);
        expect($left)->toBe([1, 2]);
        expect($right)->toBe([3, 4]);

        [$l2, $r2] = splitAt($input, 0);
        expect($l2)->toBe([]);
        expect($r2)->toBe([1, 2, 3, 4]);

        [$l3, $r3] = splitAt($input, -3);
        expect($l3)->toBe([]);
        expect($r3)->toBe([1, 2, 3, 4]);

        [$l4, $r4] = splitAt($input, 10);
        expect($l4)->toBe([1, 2, 3, 4]);
        expect($r4)->toBe([]);
    });

    it('splitAt supports currying', function (): void {
        $split1  = splitAt(1);
        [$l, $r] = $split1(['x' => 10, 'y' => 20]);
        expect($l)->toBe([10]);
        expect($r)->toBe([20]);
    });

    it('splitWhen splits at first match; matched element in right part; keys discarded', function (): void {
        $input            = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        [$before, $after] = splitWhen($input, fn (int $v): bool => $v >= 3);
        expect($before)->toBe([1, 2]);
        expect($after)->toBe([3, 4]);
    });

    it('splitWhen uses key in predicate', function (): void {
        $input            = ['a' => 1, 'b' => 2, 'c' => 3];
        [$before, $after] = splitWhen(fn ($v, $k): bool => $k === 'b')($input);
        expect($before)->toBe([1]);
        expect($after)->toBe([2, 3]);
    });

    it('splitWhen returns [all, []] if no match; and [[], all] if match at first', function (): void {
        [$b1, $a1] = splitWhen([1, 2, 3], fn ($v): bool => false);
        expect($b1)->toBe([1, 2, 3]);
        expect($a1)->toBe([]);

        [$b2, $a2] = splitWhen([1, 2, 3], fn ($v): bool => $v === 1);
        expect($b2)->toBe([]);
        expect($a2)->toBe([1, 2, 3]);
    });

    it('splitWhen supports currying form', function (): void {
        $fn           = splitWhen(fn (int $v): bool => $v === 2);
        [$pre, $post] = $fn([1, 2, 3]);
        expect($pre)->toBe([1]);
        expect($post)->toBe([2, 3]);
    });

    it('splitWhen throws on invalid direct-call arguments', function (): void {
        // first arg is callable (not iterable) in direct invocation => invalid
        expect(fn (): array => splitWhen('strlen', fn (): bool => true))->toThrow(InvalidArgumentException::class);
    });
});
