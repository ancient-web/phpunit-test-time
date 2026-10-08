<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use function mkdir;
use function unlink;
use function rmdir;
use function is_dir;
use function scandir;
use function bin2hex;
use function sys_get_temp_dir;
use function random_bytes;
use PHPUnit\Framework\TestCase;

/**
 * Base test case with a temporary working directory
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * Temporary directory created for each test
     */
    protected string $directory;

    /**
     * Create the temporary directory
     */
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/phpunit-test-time-' . bin2hex(random_bytes(6));

        mkdir($this->directory, 0777, true);
    }

    /**
     * Remove the temporary directory
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    /**
     * Recursively remove a directory
     *
     * @param string $directory Directory path
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
