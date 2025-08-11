<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\replacePrefix;

describe('Strings replacePrefix', function (): void {
    it('replaces prefix when present at the start', function (): void {
        expect(replacePrefix('foobar', 'foo', 'X'))->toBe('Xbar');
        expect(replacePrefix('hello', 'he', 'yo'))
            ->toBe('yollo');
    });

    it('returns original when prefix not at the start', function (): void {
        expect(replacePrefix('barfoo', 'foo', 'X'))->toBe('barfoo');
    });

    it('when empty prefix is provided, prepends replacement', function (): void {
        expect(replacePrefix('data', '', 'X'))->toBe('Xdata');
    });
});
