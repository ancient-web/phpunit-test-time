<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Reporters;

use AncientWeb\PhpUnitTestTime\Exception\InvalidParameter;
use AncientWeb\PhpUnitTestTime\Exception\ReportWriteFailed;
use AncientWeb\PhpUnitTestTime\Report;
use AncientWeb\PhpUnitTestTime\TestTime;
use Override;

/**
 * Writes the test execution time report as CSV.
 */
final class CsvReporter extends AbstractFileReporter
{
    /**
     * The separators accepted from the "csv-separator" parameter.
     */
    private const ALLOWED_SEPARATOR = '/^[,;|:\t]$/';

    /**
     * @param string $reportPath Path to the shared report file
     * @param null|string $token Worker token, or null when not running in paratest
     * @param int $minimumDuration Minimum duration in milliseconds
     * @param int $maximumCount Maximum number of tests (0 = unlimited)
     * @param string $separator Field separator (one of `,`, `;`, `|`, `:`, or a tab)
     */
    public function __construct(
        string $reportPath,
        ?string $token = null,
        private readonly int $minimumDuration = 0,
        private readonly int $maximumCount = 0,
        private readonly string $separator = ';',
    ) {
        if (preg_match(self::ALLOWED_SEPARATOR, $separator) !== 1) {
            throw InvalidParameter::notAnAllowedCsvSeparator($separator);
        }

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

        $file = fopen($this->reportPath, 'w');

        if ($file === false) {
            throw ReportWriteFailed::write($this->reportPath);
        }

        fputcsv($file, ['Test case', 'Execution time (s)'], $this->separator, escape: '\\');

        foreach ($report->sortedDescending() as $id => $testTime) {
            fputcsv($file, [$id, sprintf('%0.4f', $testTime->seconds)], $this->separator, escape: '\\');
        }

        if (fclose($file) === false) {
            throw ReportWriteFailed::write($this->reportPath);
        }
    }

    /**
     * Strip the ".csv" extension from the machine-readable log base.
     */
    #[Override]
    protected function reportExtension(): string
    {
        return '.csv';
    }
}
