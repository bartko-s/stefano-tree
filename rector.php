<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
    ]);

    // the project's code style (php-cs-fixer) prefers the long array syntax
    $rectorConfig->skip([
        LongArrayToShortArrayRector::class,
    ]);
};