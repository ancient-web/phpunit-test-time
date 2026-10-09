<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    // Fixtures are test doubles; their (often empty) methods must not be removed
    // or inlined as dead code.
    ->withSkip([
        __DIR__.'/tests/Fixture',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
    )
;
