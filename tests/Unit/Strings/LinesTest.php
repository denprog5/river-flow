<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\lines;

describe('Strings lines', function () {
    it('splits by LF/CRLF/CR sequences', function () {
        $text = "a\nb\r\nc\rd";
        expect(lines($text))->toBe(['a', 'b', 'c', 'd']);
    });

    it('returns single element for single-line strings', function () {
        expect(lines('single'))->toBe(['single']);
    });
});
