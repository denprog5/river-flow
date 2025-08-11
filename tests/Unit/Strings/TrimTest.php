<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\trim;

describe('Strings trim', function () {
    it('trims whitespace by default', function () {
        expect(trim(" \t hello \n "))->toBe('hello');
    });

    it('trims custom characters set', function () {
        expect(trim('~~abc~~', '~'))->toBe('abc');
        expect(trim('..abc..', '.'))->toBe('abc');
    });

    it('returns original string when no characters to trim', function () {
        expect(trim('abc', '~'))->toBe('abc');
    });
});
