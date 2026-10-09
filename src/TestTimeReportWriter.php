<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use DateTimeImmutable;

use function array_merge;
use function array_sum;
use function count;
use function dirname;
use function explode;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function flock;
use function fopen;
use function getenv;
use function getmypid;
use function glob;
use function implode;
use function is_dir;
use function is_numeric;
use function microtime;
use function mkdir;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_ends_with;
use function substr;
use function uasort;
use function unlink;

/**
 * Builds the test execution time report.
 *
 * In paratest mode each worker writes its own intermediate log under its token,
 * then all logs are merged into a shared report under an exclusive lock
 * and the intermediate files are removed
 */
final readonly class TestTimeReportWriter
{
    /**
     * @param string $reportPath Path to the shared report file
     */
    public function __construct(private string $reportPath) {}

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

        foreach ($this->existingReportFiles() as $file) {
            $modifiedAt = @filemtime($file);

            if (false !== $modifiedAt && $modifiedAt < $threshold) {
                @unlink($file);
            }
        }
    }

    /**
     * Write the current process report and merge it with reports of other processes.
     *
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    public function write(array $durations): void
    {
        $token = $this->resolveToken();

        if (null === $token) {
            $this->writeFile($this->reportPath, $durations, 'Test execution time report');

            return;
        }

        $this->writeFile($this->tokenReportPath($token), $durations, sprintf('Test execution time (worker %s)', $token));

        $this->mergeTokenReports($durations);
    }

    /**
     * Merge all worker reports into the shared file and remove intermediate logs.
     *
     * @param array<string, float> $durations Durations of the current worker
     */
    private function mergeTokenReports(array $durations): void
    {
        if (!$this->ensureDirectory(dirname($this->reportPath))) {
            return;
        }

        $handle = @fopen($this->reportPath, 'c');

        if (false === $handle) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            try {
                $merged = $this->readFile($this->reportPath);

                foreach ($this->tokenReportFiles() as $file) {
                    foreach ($this->readFile($file) as $id => $duration) {
                        $this->mergeDuration($merged, $id, $duration);
                    }
                }

                foreach ($durations as $id => $duration) {
                    $this->mergeDuration($merged, $id, $duration);
                }

                $this->writeFile($this->reportPath, $merged, 'Final test execution time report');

                $this->removeTokenReports();
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
    private function removeTokenReports(): void
    {
        foreach ($this->tokenReportFiles() as $file) {
            @unlink($file);
        }
    }

    /**
     * Resolve the current worker token.
     *
     * For paratest it is TEST_TOKEN, otherwise a unique worker token or the process pid
     */
    private function resolveToken(): ?string
    {
        $token = getenv('TEST_TOKEN');

        if (false !== $token && '' !== $token) {
            return $token;
        }

        $token = getenv('UNIQUE_TEST_TOKEN');

        if (false !== $token && '' !== $token) {
            return $token;
        }

        if (false !== getenv('PARATEST')) {
            return (string) getmypid();
        }

        return null;
    }

    /**
     * Get the list of existing report files.
     *
     * @return array<int, string>
     */
    private function existingReportFiles(): array
    {
        return array_merge([$this->reportPath], $this->tokenReportFiles());
    }

    /**
     * Get the list of worker report files.
     *
     * @return array<int, string>
     */
    private function tokenReportFiles(): array
    {
        $files = glob($this->basePath().'.*.log');

        if (false === $files) {
            return [];
        }

        return $files;
    }

    /**
     * Get the path to a worker report file.
     *
     * @param string $token Worker token
     */
    private function tokenReportPath(string $token): string
    {
        $safeToken = preg_replace('/[^A-Za-z0-9_.-]/', '_', $token) ?? 'worker';

        return $this->basePath().'.'.$safeToken.'.log';
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
     * Create the report directory if it does not exist.
     *
     * @param string $directory Directory path
     */
    private function ensureDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        return mkdir($directory, 0o777, true) || is_dir($directory);
    }

    /**
     * Write the report sorted by duration descending.
     *
     * @param string $path Report file path
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     * @param string $title Report title
     */
    private function writeFile(string $path, array $durations, string $title): void
    {
        uasort($durations, static fn (float $first, float $second): int => $second <=> $first);

        $lines = [
            sprintf('%s (%s)', $title, new DateTimeImmutable()->format('Y-m-d H:i:s')),
            sprintf('Total tests: %d, total time: %.4f s', count($durations), array_sum($durations)),
            '',
        ];

        $position = 1;

        foreach ($durations as $id => $duration) {
            $lines[] = sprintf('%6d. %10.4f s  %s', $position, $duration, $id);

            ++$position;
        }

        $this->ensureDirectory(dirname($path));

        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
    }

    /**
     * Read the report and get test durations.
     *
     * @param string $path Report file path
     *
     * @return array<string, float>
     */
    private function readFile(string $path): array
    {
        $contents = @file_get_contents($path);

        if (false === $contents) {
            return [];
        }

        $durations = [];

        foreach (explode(PHP_EOL, $contents) as $line) {
            if (1 !== preg_match('/^\s*\d+\.\s+([0-9]+\.[0-9]+) s  (.+)$/', $line, $matches)) {
                continue;
            }

            $durations[$matches[2]] = (float) $matches[1];
        }

        return $durations;
    }
}
