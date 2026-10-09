<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use AncientWeb\PhpUnitTestTime\Exception\ReportWriteFailed;
use DateTimeImmutable;

use function array_merge;
use function dirname;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function flock;
use function fopen;
use function glob;
use function is_array;
use function is_dir;
use function is_numeric;
use function json_decode;
use function json_encode;
use function microtime;
use function mkdir;
use function preg_replace;
use function str_ends_with;
use function substr;
use function unlink;

/**
 * Writes the test execution time report to a file.
 *
 * Each process records its durations as a JSON worker log. In paratest mode all
 * worker logs are merged into a JSON accumulator under an exclusive lock, from
 * which the human-readable report is rendered; the worker logs are then removed
 */
final readonly class TestTimeReportWriter implements Reporter
{
    /**
     * Flags used to encode the machine-readable logs.
     */
    private const JSON_FLAGS = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param string $reportPath Path to the shared report file
     * @param int $minimumDuration Minimum duration in milliseconds
     * @param int $maximumCount Maximum number of tests (0 = unlimited)
     * @param null|string $token Worker token, or null when not running in paratest
     */
    public function __construct(
        private string $reportPath,
        private int $minimumDuration = 0,
        private int $maximumCount = 0,
        private ?string $token = null,
    ) {}

    /**
     * Remove reports from previous runs.
     *
     * Files created after the current process started are left untouched
     */
    public function reset(): void
    {
        $threshold = isset($_SERVER['REQUEST_TIME_FLOAT']) && is_numeric($_SERVER['REQUEST_TIME_FLOAT'])
            ? (float) $_SERVER['REQUEST_TIME_FLOAT']
            : microtime(true);

        foreach ($this->existingFiles() as $file) {
            $modifiedAt = @filemtime($file);

            if (false !== $modifiedAt && $modifiedAt < $threshold) {
                @unlink($file);
            }
        }
    }

    /**
     * Write the current process durations and, in paratest mode, merge all workers.
     *
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    public function report(array $durations): void
    {
        if (null === $this->token) {
            $this->writeReport($durations);

            return;
        }

        $this->writeJson($this->workerPath($this->token), $durations);

        $this->merge();
    }

    /**
     * Merge all worker logs into the accumulator and render the shared report.
     */
    private function merge(): void
    {
        $this->ensureDirectory(dirname($this->reportPath));

        $accumulatorPath = $this->accumulatorPath();
        $handle = @fopen($accumulatorPath, 'c');

        if (false === $handle) {
            throw ReportWriteFailed::open($accumulatorPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw ReportWriteFailed::lock($accumulatorPath);
            }

            try {
                $merged = $this->readJson($accumulatorPath);

                foreach ($this->workerPaths() as $file) {
                    foreach ($this->readJson($file) as $id => $duration) {
                        $this->mergeDuration($merged, $id, $duration);
                    }
                }

                $this->writeJson($accumulatorPath, $merged);
                $this->writeReport($merged);

                $this->removeWorkerLogs();
            } finally {
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Add a test duration to the merged set, keeping the maximum value.
     *
     * @param array<string, float> $merged Merged set of durations
     * @param string $id Test identifier
     * @param float $duration Test duration
     */
    private function mergeDuration(array &$merged, string $id, float $duration): void
    {
        if (!isset($merged[$id]) || $duration > $merged[$id]) {
            $merged[$id] = $duration;
        }
    }

    /**
     * Remove intermediate worker logs.
     */
    private function removeWorkerLogs(): void
    {
        foreach ($this->workerPaths() as $file) {
            @unlink($file);
        }
    }

    /**
     * Get the list of files written by previous runs.
     *
     * @return array<int, string>
     */
    private function existingFiles(): array
    {
        return array_merge(
            [$this->reportPath, $this->accumulatorPath()],
            $this->workerPaths(),
        );
    }

    /**
     * Get the list of worker log files.
     *
     * @return array<int, string>
     */
    private function workerPaths(): array
    {
        $files = glob($this->basePath().'.*.json');

        if (false === $files) {
            return [];
        }

        return $files;
    }

    /**
     * Get the path to a worker log file.
     *
     * @param string $token Worker token
     */
    private function workerPath(string $token): string
    {
        $safeToken = preg_replace('/[^A-Za-z0-9_.-]/', '_', $token) ?? 'worker';

        return $this->basePath().'.'.$safeToken.'.json';
    }

    /**
     * Get the path to the JSON accumulator shared by all workers.
     */
    private function accumulatorPath(): string
    {
        return $this->basePath().'.json';
    }

    /**
     * Get the report base path without extension.
     */
    private function basePath(): string
    {
        if (str_ends_with($this->reportPath, '.log')) {
            return substr($this->reportPath, 0, -4);
        }

        return $this->reportPath;
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $directory Directory path
     */
    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0o777, true) && !is_dir($directory)) {
            throw ReportWriteFailed::directory($directory);
        }
    }

    /**
     * Write a machine-readable log.
     *
     * @param string $path Log file path
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    private function writeJson(string $path, array $durations): void
    {
        $this->ensureDirectory(dirname($path));

        $json = json_encode($durations, self::JSON_FLAGS);

        if (false === $json) {
            throw ReportWriteFailed::write($path);
        }

        if (false === file_put_contents($path, $json)) {
            throw ReportWriteFailed::write($path);
        }
    }

    /**
     * Read a machine-readable log.
     *
     * @param string $path Log file path
     *
     * @return array<string, float>
     */
    private function readJson(string $path): array
    {
        $contents = @file_get_contents($path);

        if (false === $contents || '' === $contents) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            return [];
        }

        $durations = [];

        foreach ($decoded as $id => $duration) {
            if (!is_string($id) || !is_numeric($duration)) {
                continue;
            }

            $durations[$id] = (float) $duration;
        }

        return $durations;
    }

    /**
     * Write the human-readable report, filtered by the configured threshold and count.
     *
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    private function writeReport(array $durations): void
    {
        $report = Report::fromDurations($durations)
            ->withMinimumDuration($this->minimumDuration, MaximumDurationResolver::resolve(...))
            ->withMaximumCount($this->maximumCount)
        ;

        $this->ensureDirectory(dirname($this->reportPath));

        $contents = $report->toText('Test execution time report', new DateTimeImmutable());

        if (false === file_put_contents($this->reportPath, $contents)) {
            throw ReportWriteFailed::write($this->reportPath);
        }
    }
}
