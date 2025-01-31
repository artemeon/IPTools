<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
                   ->withAttributesSets(phpunit: true)
                   ->withPhpSets(php84: true)
                   ->withPreparedSets(
                        deadCode: true,
                        codeQuality: true,
                        codingStyle: true,
                        privatization: true,
                        naming: true,
                        rectorPreset: true)
                   ->withPaths([
                       __DIR__ . '/src',
                       __DIR__ . '/tests',
                   ])
                   ->withTypeCoverageLevel(13);