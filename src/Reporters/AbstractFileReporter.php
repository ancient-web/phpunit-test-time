<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Reporters;

use AncientWeb\PhpUnitTestTime\Exception\ReportWriteFailed;
use AncientWeb\PhpUnitTestTime\Reporter;
use AncientWeb\PhpUnitTestTime\TestTime;

/**
 * Writes the test execution time report to a file.
 *
 * Each process records its test times as a JSON worker log. In paratest mode all
 * worker logs are merged into a JSON accumulator under an exclusive lock, from
 * which the human-readable report is rendered; the worker logs are then removed
 */
abstract readonly class AbstractFileReporter implements Reporter
{
    /**
     * Flags used to encode the machine-readable logs.
     */
    private const JSON_FLAGS = JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param string $reportPath Path to the shared report file
     * @param null|string $token Worker token, or null when not running in paratest
     */
    public function __construct(
        protected string $reportPath,
        protected ?string $token = null,
    ) {
    }

    /**
     * Remove reports from previous runs.
     *
     * Files created after the current process started are left untouched
     */
    public function reset(): void
    {
        $threshold = isset($_SERVER['REQUEST_TIME_FLOAT']) && \is_numeric($_SERVER['REQUEST_TIME_FLOAT'])
            ? (float) $_SERVER['REQUEST_TIME_FLOAT']
            : \microtime(true);

        foreach ($this->existingFiles() as $file) {
            $modifiedAt = @\filemtime($file);

            if ($modifiedAt !== false && $modifiedAt < $threshold) {
                @\unlink($file);
            }
        }
    }

    /**
     * Write the current process test times and, in paratest mode, merge all workers.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    public function report(array $testTimes): void
    {
        if ($this->token === null) {
            $this->writeReport($testTimes);

            return;
        }

        $this->writeJson($this->workerPath($this->token), $testTimes);

        $this->merge();
    }

    /**
     * Create a directory if it does not exist.
     *
     * @param string $directory Directory path
     */
    protected function ensureDirectory(string $directory): void
    {
        if (\is_dir($directory)) {
            return;
        }

        if (! \mkdir($directory, 0o777, true) && ! \is_dir($directory)) {
            throw ReportWriteFailed::directory($directory);
        }
    }

    /**
     * Write a machine-readable log.
     *
     * @param string $path Log file path
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    protected function writeJson(string $path, array $testTimes): void
    {
        $this->ensureDirectory(\dirname($path));

        $data = [];

        foreach ($testTimes as $id => $testTime) {
            $data[$id] = [
                'duration' => $testTime->seconds,
                'minimum' => $testTime->minimumMilliseconds,
            ];
        }

        $json = \json_encode($data, self::JSON_FLAGS);

        if ($json === false) {
            throw ReportWriteFailed::write($path);
        }

        if (\file_put_contents($path, $json) === false) {
            throw ReportWriteFailed::write($path);
        }
    }

    /**
     * Read a machine-readable log.
     *
     * @param string $path Log file path
     *
     * @return array<string, TestTime>
     */
    protected function readJson(string $path): array
    {
        $contents = @\file_get_contents($path);

        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = \json_decode($contents, true);

        if (! \is_array($decoded)) {
            return [];
        }

        $testTimes = [];

        foreach ($decoded as $id => $entry) {
            if (! is_string($id) || ! \is_array($entry)) {
                continue;
            }

            $duration = $entry['duration'] ?? null;

            if (! \is_numeric($duration)) {
                continue;
            }

            $minimum = $entry['minimum'] ?? null;

            $testTimes[$id] = new TestTime(
                (float) $duration,
                \is_numeric($minimum) ? (int) $minimum : null,
            );
        }

        return $testTimes;
    }

    /**
     * Write the human-readable report, filtered by the configured threshold and count.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    abstract protected function writeReport(array $testTimes): void;

    /**
     * Get the report extension that is stripped from the machine-readable log base.
     *
     * Deriving the base from the stripped extension keeps every reporter that
     * writes next to another one (for example `test-time.log` and
     * `test-time.csv`) in the same machine namespace, so they share the merged
     * log instead of competing for each other's files.
     */
    abstract protected function reportExtension(): string;

    /**
     * Get the report base path without its extension.
     */
    protected function basePath(): string
    {
        $extension = $this->reportExtension();

        if ($extension !== '' && \str_ends_with($this->reportPath, $extension)) {
            return \substr($this->reportPath, 0, -\strlen($extension));
        }

        return $this->reportPath;
    }

    /**
     * Merge all worker logs into the accumulator and render the shared report.
     */
    private function merge(): void
    {
        $this->ensureDirectory(\dirname($this->reportPath));

        $accumulatorPath = $this->accumulatorPath();
        $handle = @\fopen($accumulatorPath, 'c');

        if ($handle === false) {
            throw ReportWriteFailed::open($accumulatorPath);
        }

        try {
            if (! \flock($handle, LOCK_EX)) {
                throw ReportWriteFailed::lock($accumulatorPath);
            }

            try {
                $merged = $this->readJson($accumulatorPath);

                foreach ($this->workerPaths() as $file) {
                    foreach ($this->readJson($file) as $id => $testTime) {
                        $this->mergeTestTime($merged, $id, $testTime);
                    }
                }

                $this->writeJson($accumulatorPath, $merged);
                $this->writeReport($merged);

                $this->removeWorkerLogs();
            } finally {
                \flock($handle, LOCK_UN);
            }
        } finally {
            \fclose($handle);
        }
    }

    /**
     * Add a test time to the merged set, keeping the maximum duration.
     *
     * @param array<string, TestTime> $merged Merged set of test times
     * @param string $id Test identifier
     */
    private function mergeTestTime(array &$merged, string $id, TestTime $testTime): void
    {
        if (! isset($merged[$id])) {
            $merged[$id] = $testTime;

            return;
        }

        $current = $merged[$id];

        $merged[$id] = new TestTime(
            \max($current->seconds, $testTime->seconds),
            $current->minimumMilliseconds ?? $testTime->minimumMilliseconds,
        );
    }

    /**
     * Remove intermediate worker logs.
     */
    private function removeWorkerLogs(): void
    {
        foreach ($this->workerPaths() as $file) {
            @\unlink($file);
        }
    }

    /**
     * Get the list of files written by previous runs.
     *
     * @return array<int, string>
     */
    private function existingFiles(): array
    {
        return \array_merge(
            [$this->reportPath, $this->accumulatorPath()],
            $this->workerPaths(),
        );
    }

    /**
     * Get the list of worker log files.
     *
     * The directory is scanned directly instead of using glob(), so paths that
     * contain glob metacharacters (such as `[` or `*`) keep working.
     *
     * @return array<int, string>
     */
    private function workerPaths(): array
    {
        $directory = \dirname($this->basePath());
        $name = \basename($this->basePath());

        $entries = @\scandir($directory);

        if ($entries === false) {
            return [];
        }

        $prefix = $name . '.';
        $accumulator = $name . '.json';
        $files = [];

        foreach ($entries as $entry) {
            if ($entry === $accumulator || ! \str_starts_with($entry, $prefix) || ! \str_ends_with($entry, '.json')) {
                continue;
            }

            $files[] = $directory . '/' . $entry;
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
        $safeToken = \preg_replace('/[^A-Za-z0-9_.-]/', '_', $token) ?? 'worker';

        return $this->basePath() . '.' . $safeToken . '.json';
    }

    /**
     * Get the path to the JSON accumulator shared by all workers.
     */
    private function accumulatorPath(): string
    {
        return $this->basePath() . '.json';
    }
}
