<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\E2E;

use AncientWeb\PhpUnitTestTime\TestTimeExtension;

use function dirname;
use function sprintf;
use function var_export;

/**
 * End-to-end test for the PHAR build.
 */
final class PharBuildTest extends AbstractEndToEndTestCase
{
    /**
     * The built PHAR autoloads the extension classes from within the archive.
     */
    public function testBuildsAPharThatAutoloadsTheExtension(): void
    {
        $root = dirname(__DIR__, 2);
        $pharPath = $this->directory.'/phpunit-test-time.phar';

        $build = $this->runCommand([PHP_BINARY, '-d', 'phar.readonly=0', $root.'/bin/build-phar.php', $pharPath]);

        $this->assertSame(0, $build['exitCode'], $build['error']);
        $this->assertFileExists($pharPath);

        $code = sprintf(
            'require %s; require %s; echo (new ReflectionClass(%s))->getFileName();',
            var_export($root.'/vendor/autoload.php', true),
            var_export($pharPath, true),
            var_export(TestTimeExtension::class, true),
        );

        $verify = $this->runCommand([PHP_BINARY, '-r', $code]);

        $this->assertSame(0, $verify['exitCode'], $verify['error']);
        $this->assertStringContainsString('phar://', $verify['output']);
        $this->assertStringContainsString('TestTimeExtension.php', $verify['output']);
    }
}
