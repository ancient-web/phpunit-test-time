<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use DateTimeImmutable;
use PHPUnit\TextUI\Configuration\Configuration;

use function fwrite;

/**
 * Prints the test execution time report to the console.
 */
final readonly class ConsoleReporter implements Reporter
{
    /**
     * @param Configuration $configuration PHPUnit configuration
     * @param int $minimumDuration Minimum duration in milliseconds
     * @param int $maximumCount Maximum number of tests (0 = unlimited)
     * @param int $maximumWidth Maximum width in columns (0 = no truncation)
     */
    public function __construct(
        private Configuration $configuration,
        private int $minimumDuration,
        private int $maximumCount,
        private int $maximumWidth,
    ) {}

    /**
     * Print the report unless output is suppressed or nothing matches.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    public function report(array $testTimes): void
    {
        if ($this->configuration->noOutput()) {
            return;
        }

        $report = Report::fromTestTimes($testTimes)
            ->withMinimumDuration($this->minimumDuration)
            ->withMaximumCount($this->maximumCount)
        ;

        if (0 === $report->count()) {
            return;
        }

        $stream = $this->configuration->outputToStandardErrorStream() ? STDERR : STDOUT;

        fwrite($stream, PHP_EOL.$report->toText('Test execution time report', new DateTimeImmutable(), $this->maximumWidth));
    }
}
