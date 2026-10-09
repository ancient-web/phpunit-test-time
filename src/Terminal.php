<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use function getenv;
use function max;
use function preg_match;

/**
 * Detects the terminal width.
 */
final class Terminal
{
    /**
     * Minimum width used for narrow terminals.
     */
    private const int MINIMUM_WIDTH = 80;

    /**
     * Width used when the terminal width cannot be detected.
     */
    private const int DEFAULT_WIDTH = 80;

    /**
     * Detect the terminal width in columns.
     */
    public static function width(): int
    {
        $columns = getenv('COLUMNS');

        if (false === $columns || 1 !== preg_match('/^\d+$/', $columns)) {
            return self::DEFAULT_WIDTH;
        }

        return max(self::MINIMUM_WIDTH, (int) $columns);
    }
}
