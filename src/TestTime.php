<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

/**
 * A test's measured duration and its optional per-test minimum duration.
 */
final class TestTime
{
    /**
     * @param float $seconds Duration in seconds
     * @param null|int $minimumMilliseconds Per-test minimum duration in milliseconds
     */
    public function __construct(
        public readonly float $seconds,
        public readonly ?int $minimumMilliseconds = null,
    ) {
    }
}
