<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

/**
 * Outputs the collected test durations.
 */
interface Reporter
{
    /**
     * Report the collected test durations.
     *
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    public function report(array $durations): void;
}
