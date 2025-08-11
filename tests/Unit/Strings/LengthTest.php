<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\length;

describe('Strings length', function () {
    it('returns length for ASCII strings', function () {
        expect(length(''))
            ->toBe(0);
        expect(length('Hello'))
            ->toBe(5);
        expect(length('Hello, World!'))
            ->toBe(13);
    });

    it('is mbstring-aware for multibyte strings when available', function () {
        if (!\function_exists('mb_strlen')) {
            $this->markTestSkipped('mbstring is not available');
        }
        expect(length('Привет')) // 6 Cyrillic letters
            ->toBe(6);
        expect(length('😀')) // single emoji
            ->toBe(1);
    });
});
