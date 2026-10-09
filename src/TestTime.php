<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

/**
 * A test's measured duration and its optional per-test minimum duration.
 */
final readonly class TestTime
{
    /**
     * @param float $seconds Duration in seconds
     * @param null|int $minimumMilliseconds Per-test minimum duration in milliseconds
     */
    public function __construct(
        public float $seconds,
        public ?int $minimumMilliseconds = null,
    ) {}
}
