<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = new Finder()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->append([__FILE__, __DIR__.'/rector.php'])
;

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP84Migration' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'native_function_invocation' => false,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_align' => ['align' => 'left'],
    ])
    ->setFinder($finder);
