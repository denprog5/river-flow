<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\trim;

describe('Strings trim', function (): void {
    it('trims whitespace by default', function (): void {
        expect(trim(" \t hello \n "))->toBe('hello');
    });

    it('trims custom characters set', function (): void {
        expect(trim('~~abc~~', '~'))->toBe('abc');
        expect(trim('..abc..', '.'))->toBe('abc');
    });

    it('returns original string when no characters to trim', function (): void {
        expect(trim('abc', '~'))->toBe('abc');
    });
});
