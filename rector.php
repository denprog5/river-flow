<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Use up-to PHP 8.5 ruleset
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::NAMING,
        SetList::PRIVATIZATION,
    ]);

    // Import fully-qualified names where helpful
    $rectorConfig->importNames();

    // Cache
    if (method_exists($rectorConfig, 'cacheDirectory')) {
        $rectorConfig->cacheDirectory(__DIR__ . '/.cache/rector');
    }

    // Exclusions
    // Skip first-class callable rules for src/ - they break PHPStan type inference
    // when applied to curried function wrappers that rely on typed closures for generics
    $rectorConfig->skip([
        __DIR__ . '/vendor/*',
        __DIR__ . '/build/*',
        __DIR__ . '/coverage/*',

        // These rules convert typed closures to first-class callables, losing type info
        // Example: `fn(iterable $d): array => toList_impl($d)` -> `toList_impl(...)`
        // This breaks PHPStan's ability to infer generic types through the curried wrappers
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__ . '/src/*',
        ],
        ClosureDelegatingCallToFirstClassCallableRector::class => [
            __DIR__ . '/src/*',
        ],

        // Skip ForeachToArrayAllRector - changes behavior and less readable for our use case
        ForeachToArrayAllRector::class => [
            __DIR__ . '/src/*',
        ],
    ]);
};
