<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Reporters;

use AncientWeb\PhpUnitTestTime\Exception\InvalidParameter;
use AncientWeb\PhpUnitTestTime\Exception\ReportWriteFailed;
use AncientWeb\PhpUnitTestTime\Report;
use AncientWeb\PhpUnitTestTime\TestTime;

use function dirname;

final readonly class CsvReporter extends AbstractFileReporter
{
    private const string ALLOWED_SEPARATOR = '/^[,;|:\t]$/';

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
        private string $separator = ';',
    ) {
        if (false === preg_match(self::ALLOWED_SEPARATOR, $separator)) {
            throw new InvalidParameter('Недопустимый разделитель CSV');
        }
        parent::__construct($reportPath, $token);
    }

    /**
     * Write the human-readable report, filtered by the configured threshold and count.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    protected function writeReport(array $testTimes): void
    {
        $report = Report::fromTestTimes($testTimes)
            ->withMinimumDuration($this->minimumDuration)
            ->withMaximumCount($this->maximumCount)
        ;

        $this->ensureDirectory(dirname($this->reportPath));

        $file = fopen($this->reportPath, 'w');
        if (false === $file) {
            throw ReportWriteFailed::write($this->reportPath);
        }
        fputcsv($file, ['Test case', 'Execution time'], $this->separator, escape: '\\');
        foreach ($report->sortedDescending() as $id => $testTime) {
            fputcsv(
                $file,
                [$id, sprintf('%0.4f s', $testTime->seconds)],
                $this->separator,
                escape: '\\',
            );
        }
        $written = fclose($file);
        if (false === $written) {
            throw ReportWriteFailed::write($this->reportPath);
        }
    }
}
