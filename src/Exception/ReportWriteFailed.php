<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when the test execution time report cannot be written.
 */
final class ReportWriteFailed extends RuntimeException
{
    /**
     * The report directory could not be created.
     *
     * @param string $directory Directory path
     */
    public static function directory(string $directory): self
    {
        return new self(sprintf('Unable to create the report directory "%s".', $directory));
    }

    /**
     * The report file could not be opened.
     *
     * @param string $path File path
     */
    public static function open(string $path): self
    {
        return new self(sprintf('Unable to open the report file "%s".', $path));
    }

    /**
     * The report file could not be locked.
     *
     * @param string $path File path
     */
    public static function lock(string $path): self
    {
        return new self(sprintf('Unable to lock the report file "%s".', $path));
    }

    /**
     * The report file could not be written.
     *
     * @param string $path File path
     */
    public static function write(string $path): self
    {
        return new self(sprintf('Unable to write the report file "%s".', $path));
    }
}
