<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Attribute;

use AncientWeb\PhpUnitTestTime\Exception\InvalidMaximumDuration;
use Attribute;

/**
 * Sets the maximum duration, in milliseconds, for a single test method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class MaximumDuration
{
    /**
     * @param int $milliseconds Maximum duration in milliseconds
     *
     * @throws InvalidMaximumDuration
     */
    public function __construct(public int $milliseconds)
    {
        if ($milliseconds <= 0) {
            throw InvalidMaximumDuration::notGreaterThanZero($milliseconds);
        }
    }
}
