<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\join;
use function Denprog\RiverFlow\Strings\length;
use function Denprog\RiverFlow\Strings\split;
use function Denprog\RiverFlow\Strings\toLowerCase;
use function Denprog\RiverFlow\Strings\toUpperCase;
use function Denprog\RiverFlow\Strings\trim;

describe('Strings curried and roundtrip behavior', function (): void {
    it('supports curried trim/case/length composition', function (): void {
        $trimmed = trim()('  HeLLo  ');
        $result  = toUpperCase()(toLowerCase()($trimmed));

        expect($result)->toBe('HELLO');
        expect(length()($result))->toBe(5);
    });

    it('split and join form a stable roundtrip for delimiter-safe parts', function (): void {
        $parts = ['alpha', 'beta', 'gamma'];
        $csv   = join(',')($parts);
        $back  = split(',')($csv);

        expect($csv)->toBe('alpha,beta,gamma');
        expect($back)->toBe($parts);
    });

});
