<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withPreparedSets(
        deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, privatization: true, naming: true, instanceOf: true, strictBooleans: true, rectorPreset: true, phpunitCodeQuality: true
    )
    ->withCodeQualityLevel(0);
