<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
    ])
    ->withSkip([
        // Add rules here as the codebase is modernised.
    ]);
