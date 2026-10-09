<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use PHPUnit\Event\Telemetry\HRTime;

use function array_values;

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
     * @var array<string, TestTime> Test times keyed by test identifier
     */
    private array $testTimes = [];

    /**
     * Whether the report has already been written.
     */
    private bool $written = false;

    /**
     * @var array<int, Reporter>
     */
    private readonly array $reporters;

    /**
     * @param Reporter ...$reporters Reporters to send the collected times to
     */
    public function __construct(Reporter ...$reporters)
    {
        $this->reporters = array_values($reporters);
    }

    /**
     * Record the start of a test.
     *
     * @param string $testId Test identifier
     * @param HRTime $time Start time
     */
    public function start(string $testId, HRTime $time): void
    {
        $this->started[$testId] = $time;
    }

    /**
     * Record the end of a test and compute its duration.
     *
     * The per-test minimum is resolved here, while the test class is loaded, so
     * it can be carried through the paratest merge.
     *
     * @param string $testId Test identifier
     * @param HRTime $time End time
     */
    public function finish(string $testId, HRTime $time): void
    {
        if (!isset($this->started[$testId])) {
            return;
        }

        $this->testTimes[$testId] = new TestTime(
            $time->duration($this->started[$testId])->asFloat(),
            MaximumDurationResolver::resolve($testId),
        );

        unset($this->started[$testId]);
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

        foreach ($this->reporters as $reporter) {
            $reporter->report($this->testTimes);
        }
    }
}
