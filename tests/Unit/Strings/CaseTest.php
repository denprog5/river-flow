<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\toLowerCase;
use function Denprog\RiverFlow\Strings\toUpperCase;

describe('Strings case conversion', function (): void {
    it('handles ASCII strings', function (): void {
        expect(toLowerCase('Hello WORLD'))
            ->toBe('hello world');
        expect(toUpperCase('Hello world'))
            ->toBe('HELLO WORLD');
    });

    it('is mbstring-aware for non-ASCII strings when mbstring is available', function (): void {
        if (!\function_exists('mb_strtolower') || !\function_exists('mb_strtoupper')) {
            $this->markTestSkipped('mbstring is not available');
        }

        expect(toLowerCase('ПрИвЕт'))
            ->toBe('привет');
        expect(toUpperCase('Привет'))
            ->toBe('ПРИВЕТ');
    });
});
