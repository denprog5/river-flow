<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Use up-to PHP 8.3 ruleset for compatibility; bump to 8.4/8.5 when available
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
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
    $rectorConfig->skip([
        __DIR__ . '/vendor/*',
        __DIR__ . '/build/*',
        __DIR__ . '/coverage/*',
    ]);
};
