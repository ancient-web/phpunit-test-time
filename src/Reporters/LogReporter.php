<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Reporters;

use AncientWeb\PhpUnitTestTime\Exception\ReportWriteFailed;
use AncientWeb\PhpUnitTestTime\Report;
use AncientWeb\PhpUnitTestTime\TestTime;
use DateTimeImmutable;
use Override;

use function dirname;
use function file_put_contents;

final readonly class LogReporter extends AbstractFileReporter
{
    /**
     * @param string $reportPath Path to the shared report file
     * @param int $minimumDuration Minimum duration in milliseconds
     * @param int $maximumCount Maximum number of tests (0 = unlimited)
     * @param null|string $token Worker token, or null when not running in paratest
     */
    public function __construct(
        string $reportPath,
        ?string $token = null,
        private int $minimumDuration = 0,
        private int $maximumCount = 0,
    ) {
        parent::__construct($reportPath, $token);
    }

    /**
     * Write the human-readable report, filtered by the configured threshold and count.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    #[Override]
    protected function writeReport(array $testTimes): void
    {
        $report = Report::fromTestTimes($testTimes)
            ->withMinimumDuration($this->minimumDuration)
            ->withMaximumCount($this->maximumCount)
        ;

        $this->ensureDirectory(dirname($this->reportPath));

        $contents = $report->toText('Test execution time report', new DateTimeImmutable());

        if (false === file_put_contents($this->reportPath, $contents)) {
            throw ReportWriteFailed::write($this->reportPath);
        }
    }

    /**
     * Strip the ".log" extension from the machine-readable log base.
     */
    #[Override]
    protected function reportExtension(): string
    {
        return '.log';
    }
}
