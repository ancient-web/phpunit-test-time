<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

/**
 * Outputs the collected test times.
 */
interface Reporter
{
    /**
     * Report the collected test times.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    public function report(array $testTimes): void;
}
