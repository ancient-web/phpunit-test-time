<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * Thrown when a per-test maximum duration is not a positive integer.
 */
final class InvalidMaximumDuration extends InvalidArgumentException
{
    /**
     * The maximum duration must be greater than zero.
     *
     * @param int $milliseconds Maximum duration in milliseconds
     */
    public static function notGreaterThanZero(int $milliseconds): self
    {
        return new self(sprintf('The maximum duration must be greater than zero, got %d.', $milliseconds));
    }
}
