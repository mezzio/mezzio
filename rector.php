<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withAttributesSets()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withPreparedSets(
        typeDeclarations: true,
    )
    ->withSkipPath(__DIR__ . '/test/TestAsset');
