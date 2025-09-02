<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\matchRegex;
use function Denprog\RiverFlow\Strings\padEnd;
use function Denprog\RiverFlow\Strings\padStart;
use function Denprog\RiverFlow\Strings\testRegex;

use InvalidArgumentException;

describe('Strings regex and padding', function (): void {
    it('testRegex works direct and curried', function (): void {
        expect(testRegex('abc123', '/\d+/'))      ->toBeTrue();
        expect(testRegex('no-digits', '/\d+/'))  ->toBeFalse();

        $hasWord = testRegex('/[a-z]+/i');
        expect($hasWord('Hello 123'))->toBeTrue();
        expect($hasWord('123 !@#'))  ->toBeFalse();
    });

    it('testRegex throws InvalidArgumentException on bad pattern', function (): void {
        expect(fn (): bool|callable => testRegex('abc', '/[A-/'))->toThrow(InvalidArgumentException::class);
    });

    it('matchRegex returns matches structure and supports currying', function (): void {
        $m = matchRegex('abc123abc', '/abc/');
        expect($m[0])->toBe(['abc', 'abc']);

        $getNums = matchRegex('/\d+/');
        $m2      = $getNums('a1b22c333');
        expect($m2[0])->toBe(['1','22','333']);
    });

    it('padStart and padEnd work direct and curried', function (): void {
        expect(padStart('7', 3, '0')) ->toBe('007');
        expect(padEnd('7', 3, '0'))   ->toBe('700');

        $p3 = padStart(5, ' ');
        expect($p3('xy'))->toBe('   xy');

        $suf = padEnd(4, '_');
        expect($suf('ab'))->toBe('ab__');
    });

    it('padStart/padEnd validate padChar and lengths', function (): void {
        expect(padStart('abc', 0))   ->toBe('abc');
        expect(padEnd('abc', -10))  ->toBe('abc');
        expect(fn (): string|callable => padStart('x', 3, ''))->toThrow(InvalidArgumentException::class);
        expect(fn (): string|callable => padEnd('x', 3, ''))  ->toThrow(InvalidArgumentException::class);
    });
});
