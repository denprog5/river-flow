<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\replace;
use function Denprog\RiverFlow\Strings\slice;

describe('Strings replace/slice', function (): void {
    it('replace replaces all occurrences and supports currying', function (): void {
        expect(replace('hello', 'l', 'x'))->toBe('hexxo');

        $rmL = replace('l', '');
        expect($rmL('hello'))->toBe('heo');

        $rep = replace('о', '0'); // Cyrillic 'o'
        expect($rep('молоко'))->toBe('м0л0к0');

        expect(replace('abc', 'z', 'Q'))->toBe('abc'); // no-op when not found
    });

    it('replace with empty search returns original string (PHP str_replace semantics)', function (): void {
        expect(replace('ab', '', '-'))->toBe('ab');

        $ins = replace('', '*');
        expect($ins(''))->toBe('');
        expect($ins('X'))->toBe('X');
    });

    it('slice handles positive/negative indices and end-exclusive semantics', function (): void {
        expect(slice('hello', 1, 4))->toBe('ell');
        expect(slice('hello', -2))->toBe('lo');
        expect(slice('hello', 0, -1))->toBe('hell');
        expect(slice('hello', -4, -1))->toBe('ell');
        expect(slice('hello', 2, 999))->toBe('llo');
        expect(slice('hello', 10))->toBe('');
    });

    it('slice supports currying', function (): void {
        $mid = slice(1, -1);
        expect($mid('hello'))->toBe('ell');

        $tail2 = slice(-2);
        expect($tail2('hello'))->toBe('lo');
    });

    it('slice is mbstring-aware for multibyte strings when mbstring is available', function (): void {
        if (!\function_exists('mb_substr')) {
            $this->markTestSkipped('mbstring is not available');
        }

        expect(slice('Привет', 1, 3))->toBe('ри');
        expect(slice('Привет', -2))->toBe('ет');

        $mid = slice(1, -1);
        expect($mid('Привет'))->toBe('риве');
    });
});
