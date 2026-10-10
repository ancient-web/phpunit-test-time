<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withRootFiles()
    ->withPreparedSets(
        psr12: true,
        common: true,
    )
    ->withRules([DeclareStrictTypesFixer::class])
    ->withConfiguredRule(PhpdocAlignFixer::class, [
        'align' => 'left',
    ])
;
