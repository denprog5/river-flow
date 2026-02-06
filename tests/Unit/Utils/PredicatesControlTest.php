<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Utils;

use function Denprog\RiverFlow\Utils\allPass;
use function Denprog\RiverFlow\Utils\anyPass;
use function Denprog\RiverFlow\Utils\ascend;
use function Denprog\RiverFlow\Utils\both;
use function Denprog\RiverFlow\Utils\complement;
use function Denprog\RiverFlow\Utils\cond;
use function Denprog\RiverFlow\Utils\converge;
use function Denprog\RiverFlow\Utils\descend;
use function Denprog\RiverFlow\Utils\either;
use function Denprog\RiverFlow\Utils\ifElse;
use function Denprog\RiverFlow\Utils\memoizeWith;
use function Denprog\RiverFlow\Utils\once;
use function Denprog\RiverFlow\Utils\partial;
use function Denprog\RiverFlow\Utils\partialRight;
use function Denprog\RiverFlow\Utils\unless;
use function Denprog\RiverFlow\Utils\when;

use Stringable;

describe('Utils predicates and control/combinators', function (): void {
    it('complement negates predicate', function (): void {
        $even = fn (int $x): bool => $x % 2 === 0;
        $odd  = complement($even);
        expect($odd(2))->toBeFalse();
        expect($odd(3))->toBeTrue();
    });

    it('both and either combine predicates', function (): void {
        $gt1 = fn (int $x): bool => $x > 1;
        $lt5 = fn (int $x): bool => $x < 5;
        $b   = both($gt1, $lt5);
        $e   = either($gt1, $lt5);
        expect($b(3))->toBeTrue();
        expect($b(6))->toBeFalse();
        expect($e(0))->toBeTrue();
        expect($e(10))->toBeTrue();
        expect($e(2))->toBeTrue();
    });

    it('allPass and anyPass evaluate arrays of predicates', function (): void {
        $p1  = fn (int $x): bool => $x > 0;
        $p2  = fn (int $x): bool => $x % 2 === 0;
        $all = allPass([$p1, $p2]);
        $any = anyPass([$p1, $p2]);
        expect($all(2))->toBeTrue();
        expect($all(1))->toBeFalse();
        expect($any(1))->toBeTrue();
        expect($any(-2))->toBeTrue();
        expect($any(-1))->toBeFalse();
    });

    it('when and unless conditionally transform values', function (): void {
        $isStr = fn (mixed $x): bool => \is_string($x);
        $wrap  = fn (mixed $x): string => "[$x]";
        $w     = when($isStr, $wrap);
        $u     = unless($isStr, $wrap);
        expect($w('x'))->toBe('[x]');
        expect($w(5))->toBe(5);
        expect($u('x'))->toBe('x');
        expect($u(5))->toBe('[5]');
    });

    it('ifElse selects branch based on predicate', function (): void {
        $pred = fn (int $x): bool => $x >= 0;
        $pos  = fn (int $x): string => 'pos:' . $x;
        $neg  = fn (int $x): string => 'neg:' . $x;
        $fn   = ifElse($pred, $pos, $neg);
        expect($fn(3))->toBe('pos:3');
        expect($fn(-1))->toBe('neg:-1');
    });

    it('cond applies first matching pair and returns null when none match', function (): void {
        $pairs = [
            [fn (int $x): bool => $x < 0, fn (int $x): string => 'neg'],
            [fn (int $x): bool => $x === 0, fn (int $x): string => 'zero'],
            [fn (int $x): bool => $x > 0, fn (int $x): string => 'pos'],
        ];
        $fn = cond($pairs);
        expect($fn(-5))->toBe('neg');
        expect($fn(0))->toBe('zero');
        expect($fn(7))->toBe('pos');
        expect($fn(0))->not->toBeNull();
        // empty pairs -> always null
        $none = cond([]);
        expect($none(123))->toBeNull();
    });

    it('converge collects branch results and applies after', function (): void {
        $after    = fn (int $a, int $b): int => $a + $b;
        $branches = [
            \strlen(...),
            fn (string $s): int => \ord($s[0]),
        ];
        $fn = converge($after, $branches);
        $r  = $fn('Aaa'); // 3 + ord('A')=65
        expect($r)->toBe(68);
    });

    it('once runs function only once and caches the result', function (): void {
        $calls    = 0;
        $producer = once(function (int $x) use (&$calls): int {
            $calls++;

            return $x * 2;
        });
        expect($producer(5))->toBe(10);
        expect($producer(7))->toBe(10); // cached
        expect($calls)->toBe(1);
    });

    it('memoizeWith caches by normalized keys (scalar, null, bool, Stringable)', function (): void {
        $calls = 0;
        $keyFn = fn (mixed $x): mixed => $x;
        $fn    = memoizeWith($keyFn, function (mixed $x) use (&$calls): string {
            $calls++;

            return (string) $x;
        });
        expect($fn(1))->toBe('1');
        expect($fn(1))->toBe('1');
        expect($fn(true))->toBe('1');
        expect($fn(false))->toBe('');
        expect($fn(null))->toBe('');
        $keyObj = new readonly class ('k') implements Stringable {
            public function __construct(private string $v)
            {
            }
            public function __toString(): string
            {
                return $this->v;
            }
        };
        expect($fn($keyObj))->toBe('k');
        // calls: 1 (for 1), 1 (true normalized), 1 (false), 1 (null), 1 (Stringable) => 5
        expect($calls)->toBe(5);
    });

    it('partial and partialRight pre-apply arguments', function (): void {
        $concat = fn (string $a, string $b, string $c): string => $a . '-' . $b . '-' . $c;
        $left   = partial($concat, 'L');
        $right  = partialRight($concat, 'R');
        expect($left('M', 'N'))->toBe('L-M-N');
        expect($right('M', 'N'))->toBe('M-N-R');
    });

    it('ascend/descend provide comparators for usort', function (): void {
        $items = [
            ['name' => 'bob',    'age' => 30],
            ['name' => 'alice',  'age' => 25],
            ['name' => 'charly', 'age' => 35],
        ];
        $byAgeAsc  = ascend(fn (array $x): int => $x['age']);
        $byAgeDesc = descend(fn (array $x): int => $x['age']);
        $a         = $items;
        usort($a, $byAgeAsc);
        $d = $items;
        usort($d, $byAgeDesc);
        expect(array_column($a, 'age'))->toBe([25, 30, 35]);
        expect(array_column($d, 'age'))->toBe([35, 30, 25]);
    });
});
