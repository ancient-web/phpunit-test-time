<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Telemetry\HRTime;

/**
 * Collects the execution duration of each test.
 */
final class TestTimeCollector
{
    /**
     * @var array<string, HRTime> Test start times keyed by test identifier
     */
    private array $started = [];

    /**
     * @var array<string, float> Test execution durations in seconds keyed by test identifier
     */
    private array $durations = [];

    /**
     * Whether the report has already been written.
     */
    private bool $written = false;

    /**
     * @param TestTimeReportWriter $reportWriter Report writer
     */
    public function __construct(private readonly TestTimeReportWriter $reportWriter) {}

    /**
     * Record the start of a test.
     *
     * @param Test $test Test
     * @param HRTime $time Start time
     */
    public function start(Test $test, HRTime $time): void
    {
        $this->started[$test->id()] = $time;
    }

    /**
     * Record the end of a test and compute its duration.
     *
     * @param Test $test Test
     * @param HRTime $time End time
     */
    public function finish(Test $test, HRTime $time): void
    {
        $id = $test->id();

        if (!isset($this->started[$id])) {
            return;
        }

        $this->durations[$id] = $time->duration($this->started[$id])->asFloat();

        unset($this->started[$id]);
    }

    /**
     * Write the final report.
     */
    public function writeReport(): void
    {
        if ($this->written) {
            return;
        }

        $this->written = true;

        $this->reportWriter->write($this->durations);
    }
}
