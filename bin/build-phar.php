<?php

declare(strict_types=1);

/**
 * Build a self-contained PHAR bundle of the extension classes.
 *
 * The PHAR registers a small autoloader for the `AncientWeb\PhpUnitTestTime\`
 * namespace, so it can be `require`d from a PHPUnit bootstrap without relying on
 * Composer's autoloader for this package.
 *
 * Usage: php -d phar.readonly=0 bin/build-phar.php [output-path]
 */

$root = dirname(__DIR__);
$output = $argv[1] ?? $root.'/build/phpunit-test-time.phar';

$directory = dirname($output);

if (!is_dir($directory) && !mkdir($directory, 0o777, true) && !is_dir($directory)) {
    fwrite(STDERR, 'Unable to create '.$directory.PHP_EOL);

    exit(1);
}

if (file_exists($output)) {
    unlink($output);
}

$phar = new Phar($output);
$phar->setAlias('phpunit-test-time.phar');
$phar->startBuffering();
$phar->buildFromDirectory($root.'/src');
$phar->setStub(
    <<<'STUB'
    <?php
    Phar::mapPhar('phpunit-test-time.phar');
    spl_autoload_register(static function (string $class): void {
        $prefix = 'AncientWeb\\PhpUnitTestTime\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $path = 'phar://phpunit-test-time.phar/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require $path;
        }
    }, true, true);
    __HALT_COMPILER();
    STUB
);
$phar->stopBuffering();

fwrite(STDOUT, 'Built '.$output.PHP_EOL);
