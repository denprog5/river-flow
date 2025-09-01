<?php

declare(strict_types=1);

namespace Denprog\RiverFlow\Tests\Unit\Strings;

use function Denprog\RiverFlow\Strings\endsWith;
use function Denprog\RiverFlow\Strings\includes;
use function Denprog\RiverFlow\Strings\startsWith;

describe('Strings includes/startsWith/endsWith', function (): void {
    it('includes finds substring (direct) and treats empty needle as true', function (): void {
        expect(includes('hello', 'ell'))->toBeTrue();
        expect(includes('hello', 'z'))->toBeFalse();
        expect(includes('hello', ''))->toBeTrue();
    });

    it('includes supports currying', function (): void {
        $hasLo = includes('lo');
        expect($hasLo('hello'))->toBeTrue();
        expect($hasLo('help'))->toBeFalse();
    });

    it('startsWith works for typical and edge cases', function (): void {
        expect(startsWith('hello', 'he'))->toBeTrue();
        expect(startsWith('hello', ''))  ->toBeTrue();
        expect(startsWith('hello', 'hello'))->toBeTrue();
        expect(startsWith('hello', 'hello!'))->toBeFalse();
        expect(startsWith('hello', 'z')) ->toBeFalse();

        $st = startsWith('При');
        expect($st('Привет'))->toBeTrue();
    });

    it('endsWith works for typical and edge cases', function (): void {
        expect(endsWith('hello', 'lo'))->toBeTrue();
        expect(endsWith('hello', ''))   ->toBeTrue();
        expect(endsWith('hello', 'hello'))->toBeTrue();
        expect(endsWith('hello', '!hello'))->toBeFalse();
        expect(endsWith('hello', 'z'))  ->toBeFalse();

        $en = endsWith('вет');
        expect($en('Привет'))->toBeTrue();
    });
});
